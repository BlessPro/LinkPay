<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioVerifyService
{
    private const FALLBACK_TTL_SECONDS = 600;

    public function sendOtp(string $phone, string $channel = 'whatsapp'): void
    {
        $this->ensureClientConfigured();
        $client = $this->client();

        if ($this->hasVerifyService()) {
            try {
                $client->verify->v2->services($this->serviceSid())
                    ->verifications
                    ->create($phone, $channel);
                return;
            } catch (RestException $exception) {
                Log::warning('Twilio Verify send failed, falling back', [
                    'phone' => $phone,
                    'status' => $exception->getStatusCode(),
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::warning('Twilio Verify service SID missing/invalid, using fallback OTP', [
                'phone' => $phone,
            ]);
        }

        $this->sendFallbackOtp($phone);
    }

    public function checkOtp(string $phone, string $code): bool
    {
        $this->ensureClientConfigured();
        $client = $this->client();

        if ($this->hasVerifyService()) {
            try {
                $result = $client->verify->v2->services($this->serviceSid())
                    ->verificationChecks
                    ->create([
                        'to' => $phone,
                        'code' => $code,
                    ]);

                if ($result->status === 'approved') {
                    return true;
                }
            } catch (RestException $exception) {
                Log::warning('Twilio Verify check failed, falling back', [
                    'phone' => $phone,
                    'status' => $exception->getStatusCode(),
                    'code' => $exception->getCode(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $this->checkFallbackOtp($phone, $code);
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

    private function hasVerifyService(): bool
    {
        $sid = (string) config('services.twilio.verify_service_sid');
        return str_starts_with($sid, 'VA');
    }

    private function ensureClientConfigured(): void
    {
        if (
            ! config('services.twilio.account_sid')
            || ! config('services.twilio.auth_token')
        ) {
            throw new \RuntimeException('Twilio is not configured.');
        }
    }

    private function sendFallbackOtp(string $phone): void
    {
        $code = (string) random_int(100000, 999999);
        $key = $this->fallbackKey($phone);

        Cache::put($key, hash('sha256', $code), self::FALLBACK_TTL_SECONDS);

        $message = "Your LinkPay OTP is {$code}. It expires in 10 minutes.";
        app(TwilioMessagingService::class)->sendWhatsApp($phone, $message);
    }

    private function checkFallbackOtp(string $phone, string $code): bool
    {
        $key = $this->fallbackKey($phone);
        $stored = Cache::get($key);

        if (! $stored) {
            return false;
        }

        if (hash('sha256', $code) !== $stored) {
            return false;
        }

        Cache::forget($key);
        return true;
    }

    private function fallbackKey(string $phone): string
    {
        return 'otp_fallback:'.sha1($phone);
    }
}
