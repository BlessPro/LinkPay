<x-guest-layout>
    <div class="flex items-center justify-between">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white font-semibold">LP</span>
            <span class="text-xs uppercase tracking-[0.4em] text-slate-400">LinkPay</span>
        </a>
        <a href="{{ route('register') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Create account</a>
    </div>

    <h1 class="mt-6 text-2xl font-semibold text-slate-900">Welcome back</h1>
    <p class="mt-2 text-sm text-slate-500">Log in to manage your listings, invoices, and payouts.</p>

    <x-auth-session-status class="mt-6 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form class="mt-6 space-y-5" method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <label for="email" class="text-xs uppercase tracking-[0.3em] text-slate-400">Email address</label>
            <input id="email" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@company.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="text-xs uppercase tracking-[0.3em] text-slate-400">Password</label>
            <input id="password" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" name="password" required autocomplete="current-password" placeholder="Your secure password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between text-sm">
            <label for="remember_me" class="inline-flex items-center gap-2 text-slate-600">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a class="font-medium text-emerald-600 hover:text-emerald-500" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
            {{ __('Log in') }}
        </button>
    </form>

    <p class="mt-6 text-sm text-slate-500">
        New to LinkPay? <a class="font-semibold text-emerald-600 hover:text-emerald-500" href="{{ route('register') }}">Create an account</a>.
    </p>
</x-guest-layout>
