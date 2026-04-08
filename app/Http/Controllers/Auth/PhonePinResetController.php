<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\SmsOtpService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhonePinResetController extends Controller
{
    public function send(Request $request, SmsOtpService $smsOtp): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'phone_country' => ['nullable', 'string'],
        ]);

        $country = $data['phone_country'] ?? '+233';
        $normalized = Phone::normalize($data['phone_number'], $country);
        if (! $normalized || ! Phone::isValidGh($normalized)) {
            throw ValidationException::withMessages([
                'reset_phone_number' => 'Enter a valid phone number.',
            ]);
        }

        $user = $this->resolveUserByPhone($normalized, $data['phone_number'] ?? null);
        if (! $user) {
            throw ValidationException::withMessages([
                'reset_phone_number' => 'Account not found for this phone number.',
            ]);
        }

        try {
            $ok = $smsOtp->sendOtp($normalized);
            if (! $ok) {
                throw new \RuntimeException('OTP delivery failed.');
            }

            Log::info('PIN reset OTP send success', [
                'phone' => $normalized,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('PIN reset OTP send failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'reset_phone_number' => 'Unable to send OTP. Please try again shortly.',
            ]);
        }

        session([
            'pin_reset_pending_phone' => $normalized,
            'pin_reset_pending_country' => $country,
        ]);

        return back()
            ->with('pin_reset_status', 'sent')
            ->with('pin_reset_phone_masked', $this->maskPhone($normalized))
            ->withInput();
    }

    public function complete(Request $request, SmsOtpService $smsOtp): RedirectResponse
    {
        $pinLength = max(4, min(8, (int) config('auth_phone.pin.length', 4)));
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'phone_country' => ['nullable', 'string'],
            'otp' => ['required', 'string', 'min:4', 'max:8'],
            'reset_pin' => ['required', 'digits:'.$pinLength, 'confirmed'],
        ]);

        if ($this->isWeakPin((string) $data['reset_pin'])) {
            throw ValidationException::withMessages([
                'reset_pin' => 'Choose a less predictable PIN.',
            ]);
        }

        $country = $data['phone_country'] ?? (session('pin_reset_pending_country', '+233'));
        $pendingPhone = session('pin_reset_pending_phone');
        $normalized = Phone::normalize($data['phone_number'], $country) ?: $pendingPhone;

        if (! $normalized || ! Phone::isValidGh($normalized)) {
            throw ValidationException::withMessages([
                'reset_phone_number' => 'Enter a valid phone number.',
            ]);
        }

        if ($pendingPhone && $normalized !== $pendingPhone) {
            throw ValidationException::withMessages([
                'reset_phone_number' => 'Phone number changed. Request a new OTP.',
            ]);
        }

        $user = $this->resolveUserByPhone($normalized, $data['phone_number'] ?? null);
        if (! $user) {
            throw ValidationException::withMessages([
                'reset_phone_number' => 'Account not found for this phone number.',
            ]);
        }

        $approved = false;
        try {
            $approved = $smsOtp->verifyOtp($normalized, $data['otp']);
        } catch (\Throwable $exception) {
            Log::error('PIN reset OTP verify failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);
        }

        if (! $approved) {
            Log::warning('PIN reset OTP verify rejected', [
                'phone' => $normalized,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
            throw ValidationException::withMessages([
                'reset_otp' => 'Invalid or expired OTP.',
            ]);
        }

        $user->pin_hash = Hash::make($data['reset_pin']);
        $user->phone_verified_at = $user->phone_verified_at ?? now();
        $user->save();

        Log::info('PIN reset completed', [
            'user_id' => $user->id,
            'phone' => $normalized,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $request->session()->forget(['pin_reset_pending_phone', 'pin_reset_pending_country']);

        return redirect()
            ->route('login')
            ->with('status', 'PIN reset successful. Sign in with your new PIN.');
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! $digits || strlen($digits) < 4) {
            return $phone;
        }

        return '+***'.substr($digits, -4);
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

    private function isWeakPin(string $pin): bool
    {
        if (! (bool) config('auth_phone.pin.enforce_weak_denylist', true)) {
            return false;
        }

        $weakValues = config('auth_phone.pin.weak_values', []);

        return in_array($pin, $weakValues, true);
    }
}
