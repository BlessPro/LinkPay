<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', '8Kommerce') }} Admin Login</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        <div class="mx-auto flex min-h-screen w-full max-w-md items-center px-4">
            <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h1 class="text-xl font-semibold">Admin access</h1>
                <p class="mt-1 text-sm text-slate-500">Enter admin email and OTP code.</p>

                @if(session('status') === 'otp-sent')
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        OTP sent to your email.
                    </div>
                @endif
                @if(session('status') === 'otp-debug')
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        Email send failed. Debug OTP: <span class="font-semibold">{{ session('otp_preview') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.send') }}" class="mt-5 space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs uppercase tracking-[0.2em] text-slate-500">Email</label>
                        <input type="email" name="email" value="{{ old('email', $pendingEmail) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('email')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Send OTP</button>
                </form>

                <form method="POST" action="{{ route('admin.login.verify') }}" class="mt-4 space-y-3 border-t border-slate-200 pt-4">
                    @csrf
                    <div>
                        <label class="text-xs uppercase tracking-[0.2em] text-slate-500">Email</label>
                        <input type="email" name="email" value="{{ old('email', $pendingEmail) }}" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-[0.2em] text-slate-500">OTP</label>
                        <input type="text" name="otp" inputmode="numeric" maxlength="6" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">
                        @error('otp')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Verify OTP</button>
                </form>
            </div>
        </div>
    </body>
</html>
