<x-guest-layout>
    @php($phoneAuthEnabled = (bool) config('auth_phone.enabled', true))
    <div class="flex items-center justify-between">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white">8K</span>
            <span class="text-xs uppercase tracking-[0.4em] text-slate-400">8Kommerce</span>
        </a>
        <a href="{{ route('login') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Sign in</a>
    </div>

    <h1 class="mt-6 text-2xl font-semibold text-slate-900">Create your 8Kommerce account</h1>
    <p class="mt-2 text-sm text-slate-500">Sign up with your phone number and OTP.</p>

    @if(!$phoneAuthEnabled)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
            Phone signup is currently unavailable. Please contact support.
        </div>
    @else
        <div class="mt-6">
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

                <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                    Create account
                </button>
            </form>
        </div>
    @endif

    <p class="mt-6 text-sm text-slate-500">
        Already have an account? <a class="font-semibold text-emerald-600 hover:text-emerald-500" href="{{ route('login') }}">Sign in</a>.
    </p>
</x-guest-layout>
