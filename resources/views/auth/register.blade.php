<x-guest-layout>
    @php($phoneAuthEnabled = (bool) config('auth_phone.enabled', true))
    <div class="flex items-center justify-between">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white font-semibold">8K</span>
            <span class="text-xs uppercase tracking-[0.4em] text-slate-400">8Kommerce</span>
        </a>
        <a href="{{ route('login') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Sign in</a>
    </div>

    <h1 class="mt-6 text-2xl font-semibold text-slate-900">Create your 8Kommerce account</h1>
    <p class="mt-2 text-sm text-slate-500">
        @if($phoneAuthEnabled)
            Phone signup is faster. Email signup is still available.
        @else
            Create your account with email. Phone signup is currently disabled.
        @endif
    </p>

    @if($phoneAuthEnabled)
        <div class="mt-6 inline-flex rounded-full border border-slate-200 bg-white/80 p-1 text-sm">
            <button type="button" class="register-tab rounded-full px-4 py-2 font-semibold text-emerald-700" data-target="register-phone">Phone</button>
            <button type="button" class="register-tab rounded-full px-4 py-2 font-semibold text-slate-500" data-target="register-email">Email</button>
        </div>
    @endif

    <div id="register-phone" class="mt-6 {{ $phoneAuthEnabled ? '' : 'hidden' }}">
        @if (session('register_otp_status') === 'sent')
            <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                OTP sent to {{ session('register_otp_phone_masked', 'your phone number') }}.
            </div>
        @endif

        <form class="space-y-4" method="POST" action="{{ route('register.phone.send') }}">
            @csrf
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Phone number</label>
                <div class="mt-2 flex gap-2">
                    <select name="phone_country" class="rounded-xl border-slate-200 bg-white/80 px-3 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="+233" @selected(old('phone_country', session('register_phone_pending_country', '+233')) === '+233')>+233</option>
                    </select>
                    <input name="phone_number" value="{{ old('phone_number', session('register_phone_pending_phone')) }}" class="w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="0541900229" inputmode="numeric" pattern="[0-9]*" autocomplete="tel" data-strip-leading-zero="true" />
                </div>
                <p class="mt-2 text-xs text-slate-500">We will send an OTP to this number.</p>
                <x-input-error :messages="$errors->get('phone_number')" class="mt-2" />
            </div>
            <button type="submit" class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                Send OTP
            </button>
        </form>

        <form class="mt-4 space-y-4" method="POST" action="{{ route('register.phone.complete') }}">
            @csrf
            <input type="hidden" name="phone_country" value="{{ old('phone_country', session('register_phone_pending_country', '+233')) }}" />
            <input type="hidden" name="phone_number" value="{{ old('phone_number', session('register_phone_pending_phone')) }}" />

            <div>
                <label for="otp" class="text-xs uppercase tracking-[0.3em] text-slate-400">OTP</label>
                <input id="otp" name="otp" value="{{ old('otp') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="123456" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" />
                <x-input-error :messages="$errors->get('otp')" class="mt-2" />
            </div>

            <div>
                <label for="name_phone" class="text-xs uppercase tracking-[0.3em] text-slate-400">Full name</label>
                <input id="name_phone" name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Your name" autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="email_phone" class="text-xs uppercase tracking-[0.3em] text-slate-400">Business email (optional)</label>
                <input id="email_phone" name="email" value="{{ old('email') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="you@company.com" autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="pin" class="text-xs uppercase tracking-[0.3em] text-slate-400">4-digit PIN</label>
                <input id="pin" name="pin" type="password" value="{{ old('pin') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="1234" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" />
                <x-input-error :messages="$errors->get('pin')" class="mt-2" />
            </div>

            <div>
                <label for="pin_confirmation" class="text-xs uppercase tracking-[0.3em] text-slate-400">Confirm PIN</label>
                <input id="pin_confirmation" name="pin_confirmation" type="password" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="1234" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" />
            </div>

            <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                Create account
            </button>
        </form>
    </div>

    <div id="register-email" class="mt-6 {{ $phoneAuthEnabled ? 'hidden' : '' }}">
        <form class="space-y-5" method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <label for="name_email" class="text-xs uppercase tracking-[0.3em] text-slate-400">Full name</label>
                <input id="name_email" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="text" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="Your name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="email" class="text-xs uppercase tracking-[0.3em] text-slate-400">Email address</label>
                <input id="email" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@company.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="text-xs uppercase tracking-[0.3em] text-slate-400">Password</label>
                <input id="password" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" name="password" autocomplete="new-password" placeholder="Create a password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="text-xs uppercase tracking-[0.3em] text-slate-400">Confirm password</label>
                <input id="password_confirmation" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat your password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                Create with email
            </button>
        </form>
    </div>

    <p class="mt-6 text-sm text-slate-500">
        Already have an account? <a class="font-semibold text-emerald-600 hover:text-emerald-500" href="{{ route('login') }}">Sign in</a>.
    </p>

    <script>
        const registerButtons = document.querySelectorAll('.register-tab');
        const registerPanels = {
            'register-phone': document.getElementById('register-phone'),
            'register-email': document.getElementById('register-email'),
        };

        const setActiveRegisterTab = (target) => {
            registerButtons.forEach((button) => {
                const isActive = button.dataset.target === target;
                button.classList.toggle('text-emerald-700', isActive);
                button.classList.toggle('text-slate-500', !isActive);
                button.classList.toggle('bg-emerald-50', isActive);
            });
            Object.entries(registerPanels).forEach(([key, panel]) => {
                if (!panel) return;
                panel.classList.toggle('hidden', key !== target);
            });
        };

        registerButtons.forEach((button) => {
            button.addEventListener('click', () => setActiveRegisterTab(button.dataset.target));
        });

        const phoneAuthEnabled = @json($phoneAuthEnabled);
        const phoneErrors = @json($errors->has('phone_number') || $errors->has('otp') || $errors->has('pin') || session('register_otp_status'));
        const emailErrors = @json($errors->has('password') || $errors->has('password_confirmation') || $errors->has('email'));
        const defaultRegisterTab = !phoneAuthEnabled ? 'register-email' : (phoneErrors ? 'register-phone' : (emailErrors ? 'register-email' : 'register-phone'));
        setActiveRegisterTab(defaultRegisterTab);
    </script>
</x-guest-layout>
