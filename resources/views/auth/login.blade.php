<x-guest-layout>
    @php($phoneAuthEnabled = (bool) config('auth_phone.enabled', true))
    <div class="flex items-center justify-between">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white">8K</span>
            <span class="text-xs uppercase tracking-[0.4em] text-slate-400">8Kommerce</span>
        </a>
        <a href="{{ route('register') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Create account</a>
    </div>

    <h1 class="mt-6 text-2xl font-semibold text-slate-900">Welcome back</h1>
    <p class="mt-2 text-sm text-slate-500">Sign in with either Email or PIN.</p>

    <x-auth-session-status class="mt-6 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <div class="mt-6 inline-flex rounded-full border border-slate-200 bg-white/80 p-1 text-sm">
        <button type="button" class="login-tab rounded-full px-4 py-2 font-semibold text-emerald-700 bg-emerald-50" data-target="login-email">Email</button>
        <button type="button" class="login-tab rounded-full px-4 py-2 font-semibold text-slate-500" data-target="login-pin">PIN</button>
    </div>

    <div id="login-email" class="mt-6">
        <form class="space-y-5" method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <label for="email" class="text-xs uppercase tracking-[0.3em] text-slate-400">Email address</label>
                <input id="email" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@company.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="pin" class="text-xs uppercase tracking-[0.3em] text-slate-400">4-digit PIN</label>
                <input id="pin" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" name="pin" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="current-password" placeholder="1234" />
                <x-input-error :messages="$errors->get('pin')" class="mt-2" />
            </div>

            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                Sign in with Email
            </button>
        </form>
    </div>

    <div id="login-pin" class="mt-6 hidden">
        <form class="space-y-4" method="POST" action="{{ route('login.phone.pin') }}">
            @csrf
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Phone number</label>
                <div class="mt-2 flex gap-2">
                    <select name="phone_country" class="rounded-xl border-slate-200 bg-white/80 px-3 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="+233">+233</option>
                    </select>
                    <input name="phone_number" value="{{ old('phone_number', session('phone_login_pending_phone')) }}" class="w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0541900229" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" data-strip-leading-zero="true" />
                </div>
                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
            </div>

            <div>
                <label for="pin-phone" class="text-xs uppercase tracking-[0.3em] text-slate-400">4-digit PIN</label>
                <input id="pin-phone" name="pin" value="{{ old('pin') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" placeholder="1234" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('pin')" class="mt-2" />
            </div>

            <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                Sign in with PIN
            </button>
        </form>
    </div>

    @if($phoneAuthEnabled)
        <div class="mt-6 border-t border-slate-200 pt-5">
            <button type="button" id="toggle-pin-reset" class="text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                Forgot PIN?
            </button>
            <p class="mt-1 text-xs text-slate-500">Use OTP to reset your PIN.</p>
        </div>

        <div id="pin-reset-panel" class="mt-4 hidden">
            @if (session('pin_reset_status') === 'sent')
                <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    PIN reset OTP sent to {{ session('pin_reset_phone_masked', 'your phone number') }}.
                </div>
            @endif

            <form class="space-y-4" method="POST" action="{{ route('login.phone.pin.reset.send') }}">
                @csrf
                <div>
                    <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Business phone number</label>
                    <div class="mt-2 flex gap-2">
                        <select name="phone_country" class="rounded-xl border-slate-200 bg-white/80 px-3 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="+233">+233</option>
                        </select>
                        <input name="phone_number" value="{{ old('phone_number', session('pin_reset_pending_phone')) }}" class="w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0541900229" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" data-strip-leading-zero="true" />
                    </div>
                    <x-input-error :messages="$errors->get('reset_phone_number')" class="mt-2" />
                </div>
                <button type="submit" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                    Send reset OTP
                </button>
            </form>

            <form class="mt-4 space-y-4" method="POST" action="{{ route('login.phone.pin.reset.complete') }}">
                @csrf
                <input type="hidden" name="phone_country" value="{{ old('phone_country', session('pin_reset_pending_country', '+233')) }}" />
                <div>
                    <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Business phone number</label>
                    <input name="phone_number" value="{{ old('phone_number', session('pin_reset_pending_phone')) }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0541900229" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" data-strip-leading-zero="true" />
                    <x-input-error :messages="$errors->get('reset_phone_number')" class="mt-2" />
                </div>
                <div>
                    <label for="reset_otp" class="text-xs uppercase tracking-[0.3em] text-slate-400">OTP</label>
                    <input id="reset_otp" name="otp" value="{{ old('otp') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="123456" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" />
                    <x-input-error :messages="$errors->get('reset_otp')" class="mt-2" />
                </div>
                <div>
                    <label for="reset_pin" class="text-xs uppercase tracking-[0.3em] text-slate-400">New 4-digit PIN</label>
                    <input id="reset_pin" name="reset_pin" value="{{ old('reset_pin') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" placeholder="1234" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('reset_pin')" class="mt-2" />
                </div>
                <div>
                    <label for="reset_pin_confirmation" class="text-xs uppercase tracking-[0.3em] text-slate-400">Confirm new PIN</label>
                    <input id="reset_pin_confirmation" name="reset_pin_confirmation" value="{{ old('reset_pin_confirmation') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" placeholder="1234" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" />
                </div>
                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                    Verify OTP and reset PIN
                </button>
            </form>
        </div>
    @endif

    <p class="mt-6 text-sm text-slate-500">
        New to 8Kommerce? <a class="font-semibold text-emerald-600 hover:text-emerald-500" href="{{ route('register') }}">Create an account</a>.
    </p>

    <script>
        const tabButtons = document.querySelectorAll('.login-tab');
        const panels = {
            'login-email': document.getElementById('login-email'),
            'login-pin': document.getElementById('login-pin'),
        };

        const setActiveTab = (target) => {
            tabButtons.forEach((button) => {
                const isActive = button.dataset.target === target;
                button.classList.toggle('text-emerald-700', isActive);
                button.classList.toggle('bg-emerald-50', isActive);
                button.classList.toggle('text-slate-500', !isActive);
            });
            Object.entries(panels).forEach(([key, panel]) => {
                if (!panel) return;
                panel.classList.toggle('hidden', key !== target);
            });
        };

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => setActiveTab(button.dataset.target));
        });

        const pinTabErrors = @json($errors->has('phone_number'));
        setActiveTab(pinTabErrors ? 'login-pin' : 'login-email');

        const togglePinReset = document.getElementById('toggle-pin-reset');
        const pinResetPanel = document.getElementById('pin-reset-panel');
        const shouldOpenReset = @json($phoneAuthEnabled && (session('pin_reset_status') || $errors->has('reset_phone_number') || $errors->has('reset_otp') || $errors->has('reset_pin')));

        if (togglePinReset && pinResetPanel) {
            if (shouldOpenReset) {
                pinResetPanel.classList.remove('hidden');
            }

            togglePinReset.addEventListener('click', () => {
                pinResetPanel.classList.toggle('hidden');
            });
        }
    </script>
</x-guest-layout>
