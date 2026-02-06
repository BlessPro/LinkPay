<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackService $paystack, PaymentService $payments)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $expected = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

        if (! $signature || ! hash_equals($expected, $signature)) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        if ($request->input('event') !== 'charge.success') {
            return response()->json(['status' => 'ignored']);
        }

        $reference = data_get($request->input('data'), 'reference');
        if (! $reference) {
            return response()->json(['status' => 'missing_reference']);
        }

        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            return response()->json(['status' => 'payment_not_found']);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            return response()->json(['status' => 'already_processed']);
        }

        try {
            $verification = $paystack->verifyTransaction($reference);
            $verifiedStatus = data_get($verification, 'data.status');

            if ($verifiedStatus !== 'success') {
                return response()->json(['status' => 'verification_failed']);
            }

            $payments->markSuccess($payment, data_get($verification, 'data', []));
        } catch (\Throwable $exception) {
            Log::warning('Webhook verification/markSuccess failed', [
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            // Return 200 so Paystack doesn't hammer retries when the issue is transient/logical.
            return response()->json(['status' => 'error']);
        }

        return response()->json(['status' => 'ok']);
    }
}
