<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\TwilioVerifyService;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PhoneOtpController extends Controller
{
    public function send(Request $request, TwilioVerifyService $twilio): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'phone_country' => ['nullable', 'string'],
        ]);

        $country = $data['phone_country'] ?? '+233';
        $normalized = Phone::normalize($data['phone_number'], $country);
        if (! $normalized || ! Phone::isValidGh($data['phone_number'])) {
            throw ValidationException::withMessages([
                'phone_number' => 'Enter a valid WhatsApp number.',
            ]);
        }

        $user = User::where('phone', $normalized)->first();
        if (! $user) {
            $profile = SellerProfile::where('phone', $normalized)->first();
            $user = $profile?->user;
            if ($user) {
                $user->phone = $normalized;
                $user->phone_verified_at = null;
                $user->save();
            }
        }

        if (! $user) {
            throw ValidationException::withMessages([
                'phone_number' => 'Account not found. Use email to sign in.',
            ]);
        }

        try {
            $ok = $twilio->sendOtp($normalized, 'whatsapp');
            if (! $ok) {
                throw new \RuntimeException('OTP delivery failed.');
            }
        } catch (\Throwable $exception) {
            Log::error('Twilio OTP send failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'phone_number' => 'Unable to send OTP. Please try again shortly.',
            ]);
        }

        return back()->with('otp_status', 'sent')->withInput();
    }

    public function verify(Request $request, TwilioVerifyService $twilio): RedirectResponse
    {
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
            'phone_country' => ['nullable', 'string'],
            'otp' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $country = $data['phone_country'] ?? '+233';
        $normalized = Phone::normalize($data['phone_number'], $country);
        if (! $normalized || ! Phone::isValidGh($data['phone_number'])) {
            throw ValidationException::withMessages([
                'phone_number' => 'Enter a valid WhatsApp number.',
            ]);
        }

        $user = User::where('phone', $normalized)->first();
        if (! $user) {
            throw ValidationException::withMessages([
                'phone_number' => 'Account not found. Use email to sign in.',
            ]);
        }

        $twilioApproved = false;
        try {
            $twilioApproved = $twilio->checkOtp($normalized, $data['otp']);
        } catch (\Throwable $exception) {
            Log::error('Twilio OTP verify failed', [
                'phone' => $normalized,
                'message' => $exception->getMessage(),
            ]);
            $twilioApproved = false;
        }
        if (! $twilioApproved) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired OTP.',
            ]);
        }

        $user->phone_verified_at = $user->phone_verified_at ?? now();
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
