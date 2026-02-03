<?php

namespace App\Services;

use App\Support\Phone;
use Twilio\Rest\Client;

class TwilioMessagingService
{
    public function sendWhatsApp(string $to, string $body): void
    {
        $this->ensureConfigured();

        $client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );

        $from = (string) config('services.twilio.whatsapp_from');
        $to = $this->formatWhatsApp($to);
        $from = $this->formatWhatsApp($from);

        $client->messages->create($to, [
            'from' => $from,
            'body' => $body,
        ]);
    }

    private function formatWhatsApp(string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, 'whatsapp:')) {
            return $value;
        }

        if (! str_starts_with($value, '+')) {
            $normalized = Phone::normalize($value, '+233');
            if ($normalized) {
                $value = $normalized;
            }
        }

        return 'whatsapp:'.$value;
    }

    private function ensureConfigured(): void
    {
        if (
            ! config('services.twilio.account_sid')
            || ! config('services.twilio.auth_token')
            || ! config('services.twilio.whatsapp_from')
        ) {
            throw new \RuntimeException('Twilio WhatsApp is not configured.');
        }
    }
}
