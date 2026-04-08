<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackService $paystack, PaymentService $payments)
    {
        $payload = $request->getContent();
        $eventHash = hash('sha256', $payload);
        $eventName = (string) $request->input('event');
        $reference = data_get($request->input('data'), 'reference');
        $providerEventId = (string) (data_get($request->input('data'), 'id') ?? $request->header('x-paystack-event-id') ?? '');

        $existingEvent = null;
        try {
            $query = WebhookEvent::query()->where('provider', 'paystack');
            if ($providerEventId !== '') {
                $query->where('provider_event_id', $providerEventId);
            } else {
                $query->where('event_hash', $eventHash);
            }
            $existingEvent = $query->latest('id')->first();
        } catch (\Throwable $exception) {
            Log::warning('Webhook duplicate lookup failed', [
                'message' => $exception->getMessage(),
            ]);
        }

        if ($existingEvent && in_array($existingEvent->status, [WebhookEvent::STATUS_PROCESSED, WebhookEvent::STATUS_IGNORED], true)) {
            return response()->json(['status' => 'duplicate_ignored']);
        }

        $event = null;
        try {
            if ($existingEvent) {
                $event = $existingEvent;
            } else {
                try {
                    $event = WebhookEvent::create([
                        'provider' => 'paystack',
                        'provider_event_id' => $providerEventId !== '' ? $providerEventId : null,
                        'event' => $eventName,
                        'event_hash' => $eventHash,
                        'reference' => $reference,
                        'status' => WebhookEvent::STATUS_RECEIVED,
                        'payload' => $request->all(),
                        'received_at' => now(),
                    ]);
                } catch (QueryException $exception) {
                    if (! $this->isDuplicateEventHashException($exception)) {
                        throw $exception;
                    }

                    $event = WebhookEvent::query()
                        ->where('provider', 'paystack')
                        ->where(function ($query) use ($providerEventId, $eventHash) {
                            if ($providerEventId !== '') {
                                $query->where('provider_event_id', $providerEventId);
                            } else {
                                $query->where('event_hash', $eventHash);
                            }
                        })
                        ->latest('id')
                        ->first();
                }
            }

            if ($existingEvent) {
                $existingEvent->update([
                    'provider_event_id' => $providerEventId !== '' ? $providerEventId : $existingEvent->provider_event_id,
                    'event' => $eventName,
                    'reference' => $reference,
                    'status' => WebhookEvent::STATUS_RECEIVED,
                    'payload' => $request->all(),
                    'error_message' => null,
                    'received_at' => now(),
                ]);
            }
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

        if ($eventName !== 'charge.success') {
            $this->updateEvent($event, [
                'status' => WebhookEvent::STATUS_IGNORED,
                'verification_status' => 'ignored_event',
            ]);
            return response()->json(['status' => 'ignored']);
        }

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
            $paymentId = data_get($request->input('data'), 'metadata.payment_id');
            if ($paymentId) {
                $payment = Payment::where('id', $paymentId)->first();
            }
        }
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
                $this->markPaymentFailed($payment, 'verification_failed', [
                    'verified_status' => $verifiedStatus,
                ]);
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
            $this->markPaymentFailed($payment, 'verification_exception', [
                'message' => $exception->getMessage(),
            ]);
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

    private function markPaymentFailed(Payment $payment, string $reason, array $meta = []): void
    {
        if ($payment->status === Payment::STATUS_SUCCESS) {
            return;
        }

        $raw = $payment->raw_payload ?? [];
        $raw['fallback'] = array_merge([
            'reason' => $reason,
            'at' => now()->toIso8601String(),
        ], $meta);

        $payment->status = Payment::STATUS_FAILED;
        $payment->raw_payload = $raw;
        $payment->verified_at = now();
        $payment->save();
    }

    private function isDuplicateEventHashException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'webhook_events_provider_event_hash_unique')
            || str_contains($message, 'webhook_events_provider_event_id_unique')
            || str_contains($message, 'duplicate key value')
            || str_contains($message, 'unique constraint');
    }
}
