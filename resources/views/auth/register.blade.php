<x-guest-layout>
    <div class="flex items-center justify-between">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white font-semibold">8K</span>
            <span class="text-xs uppercase tracking-[0.4em] text-slate-400">8Kommerce</span>
        </a>
        <a href="{{ route('login') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Sign in</a>
    </div>

    <h1 class="mt-6 text-2xl font-semibold text-slate-900">Create your 8Kommerce account</h1>
    <p class="mt-2 text-sm text-slate-500">Set up your seller workspace in minutes. Add an email or phone number.</p>

    <form class="mt-6 space-y-5" method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <label for="name" class="text-xs uppercase tracking-[0.3em] text-slate-400">Full name</label>
            <input id="name" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Your name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="text-xs uppercase tracking-[0.3em] text-slate-400">Email address (optional)</label>
            <input id="email" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@company.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Phone number (optional)</label>
            <div class="mt-2 flex gap-2">
                <select name="phone_country" class="rounded-xl border-slate-200 bg-white/80 px-3 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="+233" @selected(old('phone_country', '+233') === '+233')>+233</option>
                </select>
                <input name="phone_number" value="{{ old('phone_number') }}" class="w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0541900229" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" data-strip-leading-zero="true" />
            </div>
            <p class="mt-2 text-xs text-slate-500">Used for SMS OTP sign in.</p>
            <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="text-xs uppercase tracking-[0.3em] text-slate-400">Password</label>
            <input id="password" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" name="password" required autocomplete="new-password" placeholder="Create a password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="password_confirmation" class="text-xs uppercase tracking-[0.3em] text-slate-400">Confirm password</label>
            <input id="password_confirmation" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat your password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
            {{ __('Create account') }}
        </button>
    </form>

    <p class="mt-6 text-sm text-slate-500">
        Already have an account? <a class="font-semibold text-emerald-600 hover:text-emerald-500" href="{{ route('login') }}">Sign in</a>.
    </p>
</x-guest-layout>
