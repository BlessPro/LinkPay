<?php

namespace App\Services;

use App\Models\TwilioMessageLog;
use App\Support\Phone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioMessagingService
{
    public function sendWhatsApp(string $to, string $body, array $context = []): void
    {
        $this->ensureWhatsAppConfigured();

        $client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );

        $from = (string) config('services.twilio.whatsapp_from');
        $to = $this->formatWhatsApp($to);
        $from = $this->formatWhatsApp($from);
        $statusCallback = $this->statusCallbackUrl();

        try {
            $message = $client->messages->create($to, [
                'from' => $from,
                'body' => $body,
                'statusCallback' => $statusCallback,
            ]);

            $this->logMessage(array_merge($context, [
                'channel' => 'whatsapp',
                'to' => $to,
                'from' => $from,
                'status' => $message->status ?? 'queued',
                'provider_sid' => $message->sid ?? null,
                'payload' => ['body' => $body],
                'sent_at' => Carbon::now(),
            ]));
        } catch (RestException $e) {
            // Common WhatsApp sandbox limitation: freeform messages require a recent inbound message (24h window).
            Log::warning('Twilio WhatsApp send failed', [
                'to' => $to,
                'status' => $e->getStatusCode(),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            $this->logMessage(array_merge($context, [
                'channel' => 'whatsapp',
                'to' => $to,
                'from' => $from,
                'status' => 'failed',
                'error_code' => (string) $e->getCode(),
                'error_message' => $e->getMessage(),
                'payload' => ['body' => $body],
                'sent_at' => Carbon::now(),
            ]));

            throw $e;
        }
    }

    public function sendSms(string $to, string $body, array $context = []): void
    {
        $this->ensureSmsConfigured();

        $client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );

        $to = $this->formatE164($to);
        $payload = ['body' => $body];

        $messagingServiceSid = (string) config('services.twilio.messaging_service_sid');
        $smsFrom = (string) config('services.twilio.sms_from');
        $statusCallback = $this->statusCallbackUrl();

        if (str_starts_with($messagingServiceSid, 'MG')) {
            $payload['messagingServiceSid'] = $messagingServiceSid;
        } elseif ($smsFrom) {
            $payload['from'] = $smsFrom;
        }
        $payload['statusCallback'] = $statusCallback;

        try {
            $message = $client->messages->create($to, $payload);

            $this->logMessage(array_merge($context, [
                'channel' => 'sms',
                'to' => $to,
                'from' => $payload['from'] ?? $payload['messagingServiceSid'] ?? null,
                'status' => $message->status ?? 'queued',
                'provider_sid' => $message->sid ?? null,
                'payload' => ['body' => $body],
                'sent_at' => Carbon::now(),
            ]));
        } catch (RestException $e) {
            $this->logMessage(array_merge($context, [
                'channel' => 'sms',
                'to' => $to,
                'from' => $payload['from'] ?? $payload['messagingServiceSid'] ?? null,
                'status' => 'failed',
                'error_code' => (string) $e->getCode(),
                'error_message' => $e->getMessage(),
                'payload' => ['body' => $body],
                'sent_at' => Carbon::now(),
            ]));

            throw $e;
        }
    }

    private function formatWhatsApp(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, 'whatsapp:')) {
            return $value;
        }

        $value = $this->formatE164($value);

        return 'whatsapp:'.$value;
    }

    private function formatE164(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, '+')) {
            return $value;
        }

        $defaultCountry = (string) config('services.twilio.default_country', '+233');
        $normalized = Phone::normalize($value, $defaultCountry);
        return $normalized ?: $value;
    }

    private function ensureWhatsAppConfigured(): void
    {
        if (! config('services.twilio.account_sid') || ! config('services.twilio.auth_token') || ! config('services.twilio.whatsapp_from')) {
            throw new \RuntimeException('Twilio WhatsApp is not configured (TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN/TWILIO_WHATSAPP_FROM).');
        }
    }

    private function ensureSmsConfigured(): void
    {
        if (! config('services.twilio.account_sid') || ! config('services.twilio.auth_token')) {
            throw new \RuntimeException('Twilio is not configured (TWILIO_ACCOUNT_SID/TWILIO_AUTH_TOKEN).');
        }

        $messagingServiceSid = (string) config('services.twilio.messaging_service_sid');
        $smsFrom = (string) config('services.twilio.sms_from');
        if (! str_starts_with($messagingServiceSid, 'MG') && ! $smsFrom) {
            throw new \RuntimeException('Twilio SMS is not configured (TWILIO_MESSAGING_SERVICE_SID or TWILIO_SMS_FROM).');
        }
    }

    private function logMessage(array $data): void
    {
        TwilioMessageLog::create([
            'user_id' => $data['user_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'channel' => $data['channel'] ?? 'sms',
            'direction' => $data['direction'] ?? 'outgoing',
            'to' => $data['to'] ?? null,
            'from' => $data['from'] ?? null,
            'status' => $data['status'] ?? null,
            'provider_sid' => $data['provider_sid'] ?? null,
            'error_code' => $data['error_code'] ?? null,
            'error_message' => $data['error_message'] ?? null,
            'context_type' => $data['context_type'] ?? null,
            'context_id' => $data['context_id'] ?? null,
            'payload' => $data['payload'] ?? null,
            'sent_at' => $data['sent_at'] ?? Carbon::now(),
        ]);
    }

    private function statusCallbackUrl(): string
    {
        $configured = trim((string) config('services.twilio.status_callback_url'));
        if ($configured !== '') {
            return $configured;
        }

        return route('webhooks.twilio.status');
    }
}
