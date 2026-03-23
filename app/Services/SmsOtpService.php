<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsOtpService
{
    private const TTL_SECONDS = 600;

    public function sendOtp(string $phone): bool
    {
        $code = (string) random_int(100000, 999999);
        $key = $this->cacheKey($phone);
        Cache::put($key, hash('sha256', $code), self::TTL_SECONDS);

        $message = "Your 8Kommerce OTP is {$code}. It expires in 10 minutes.";

        try {
            app(HubtelSmsService::class)->send($phone, $message, [
                'context_type' => 'auth_login_otp_sms',
            ]);

            Log::info('Hubtel OTP sent', [
                'phone' => $phone,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Hubtel OTP send failed', [
                'phone' => $phone,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        $key = $this->cacheKey($phone);
        $stored = Cache::get($key);

        if (! $stored) {
            return false;
        }

        if (hash('sha256', trim($code)) !== $stored) {
            return false;
        }

        Cache::forget($key);

        return true;
    }

    private function cacheKey(string $phone): string
    {
        return 'sms_otp:'.sha1($phone);
    }
}

