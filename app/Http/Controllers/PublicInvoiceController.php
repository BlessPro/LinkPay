<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AnalyticsService;
use App\Services\OgImageService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Support\Email;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PublicInvoiceController extends Controller
{
    public function show(string $token, Request $request, AnalyticsService $analytics)
    {
        $invoice = Invoice::where('token', $token)
            ->with(['user.sellerProfile', 'payments'])
            ->firstOrFail();

        $analytics->trackEvent(
            $request,
            $invoice->user_id,
            \App\Models\AnalyticsEvent::TYPE_INVOICE_VIEW,
            'invoice',
            (string) $invoice->id
        );

        $sellerName = $invoice->user->sellerProfile?->business_name ?? 'Seller';
        $ogPath = app(OgImageService::class)->publicInvoiceOgPath($invoice->token);
        if (Storage::disk('public')->exists($ogPath)) {
            $ogImage = url(Storage::url($ogPath));
        } else {
            $ogImage = $invoice->image_path
                ? url('storage/'.$invoice->image_path)
                : asset('images/og-default.png');
        }

        $currency = config('services.paystack.currency', 'GHS');

        return view('public.invoice', [
            'invoice' => $invoice,
            'seller' => $invoice->user->sellerProfile,
            'amountDue' => $invoice->amountDue(),
            'balance' => $invoice->balanceRemaining(),
            'currency' => $currency,
            'paymentsEnabled' => $invoice->user?->canUsePaymentsFeature() ?? false,
            'ogTitle' => $sellerName.' - '.$invoice->title,
            'ogDescription' => 'Amount due: '.Money::format($invoice->amountDue(), $currency).'. Tap to view and pay securely.',
            'ogImage' => $ogImage,
            'ogUrl' => route('public.invoice', $invoice->token),
            'ogType' => 'website',
        ]);
    }

    public function pay(Request $request, string $token, PaystackService $paystack, AnalyticsService $analytics)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email'],
            'phone_number' => ['required', 'string', 'max:25'],
            'phone_country' => ['nullable', 'string'],
        ]);

        $invoice = Invoice::where('token', $token)
            ->with('user.sellerProfile')
            ->firstOrFail();

        $sellerUser = $invoice->user;
        if ($sellerUser) {
            if (! $sellerUser->canUsePaymentsFeature()) {
                return back()->withErrors([
                    'paystack' => 'This seller is not on the Payments plan. Please use Chat on WhatsApp.',
                ])->withInput();
            }
        }

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
        $phoneInput = $request->input('phone_number');
        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phoneInput)));
        $primaryPhone = $phoneParts[0] ?? $phoneInput;
        $phone = Phone::normalize($primaryPhone, $request->input('phone_country', '+233'));
        if (! $phone || ! Phone::isValidGh($primaryPhone)) {
            return back()->withErrors(['phone_number' => 'Enter a valid WhatsApp number.'])->withInput();
        }

        $emailInput = $request->input('email');
        $emailParts = array_filter(array_map('trim', explode(',', (string) $emailInput)));
        $email = $emailParts[0] ?? $emailInput;
        if (! $email) {
            $email = Email::placeholder($reference);
        }

        $analytics->trackEvent(
            $request,
            $invoice->user_id,
            \App\Models\AnalyticsEvent::TYPE_INVOICE_CLICK,
            'invoice',
            (string) $invoice->id
        );

        $payment = Payment::create([
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoice->id,
            'reference' => $reference,
            'amount' => $amountDue,
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'name' => $request->input('name'),
                    'email' => $email,
                    'phone' => $phone,
                ],
            ],
        ]);

        // Platform commission is a percent of the transaction (e.g. 1%).
        $commissionPercent = (string) config('plans.payments.commission_percent', '0.01');
        $platformFee = Money::percent((string) $amountDue, $commissionPercent);
        $platformFee = Money::compare($platformFee, '0.00') === 1 ? $platformFee : null;

        $data = $paystack->initializeTransaction(
            $amountDue,
            $email,
            [
                'reference' => $reference,
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'purpose' => 'invoice',
                'platform_fee' => $platformFee,
                'customer' => [
                    'name' => $request->input('name'),
                    'email' => $email,
                    'phone' => $phone,
                ],
            ],
            $seller->paystack_subaccount_code,
            $platformFee
        );

        return redirect()->away($data['authorization_url']);
    }

    public function success(Request $request, PaystackService $paystack, PaymentService $payments)
    {
        $reference = $request->query('reference');
        $payment = null;

        if ($reference) {
            $payment = Payment::where('reference', $reference)
                ->with(['invoice.user.sellerProfile', 'product'])
                ->first();

            if ($payment && $payment->status !== Payment::STATUS_SUCCESS) {
                try {
                    $verification = $paystack->verifyTransaction($reference);
                    if (data_get($verification, 'data.status') === 'success') {
                        $payments->markSuccess($payment, data_get($verification, 'data', []));
                        $payment->refresh();
                    }
                } catch (\Throwable $exception) {
                    // Ignore verification errors on success page.
                }
            }
        }

        $listingSlug = $payment
            ? $payment->invoice?->user?->sellerProfile?->public_slug
                ?? $payment->product?->user?->sellerProfile?->public_slug
            : null;
        $listingUrl = $listingSlug ? route('public.listing', $listingSlug) : null;

        return view('public.success', [
            'payment' => $payment,
            'reference' => $reference,
            'currency' => config('services.paystack.currency', 'GHS'),
            'listingUrl' => $listingUrl,
        ]);
    }
}
