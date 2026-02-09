<?php

namespace App\Services;

use App\Support\Phone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Twilio\Exceptions\RestException;
use Twilio\Rest\Client;

class TwilioVerifyService
{
    private const FALLBACK_TTL_SECONDS = 600;

    /**
     * Send OTP using Twilio Verify when available. Falls back to app-generated OTP delivered
     * via SMS/WhatsApp based on config when Verify is missing/unavailable.
     *
     * Returns true when the request was accepted by the provider (or fallback delivered),
     * false when delivery failed.
     */
    public function sendOtp(string $phone, ?string $channel = null): bool
    {
        $this->ensureClientConfigured();
        $client = $this->client();
        $phone = $this->normalizePhone($phone);

        $channel = $channel ?: (string) config('services.twilio.verify_default_channel', 'whatsapp');

        if ($this->hasVerifyService()) {
            try {
                $verification = $client->verify->v2->services($this->serviceSid())
                    ->verifications
                    ->create($phone, $channel);

                Log::info('Twilio Verify OTP requested', [
                    'to' => $phone,
                    'channel' => $channel,
                    'sid' => $verification->sid ?? null,
                    'status' => $verification->status ?? null,
                ]);

                return true;
            } catch (RestException $exception) {
                Log::warning('Twilio Verify send failed, falling back', [
                    'phone' => $phone,
                    'channel' => $channel,
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

        return $this->sendFallbackOtp($phone);
    }

    public function checkOtp(string $phone, string $code): bool
    {
        $this->ensureClientConfigured();
        $client = $this->client();
        $phone = $this->normalizePhone($phone);

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

    private function sendFallbackOtp(string $phone): bool
    {
        $code = (string) random_int(100000, 999999);
        $key = $this->fallbackKey($phone);

        Cache::put($key, hash('sha256', $code), self::FALLBACK_TTL_SECONDS);

        $message = "Your 8Kommerce OTP is {$code}. It expires in 10 minutes.";

        $fallbackChannel = (string) config('services.twilio.otp_fallback_channel', 'sms');
        $messaging = app(TwilioMessagingService::class);

        try {
            if ($fallbackChannel === 'whatsapp') {
                $messaging->sendWhatsApp($phone, $message);
            } else {
                $messaging->sendSms($phone, $message);
            }

            Log::info('OTP fallback delivered', [
                'to' => $phone,
                'channel' => $fallbackChannel,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('OTP fallback delivery failed', [
                'to' => $phone,
                'channel' => $fallbackChannel,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
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

    private function normalizePhone(string $raw): string
    {
        $defaultCountry = (string) config('services.twilio.default_country', '+233');
        $normalized = Phone::normalize($raw, $defaultCountry);

        return $normalized ?: $raw;
    }
}
