<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminOtpCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminOtpAuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'pendingEmail' => session('admin_otp_email'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($validated['email']));
        if (! $this->isAllowedEmail($email)) {
            return back()->withErrors([
                'email' => 'This email is not allowed for admin access.',
            ])->withInput();
        }

        $otp = (string) random_int(100000, 999999);
        $cacheKey = $this->cacheKey($email);

        Cache::put($cacheKey, Hash::make($otp), now()->addMinutes((int) config('admin.otp_ttl_minutes', 10)));
        try {
            Mail::to($email)->send(new AdminOtpCode($otp));
        } catch (\Throwable $exception) {
            Log::error('Admin OTP mail send failed', [
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Unable to send OTP email. Check SMTP sender domain/mailbox settings.',
            ])->withInput();
        }

        session(['admin_otp_email' => $email]);

        return back()->with('status', 'otp-sent');
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
        ]);

        $email = strtolower(trim($validated['email']));
        if (! $this->isAllowedEmail($email)) {
            return back()->withErrors([
                'email' => 'This email is not allowed for admin access.',
            ])->withInput();
        }

        $cacheKey = $this->cacheKey($email);
        $hash = Cache::get($cacheKey);
        if (! $hash || ! Hash::check($validated['otp'], $hash)) {
            return back()->withErrors([
                'otp' => 'Invalid or expired OTP. Please request a new code.',
            ])->withInput();
        }

        Cache::forget($cacheKey);
        session()->forget('admin_otp_email');

        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists) {
            $user->name = Str::headline(Str::before($email, '@'));
            $user->password = Hash::make(Str::random(40));
        }
        $user->is_admin = true;
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    private function isAllowedEmail(string $email): bool
    {
        $allowed = array_map('strtolower', config('admin.allowed_emails', []));

        return in_array($email, $allowed, true);
    }

    private function cacheKey(string $email): string
    {
        return 'admin_otp:'.sha1($email);
    }
}
