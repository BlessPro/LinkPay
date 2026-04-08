<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhonePinLoginController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $pinLength = max(4, min(8, (int) config('auth_phone.pin.length', 4)));
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
            'phone_country' => ['nullable', 'string'],
            'pin' => ['required', 'digits:'.$pinLength],
        ]);

        $country = $data['phone_country'] ?? '+233';
        $normalized = Phone::normalize($data['phone_number'], $country);
        if (! $normalized || ! Phone::isValidGh($normalized)) {
            throw ValidationException::withMessages([
                'phone_number' => 'Enter a valid phone number.',
            ]);
        }

        if ($this->isLockedOut($normalized)) {
            throw ValidationException::withMessages([
                'pin' => 'Too many PIN attempts. Try again in '.$this->remainingLockoutSeconds($normalized).' seconds.',
            ]);
        }

        $user = $this->resolveUserByPhone($normalized, $data['phone_number'] ?? null);
        if (! $user) {
            $this->recordFailedAttempt($normalized, 'user_not_found', $request);
            throw ValidationException::withMessages([
                'phone_number' => 'Account not found. Use OTP or email sign in.',
            ]);
        }

        if (! $user->pin_hash) {
            $this->recordFailedAttempt($normalized, 'pin_not_set', $request);
            throw ValidationException::withMessages([
                'pin' => 'PIN not set for this account. Use Forgot PIN to set one.',
            ]);
        }

        if (! Hash::check((string) $data['pin'], (string) $user->pin_hash)) {
            $this->recordFailedAttempt($normalized, 'invalid_pin', $request);
            throw ValidationException::withMessages([
                'pin' => 'Invalid PIN.',
            ]);
        }

        $user->phone_verified_at = $user->phone_verified_at ?? now();
        $user->save();
        $this->clearAttempts($normalized);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function resolveUserByPhone(string $normalized, ?string $rawPhone = null): ?User
    {
        $candidates = $this->buildPhoneCandidates($normalized, $rawPhone);

        $user = User::query()
            ->whereIn('phone', $candidates)
            ->first();

        if ($user) {
            if ($user->phone !== $normalized) {
                $user->phone = $normalized;
                $user->phone_verified_at = null;
                $user->save();
            }

            return $user;
        }

        $profile = SellerProfile::query()
            ->whereIn('phone', $candidates)
            ->first();
        $user = $profile?->user;

        if (! $user) {
            return null;
        }

        if ($profile && $profile->phone !== $normalized) {
            $profile->phone = $normalized;
            $profile->save();
        }

        if ($user->phone !== $normalized) {
            $user->phone = $normalized;
            $user->phone_verified_at = null;
            $user->save();
        }

        return $user;
    }

    private function buildPhoneCandidates(string $normalized, ?string $rawPhone = null): array
    {
        $candidates = [$normalized];
        $digits = preg_replace('/\D+/', '', $normalized);

        if ($digits && strlen($digits) >= 9) {
            $local = substr($digits, -9);
            $candidates[] = $local;
            $candidates[] = '0'.$local;
            $candidates[] = '+233'.$local;
            $candidates[] = '233'.$local;
        }

        if ($rawPhone) {
            $rawDigits = preg_replace('/\D+/', '', $rawPhone);
            if ($rawDigits) {
                $candidates[] = $rawDigits;
                if (strlen($rawDigits) >= 9) {
                    $localFromRaw = substr($rawDigits, -9);
                    $candidates[] = $localFromRaw;
                    $candidates[] = '0'.$localFromRaw;
                }
            }
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function attemptsKey(string $phone): string
    {
        return 'pin_login_attempts:'.sha1($phone);
    }

    private function lockoutKey(string $phone): string
    {
        return 'pin_login_lockout:'.sha1($phone);
    }

    private function maxAttempts(): int
    {
        return max(3, (int) config('auth_phone.pin.max_login_attempts', 5));
    }

    private function lockoutSeconds(): int
    {
        return max(60, (int) config('auth_phone.pin.lockout_seconds', 900));
    }

    private function isLockedOut(string $phone): bool
    {
        $lockoutUntil = (int) Cache::get($this->lockoutKey($phone), 0);
        if ($lockoutUntil <= 0) {
            return false;
        }

        if ($lockoutUntil <= now()->timestamp) {
            Cache::forget($this->lockoutKey($phone));

            return false;
        }

        return true;
    }

    private function remainingLockoutSeconds(string $phone): int
    {
        $lockoutUntil = (int) Cache::get($this->lockoutKey($phone), 0);
        if ($lockoutUntil <= 0) {
            return $this->lockoutSeconds();
        }

        return max(1, $lockoutUntil - now()->timestamp);
    }

    private function recordFailedAttempt(string $phone, string $reason, Request $request): void
    {
        $attemptsKey = $this->attemptsKey($phone);
        $lockoutKey = $this->lockoutKey($phone);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addSeconds($this->lockoutSeconds()));

        Log::warning('Phone PIN login failed', [
            'phone' => $phone,
            'reason' => $reason,
            'attempts' => $attempts,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        if ($attempts < $this->maxAttempts()) {
            return;
        }

        $lockoutUntil = now()->addSeconds($this->lockoutSeconds())->timestamp;
        Cache::put($lockoutKey, $lockoutUntil, now()->addSeconds($this->lockoutSeconds()));
        Cache::forget($attemptsKey);

        Log::warning('Phone PIN login lockout applied', [
            'phone' => $phone,
            'ip' => $request->ip(),
            'lockout_seconds' => $this->lockoutSeconds(),
        ]);
    }

    private function clearAttempts(string $phone): void
    {
        Cache::forget($this->attemptsKey($phone));
        Cache::forget($this->lockoutKey($phone));
    }
}
