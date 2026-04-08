<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmsOtpService
{
    public function sendOtp(string $phone): bool
    {
        if ($this->isLockedOut($phone)) {
            Log::warning('OTP send blocked due to lockout', ['phone' => $phone]);

            return false;
        }

        if (! $this->acquireResendCooldown($phone)) {
            Log::warning('OTP send blocked due to resend cooldown', ['phone' => $phone]);

            return false;
        }

        $length = max(4, min(8, (int) config('auth_phone.otp.length', 6)));
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('', $length, '9');
        $code = (string) random_int($min, $max);
        $key = $this->cacheKey($phone);
        $ttlSeconds = max(60, (int) config('auth_phone.otp.ttl_seconds', 600));
        Cache::put($key, hash('sha256', $code), $ttlSeconds);
        Cache::forget($this->attemptsKey($phone));

        $expiresMinutes = (int) ceil($ttlSeconds / 60);
        $message = "Your 8Kommerce OTP is {$code}. It expires in {$expiresMinutes} minutes.";

        try {
            app(HubtelSmsService::class)->send($phone, $message, [
                'context_type' => 'auth_login_otp_sms',
            ]);

            Log::info('Hubtel OTP sent', [
                'phone' => $phone,
            ]);

            return true;
        } catch (\Throwable $exception) {
            Cache::forget($this->cooldownKey($phone));
            Log::error('Hubtel OTP send failed', [
                'phone' => $phone,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function verifyOtp(string $phone, string $code): bool
    {
        if ($this->isLockedOut($phone)) {
            return false;
        }

        $key = $this->cacheKey($phone);
        $stored = Cache::get($key);

        if (! $stored) {
            return false;
        }

        if (hash('sha256', trim($code)) !== $stored) {
            $attempts = $this->recordFailedAttempt($phone);
            $maxAttempts = max(1, (int) config('auth_phone.otp.max_verify_attempts', 5));
            if ($attempts >= $maxAttempts) {
                $this->setLockout($phone);
            }

            return false;
        }

        Cache::forget($key);
        Cache::forget($this->attemptsKey($phone));
        Cache::forget($this->lockoutKey($phone));

        return true;
    }

    private function cacheKey(string $phone): string
    {
        return 'sms_otp:'.sha1($phone);
    }

    private function cooldownKey(string $phone): string
    {
        return 'sms_otp_cooldown:'.sha1($phone);
    }

    private function attemptsKey(string $phone): string
    {
        return 'sms_otp_attempts:'.sha1($phone);
    }

    private function lockoutKey(string $phone): string
    {
        return 'sms_otp_lockout:'.sha1($phone);
    }

    private function acquireResendCooldown(string $phone): bool
    {
        $cooldown = max(0, (int) config('auth_phone.otp.resend_cooldown_seconds', 30));
        if ($cooldown === 0) {
            return true;
        }

        return Cache::add($this->cooldownKey($phone), true, $cooldown);
    }

    private function recordFailedAttempt(string $phone): int
    {
        $attemptsKey = $this->attemptsKey($phone);
        $attemptsTtl = max(300, (int) config('auth_phone.otp.lockout_seconds', 900));
        $current = (int) Cache::get($attemptsKey, 0);
        $next = $current + 1;
        Cache::put($attemptsKey, $next, $attemptsTtl);

        return $next;
    }

    private function setLockout(string $phone): void
    {
        $seconds = max(60, (int) config('auth_phone.otp.lockout_seconds', 900));
        Cache::put($this->lockoutKey($phone), true, $seconds);
        Cache::forget($this->cacheKey($phone));
        Cache::forget($this->attemptsKey($phone));
    }

    private function isLockedOut(string $phone): bool
    {
        return (bool) Cache::get($this->lockoutKey($phone), false);
    }
}
