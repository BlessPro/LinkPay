<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaystackService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicInvoiceController extends Controller
{
    public function show(string $token)
    {
        $invoice = Invoice::where('token', $token)
            ->with(['user.sellerProfile', 'payments'])
            ->firstOrFail();

        return view('public.invoice', [
            'invoice' => $invoice,
            'seller' => $invoice->user->sellerProfile,
            'amountDue' => $invoice->amountDue(),
            'balance' => $invoice->balanceRemaining(),
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function pay(Request $request, string $token, PaystackService $paystack)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $invoice = Invoice::where('token', $token)
            ->with('user.sellerProfile')
            ->firstOrFail();

        if ($invoice->status === Invoice::STATUS_PAID) {
            return back()->withErrors(['invoice' => 'This invoice is already fully paid.']);
        }

        $seller = $invoice->user->sellerProfile;
        if (! $seller || ! $seller->paystack_subaccount_code) {
            return back()->withErrors(['paystack' => 'Seller is not connected to Paystack yet.']);
        }

        $amountDue = $invoice->amountDue();
        if (Money::compare($amountDue, '0.00') !== 1) {
            return back()->withErrors(['invoice' => 'No balance due on this invoice.']);
        }

        $reference = (string) Str::uuid();

        $payment = Payment::create([
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoice->id,
            'reference' => $reference,
            'amount' => $amountDue,
            'status' => Payment::STATUS_PENDING,
        ]);

        $platformFee = (string) config('services.paystack.platform_fee_flat', '0');
        $platformFee = Money::compare($platformFee, '0.00') === 1 ? $platformFee : null;

        $data = $paystack->initializeTransaction(
            $amountDue,
            $request->input('email'),
            [
                'reference' => $reference,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'purpose' => 'invoice',
            ],
            $seller->paystack_subaccount_code,
            $platformFee
        );

        return redirect()->away($data['authorization_url']);
    }

    public function success(Request $request)
    {
        $reference = $request->query('reference');
        $payment = null;

        if ($reference) {
            $payment = Payment::where('reference', $reference)
                ->with(['invoice.user.sellerProfile', 'product'])
                ->first();
        }

        return view('public.success', [
            'payment' => $payment,
            'reference' => $reference,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }
}
