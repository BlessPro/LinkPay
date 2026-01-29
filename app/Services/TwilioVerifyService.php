<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioVerifyService
{
    public function sendOtp(string $phone, string $channel = 'whatsapp'): void
    {
        $this->ensureConfigured();
        $client = $this->client();

        $client->verify->v2->services($this->serviceSid())
            ->verifications
            ->create($phone, $channel);
    }

    public function checkOtp(string $phone, string $code): bool
    {
        $this->ensureConfigured();
        $client = $this->client();

        $result = $client->verify->v2->services($this->serviceSid())
            ->verificationChecks
            ->create([
                'to' => $phone,
                'code' => $code,
            ]);

        return $result->status === 'approved';
    }

    private function client(): Client
    {
        return new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
    }

    private function serviceSid(): string
    {
        return (string) config('services.twilio.verify_service_sid');
    }

    private function ensureConfigured(): void
    {
        if (
            ! config('services.twilio.account_sid')
            || ! config('services.twilio.auth_token')
            || ! config('services.twilio.verify_service_sid')
        ) {
            throw new \RuntimeException('Twilio Verify is not configured.');
        }
    }
}
