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

    <div class="mt-6 inline-flex rounded-full border border-slate-200 bg-white/80 p-1 text-sm">
        <button type="button" class="login-tab rounded-full px-4 py-2 font-semibold text-emerald-700" data-target="login-email">Email</button>
        <button type="button" class="login-tab rounded-full px-4 py-2 font-semibold text-slate-500" data-target="login-whatsapp">WhatsApp</button>
    </div>

    <div id="login-email" class="mt-6">
        <form class="space-y-5" method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <label for="email" class="text-xs uppercase tracking-[0.3em] text-slate-400">Email address</label>
                <input id="email" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@company.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="text-xs uppercase tracking-[0.3em] text-slate-400">Password</label>
                <div class="relative mt-2">
                    <input id="password" class="w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 pr-12 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" name="password" required autocomplete="current-password" placeholder="Your secure password" />
                    <button
                        type="button"
                        id="toggle-password"
                        class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:text-slate-600"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3C5.5 3 2 7 1 10c1 3 4.5 7 9 7s8-4 9-7c-1-3-4.5-7-9-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Z" />
                            <path d="M10 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z" />
                        </svg>
                        <svg id="icon-eye-off" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3.28 2.22 2.22 3.28l2.1 2.1C2.67 6.74 1.5 8.55 1 10c1 3 4.5 7 9 7 1.83 0 3.47-.6 4.85-1.52l1.87 1.87 1.06-1.06L3.28 2.22ZM10 14a4 4 0 0 1-3.99-3.68l1.67 1.67A2 2 0 0 0 10 12a1.99 1.99 0 0 0 .01-.28l2.27 2.27c-.68.32-1.45.5-2.28.5Zm6.71-1.29-1.5-1.5A9.8 9.8 0 0 0 19 10c-1-3-4.5-7-9-7-1.4 0-2.67.32-3.8.88l1.61 1.61A8.2 8.2 0 0 1 10 5c3.13 0 5.68 2.43 6.71 5.71Z" />
                        </svg>
                    </button>
                </div>
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
    </div>

    <div id="login-whatsapp" class="mt-6 hidden">
        @if (session('otp_status') === 'sent')
            <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                OTP sent to your WhatsApp number.
            </div>
        @endif

        <form class="space-y-4" method="POST" action="{{ route('login.phone.send') }}">
            @csrf
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">WhatsApp number</label>
                <div class="mt-2 flex gap-2">
                    <select name="phone_country" class="rounded-xl border-slate-200 bg-white/80 px-3 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="+233">+233</option>
                    </select>
                    <input name="phone_number" value="{{ old('phone_number') }}" class="w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0541900229" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" data-strip-leading-zero="true" />
                </div>
                <p class="mt-2 text-xs text-slate-500">We will send an OTP to your WhatsApp number.</p>
                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
            </div>
            <button type="submit" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                Send OTP
            </button>
        </form>

        <form class="mt-4 space-y-4" method="POST" action="{{ route('login.phone.verify') }}">
            @csrf
            <input type="hidden" name="phone_country" value="{{ old('phone_country', '+233') }}" />
            <input type="hidden" name="phone_number" value="{{ old('phone_number') }}" />
            <div>
                <label for="otp" class="text-xs uppercase tracking-[0.3em] text-slate-400">OTP</label>
                <input id="otp" name="otp" value="{{ old('otp') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="123456" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>
            <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                Verify & sign in
            </button>
        </form>
    </div>

    <p class="mt-6 text-sm text-slate-500">
        New to LinkPay? <a class="font-semibold text-emerald-600 hover:text-emerald-500" href="{{ route('register') }}">Create an account</a>.
    </p>

    <script>
        const tabButtons = document.querySelectorAll('.login-tab');
        const panels = {
            'login-email': document.getElementById('login-email'),
            'login-whatsapp': document.getElementById('login-whatsapp'),
        };

        const setActiveTab = (target) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.target === target;
                button.classList.toggle('text-emerald-700', isActive);
                button.classList.toggle('text-slate-500', !isActive);
                button.classList.toggle('bg-emerald-50', isActive);
            });
            Object.entries(panels).forEach(([key, panel]) => {
                if (! panel) {
                    return;
                }
                panel.classList.toggle('hidden', key !== target);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => setActiveTab(button.dataset.target));
        });

        const defaultTab = @json((session('otp_status') || $errors->has('phone_number') || $errors->has('otp')) ? 'login-whatsapp' : 'login-email');
        setActiveTab(defaultTab);

        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('toggle-password');
        const eyeIcon = document.getElementById('icon-eye');
        const eyeOffIcon = document.getElementById('icon-eye-off');

        if (passwordInput && togglePassword && eyeIcon && eyeOffIcon) {
            togglePassword.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                togglePassword.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                togglePassword.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                eyeIcon.classList.toggle('hidden', isHidden);
                eyeOffIcon.classList.toggle('hidden', !isHidden);
            });
        }
    </script>
</x-guest-layout>
