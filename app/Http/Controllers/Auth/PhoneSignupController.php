<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\SmsOtpService;
use App\Support\Phone;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PhoneSignupController extends Controller
{
    public function send(Request $request, SmsOtpService $smsOtp): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
            'phone_country' => ['nullable', 'string', Rule::in(['+233'])],
        ]);

        $country = $data['phone_country'] ?? '+233';
        $normalized = Phone::normalize($data['phone_number'], $country);
        if (! $normalized || ! Phone::isValidGh($normalized)) {
            throw ValidationException::withMessages([
                'phone_number' => 'Enter a valid phone number.',
            ]);
        }

        if (User::where('phone', $normalized)->exists()) {
            throw ValidationException::withMessages([
                'phone_number' => 'This phone number already has an account. Sign in instead.',
            ]);
        }

        try {
            $ok = $smsOtp->sendOtp($normalized);
            if (! $ok) {
                throw new \RuntimeException('OTP delivery failed.');
            }

            Log::info('Signup OTP send success', [
                'phone' => $normalized,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Signup OTP send failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'phone_number' => 'Unable to send OTP now. Please try again shortly.',
            ]);
        }

        session([
            'register_phone_pending_phone' => $normalized,
            'register_phone_pending_country' => $country,
        ]);

        return back()
            ->with('register_otp_status', 'sent')
            ->with('register_otp_phone_masked', $this->maskPhone($normalized))
            ->withInput();
    }

    public function complete(Request $request, SmsOtpService $smsOtp): RedirectResponse
    {
        $pinLength = max(4, min(8, (int) config('auth_phone.pin.length', 4)));
        $data = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
            'phone_country' => ['nullable', 'string', Rule::in(['+233'])],
            'otp' => ['required', 'string', 'min:4', 'max:8'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'pin' => ['required', 'digits:'.$pinLength, 'confirmed'],
        ]);

        if ($this->isWeakPin((string) $data['pin'])) {
            throw ValidationException::withMessages([
                'pin' => 'Choose a less predictable PIN.',
            ]);
        }

        $country = $data['phone_country'] ?? (session('register_phone_pending_country', '+233'));
        $pendingPhone = session('register_phone_pending_phone');
        $normalized = Phone::normalize($data['phone_number'], $country) ?: $pendingPhone;

        if (! $normalized || ! Phone::isValidGh($normalized)) {
            throw ValidationException::withMessages([
                'phone_number' => 'Enter a valid phone number.',
            ]);
        }

        if ($pendingPhone && $normalized !== $pendingPhone) {
            throw ValidationException::withMessages([
                'phone_number' => 'Phone number changed. Request a new OTP.',
            ]);
        }

        if (User::where('phone', $normalized)->exists()) {
            throw ValidationException::withMessages([
                'phone_number' => 'This phone number already has an account. Sign in instead.',
            ]);
        }

        $approved = false;
        try {
            $approved = $smsOtp->verifyOtp($normalized, $data['otp']);
        } catch (\Throwable $exception) {
            Log::error('Signup OTP verify failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);
            $approved = false;
        }

        if (! $approved) {
            Log::warning('Signup OTP verify rejected', [
                'phone' => $normalized,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $normalized,
            // Keep password non-null for legacy/email fallback internals.
            'password' => Hash::make(Str::random(32)),
            'pin_hash' => $data['pin'],
        ]);
        $user->phone_verified_at = now();

        $days = (int) config('plans.trial_days', 7);
        $user->plan_type = User::PLAN_FREE_TRIAL;
        $user->trial_started_at = now();
        $user->trial_ends_at = now()->addDays($days);
        $user->save();

        SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => $user->name,
            'phone' => $normalized,
            'public_slug' => SellerProfile::generateUniqueSlug($user->name),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget(['register_phone_pending_phone', 'register_phone_pending_country']);

        Log::info('Phone signup success', [
            'user_id' => $user->id,
            'phone' => $normalized,
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (! $digits || strlen($digits) < 4) {
            return $phone;
        }

        return '+***'.substr($digits, -4);
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
