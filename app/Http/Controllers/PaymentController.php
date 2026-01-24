<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = $request->user()->payments()->latest()->paginate(10);

        return view('dashboard.payments.index', [
            'payments' => $payments,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function verify(Request $request, Payment $payment, PaystackService $paystack, PaymentService $payments)
    {
        if (! $request->user()->is_admin && $payment->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $verification = $paystack->verifyTransaction($payment->reference);
            if (data_get($verification, 'data.status') === 'success') {
                $payments->markSuccess($payment, data_get($verification, 'data', []));
                return back()->with('status', 'payment-verified');
            }

            return back()->withErrors(['payment' => 'Payment is not marked successful on Paystack yet.']);
        } catch (\Throwable $exception) {
            return back()->withErrors(['payment' => 'Unable to verify payment at the moment.']);
        }
    }
}
