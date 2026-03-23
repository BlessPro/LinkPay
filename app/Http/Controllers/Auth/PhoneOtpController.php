<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\SmsOtpService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhoneOtpController extends Controller
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
                'phone_number' => 'Enter a valid phone number.',
            ]);
        }

        $user = $this->resolveUserByPhone($normalized, $data['phone_number'] ?? null);

        if (! $user) {
            throw ValidationException::withMessages([
                'phone_number' => 'Account not found. Use email to sign in.',
            ]);
        }

        try {
            $ok = $smsOtp->sendOtp($normalized);
            if (! $ok) {
                throw new \RuntimeException('OTP delivery failed.');
            }
        } catch (\Throwable $exception) {
            Log::error('SMS OTP send failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'phone_number' => 'Unable to send OTP. Please try again shortly.',
            ]);
        }

        session([
            'phone_login_pending_phone' => $normalized,
            'phone_login_pending_country' => $country,
        ]);

        return back()
            ->with('otp_status', 'sent')
            ->with('otp_phone_masked', $this->maskPhone($normalized))
            ->withInput();
    }

    public function verify(Request $request, SmsOtpService $smsOtp): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'phone_country' => ['nullable', 'string'],
            'otp' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $country = $data['phone_country'] ?? (session('phone_login_pending_country', '+233'));
        $pendingPhone = session('phone_login_pending_phone');
        $normalized = Phone::normalize($data['phone_number'], $country) ?: $pendingPhone;
        if (! $normalized || ! Phone::isValidGh($normalized)) {
            if ($pendingPhone && Phone::isValidGh($pendingPhone)) {
                $normalized = $pendingPhone;
            } else {
                throw ValidationException::withMessages([
                    'phone_number' => 'Enter a valid phone number.',
                ]);
            }
        }

        if (! $normalized) {
            throw ValidationException::withMessages([
                'phone_number' => 'Enter a valid phone number.',
            ]);
        }

        $user = $this->resolveUserByPhone($normalized, $data['phone_number'] ?? null);
        if (! $user) {
            throw ValidationException::withMessages([
                'phone_number' => 'Account not found. Use email to sign in.',
            ]);
        }

        $approved = false;
        try {
            $approved = $smsOtp->verifyOtp($normalized, $data['otp']);
        } catch (\Throwable $exception) {
            Log::error('SMS OTP verify failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);
            $approved = false;
        }
        if (! $approved) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP.',
            ]);
        }

        $user->phone_verified_at = $user->phone_verified_at ?? now();
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['phone_login_pending_phone', 'phone_login_pending_country']);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! $digits || strlen($digits) < 4) {
            return $phone;
        }

        $visible = substr($digits, -4);

        return '+***'.$visible;
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
}
