<x-guest-layout>
    <div class="flex items-center justify-between">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 font-semibold text-white">8K</span>
            <span class="text-xs uppercase tracking-[0.4em] text-slate-400">8Kommerce</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Logout</button>
        </form>
    </div>

    <h1 class="mt-6 text-2xl font-semibold text-slate-900">Set your PIN</h1>
    <p class="mt-2 text-sm text-slate-500">Create your 4-digit PIN to continue.</p>

    <x-auth-session-status class="mt-6 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700" :status="session('status')" />

    <form class="mt-6 space-y-5" method="POST" action="{{ route('pin.setup.store') }}">
        @csrf

        <div>
            <label for="pin" class="text-xs uppercase tracking-[0.3em] text-slate-400">4-digit PIN</label>
            <input id="pin" name="pin" value="{{ old('pin') }}" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" placeholder="1234" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('pin')" class="mt-2" />
        </div>

        <div>
            <label for="pin_confirmation" class="text-xs uppercase tracking-[0.3em] text-slate-400">Confirm PIN</label>
            <input id="pin_confirmation" name="pin_confirmation" class="mt-2 w-full rounded-xl border-slate-200 bg-white/80 px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" type="password" placeholder="1234" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="new-password" />
        </div>

        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
            Save PIN
        </button>
    </form>
</x-guest-layout>

