<?php

namespace App\Services;

use App\Support\Phone;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioMessagingService
{
    public function sendWhatsApp(string $to, string $body): void
    {
        $this->ensureWhatsAppConfigured();

        $client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );

        $from = (string) config('services.twilio.whatsapp_from');
        $to = $this->formatWhatsApp($to);
        $from = $this->formatWhatsApp($from);

        try {
            $client->messages->create($to, [
                'from' => $from,
                'body' => $body,
            ]);
        } catch (RestException $e) {
            // Common WhatsApp sandbox limitation: freeform messages require a recent inbound message (24h window).
            Log::warning('Twilio WhatsApp send failed', [
                'to' => $to,
                'status' => $e->getStatusCode(),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function sendSms(string $to, string $body): void
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

        if (str_starts_with($messagingServiceSid, 'MG')) {
            $payload['messagingServiceSid'] = $messagingServiceSid;
        } elseif ($smsFrom) {
            $payload['from'] = $smsFrom;
        }

        $client->messages->create($to, $payload);
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
}
