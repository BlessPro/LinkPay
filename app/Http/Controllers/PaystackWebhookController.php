<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackService $paystack, PaymentService $payments)
    {
        $payload = $request->getContent();
        $event = null;
        try {
            $event = WebhookEvent::create([
                'provider' => 'paystack',
                'event' => $request->input('event'),
                'reference' => data_get($request->input('data'), 'reference'),
                'status' => WebhookEvent::STATUS_RECEIVED,
                'payload' => $request->all(),
                'received_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            // Webhook handling must continue even if observability tables are missing.
            Log::warning('Webhook event logging failed', [
                'message' => $exception->getMessage(),
            ]);
        }

        $signature = $request->header('x-paystack-signature');
        $expected = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

        if (! $signature || ! hash_equals($expected, $signature)) {
            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_FAILED,
                'verification_status' => 'invalid_signature',
                'error_message' => 'Signature mismatch',
            ]);
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        if ($request->input('event') !== 'charge.success') {
            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_IGNORED,
                'verification_status' => 'ignored_event',
            ]);
            return response()->json(['status' => 'ignored']);
        }

        $reference = data_get($request->input('data'), 'reference');
        if (! $reference) {
            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_FAILED,
                'verification_status' => 'missing_reference',
                'error_message' => 'Missing reference',
            ]);
            return response()->json(['status' => 'missing_reference']);
        }

        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_FAILED,
                'verification_status' => 'payment_not_found',
                'reference' => $reference,
                'error_message' => 'Payment not found',
            ]);
            return response()->json(['status' => 'payment_not_found']);
        }

        if ($event) {
            $event->payment_id = $payment->id;
            $event->reference = $reference;
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_PROCESSED,
                'verification_status' => 'already_processed',
            ]);
            return response()->json(['status' => 'already_processed']);
        }

        try {
            $verification = $paystack->verifyTransaction($reference);
            $verifiedStatus = data_get($verification, 'data.status');

            if ($verifiedStatus !== 'success') {
                $this->updateEvent($event, [
                    'status' => WebhookEvent::STATUS_FAILED,
                    'verification_status' => 'verification_failed',
                    'error_message' => 'Verify API did not return success',
                ]);
                return response()->json(['status' => 'verification_failed']);
            }

            $payments->markSuccess($payment, data_get($verification, 'data', []));
            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_PROCESSED,
                'verification_status' => 'success',
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Webhook verification/markSuccess failed', [
                'reference' => $reference,
                'message' => $exception->getMessage(),
            ]);

            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_FAILED,
                'verification_status' => 'exception',
                'error_message' => $exception->getMessage(),
            ]);

            // Return non-2xx so Paystack retries delivery.
            return response()->json(['status' => 'error'], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    private function updateEvent(?WebhookEvent $event, array $data): void
    {
        if (! $event) {
            return;
        }

        try {
            $event->update($data);
        } catch (\Throwable $exception) {
            Log::warning('Webhook event update failed', [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
