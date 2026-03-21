@php
    $title = 'Profile';
    $selectedCountry = old('phone_country', '+233');
    $displayPhone = old('phone_number');
    if (! $displayPhone && $profile->phone && str_starts_with($profile->phone, '+233')) {
        $displayPhone = '0'.substr($profile->phone, 4);
    }
    $payoutMethod = old('payout_method', $profile->payout_method ?? 'MOMO');
    $momoBanks = $momoBanks ?? [];
    $bankOptions = $banks ?? [];
    $momoBankOptions = ! empty($momoBanks) ? $momoBanks : $bankOptions;
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Seller profile</h2>
                <p class="mt-1 text-sm text-slate-600">Business details and payout setup.</p>
                <div class="mt-4">
                    @if($profile->paystack_subaccount_code)
                        <span class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700">Paystack Connected</span>
                    @else
                        <span class="rounded-full bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">Paystack Not Connected</span>
                    @endif
                </div>

                @if($errors->has('paystack'))
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                        {{ $errors->first('paystack') }}
                    </div>
                @endif
                @if(session('paystack_status'))
                    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ session('paystack_status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('profile.seller.update') }}" class="mt-6 space-y-5">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="text-sm font-medium text-slate-700">Business name</label>
                        <input name="business_name" value="{{ old('business_name', $profile->business_name) }}" required class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('business_name') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-sm font-medium text-slate-700">Phone</label>
                        <div class="mt-2 flex gap-2">
                            <select name="phone_country" class="w-28 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="+233" {{ $selectedCountry === '+233' ? 'selected' : '' }}>+233</option>
                            </select>
                            <input name="phone_number" value="{{ $displayPhone }}" placeholder="0541900229" class="flex-1 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" data-strip-leading-zero="true" />
                        </div>
                        <p class="mt-2 text-xs text-slate-500">We remove the leading 0 and save 9 digits.</p>
                        @error('phone_number') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                        @error('phone_country') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Paystack payout</p>
                        <p class="mt-2 text-xs text-slate-500">Fill in to connect your Paystack subaccount.</p>
                        <div class="mt-4 inline-flex overflow-hidden rounded-full border border-slate-200 bg-white text-xs font-semibold">
                            <label class="cursor-pointer px-4 py-2 {{ $payoutMethod === 'MOMO' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600' }}">
                                <input type="radio" name="payout_method" value="MOMO" class="hidden" {{ $payoutMethod === 'MOMO' ? 'checked' : '' }}>
                                Mobile Money
                            </label>
                            <label class="cursor-pointer px-4 py-2 {{ $payoutMethod === 'BANK' ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600' }}">
                                <input type="radio" name="payout_method" value="BANK" class="hidden" {{ $payoutMethod === 'BANK' ? 'checked' : '' }}>
                                Bank
                            </label>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label id="settlement-label" class="text-sm font-medium text-slate-700">MoMo provider</label>
                                @if(! empty($bankOptions))
                                    <select name="settlement_bank_code" id="settlement-bank-code" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="">Select provider</option>
                                        @foreach($momoBankOptions as $bank)
                                            @php
                                                $bankNameLower = strtolower($bank['name']);
                                                $network = 'UNKNOWN';
                                                if (str_contains($bankNameLower, 'mtn')) {
                                                    $network = 'MTN';
                                                } elseif (str_contains($bankNameLower, 'airtel') || str_contains($bankNameLower, 'tigo')) {
                                                    $network = 'AIRTELTIGO';
                                                } elseif (str_contains($bankNameLower, 'telecel') || str_contains($bankNameLower, 'vodafone')) {
                                                    $network = 'TELECEL';
                                                }
                                            @endphp
                                            <option
                                                value="{{ $bank['code'] }}"
                                                data-network="{{ $network }}"
                                                data-type="momo"
                                                {{ old('settlement_bank_code', $profile->settlement_bank_code) === $bank['code'] ? 'selected' : '' }}
                                            >
                                                {{ $bank['name'] }} ({{ $bank['code'] }})
                                            </option>
                                        @endforeach
                                        @if(! empty($bankOptions))
                                            @foreach($bankOptions as $bank)
                                                <option
                                                    value="{{ $bank['code'] }}"
                                                    data-network="UNKNOWN"
                                                    data-type="bank"
                                                    @if($payoutMethod === 'BANK') style="display:block" @else style="display:none" @endif
                                                    {{ old('settlement_bank_code', $profile->settlement_bank_code) === $bank['code'] && ! collect($momoBankOptions)->contains(fn($b) => $b['code'] === $bank['code']) ? 'selected' : '' }}
                                                >
                                                    {{ $bank['name'] }} ({{ $bank['code'] }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @endif
                                @else
                                    <input name="settlement_bank_code" value="{{ old('settlement_bank_code', $profile->settlement_bank_code) }}" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                                    <p class="mt-2 text-xs text-slate-500">Bank list unavailable. Enter the bank code manually.</p>
                                @endif
                                @error('settlement_bank_code') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label id="account-number-label" class="text-sm font-medium text-slate-700">Wallet number</label>
                                <input id="account-number" name="account_number" value="{{ old('account_number', $profile->account_number) }}" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" placeholder="e.g. 0541900229" />
                                <p id="momo-network-hint" class="mt-2 hidden text-xs text-slate-500"></p>
                                @error('account_number') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-sm font-medium text-slate-700">Account name</label>
                                <input name="account_name" value="{{ old('account_name', $profile->account_name) }}" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                                @error('account_name') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                        Save profile
                    </button>
                </form>
                <form method="POST" action="{{ route('profile.seller.test') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="rounded-full border border-emerald-200 px-5 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                        Test Paystack connection
                    </button>
                </form>
                <div class="mt-6 rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-500">Public listing</p>
                    <a href="{{ route('public.listing', $profile->public_slug) }}" class="mt-2 block break-all text-sm font-semibold text-emerald-700 hover:text-emerald-600">
                        {{ route('public.listing', $profile->public_slug) }}
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Account details</h2>
                <div class="mt-4">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Security</h2>
                <div class="mt-4">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
            <div class="rounded-2xl border border-rose-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-rose-600">Danger zone</h2>
                <div class="mt-4">
                    @include('profile.partials.data-deletion-request-form')
                </div>
                <div class="mt-6">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const accountInput = document.getElementById('account-number');
            const hint = document.getElementById('momo-network-hint');
            const bankSelect = document.querySelector('select[name="settlement_bank_code"]');
            const payoutRadios = document.querySelectorAll('input[name="payout_method"]');
            const settlementLabel = document.getElementById('settlement-label');
            const accountLabel = document.getElementById('account-number-label');

            const detectGhNetwork = (value) => {
                const digits = (value || '').replace(/\D+/g, '');
                if (!digits) return null;

                let local = digits;
                if (local.startsWith('233') && local.length >= 12) {
                    local = local.slice(3);
                }

                let prefix = null;
                if (local.length === 10 && local.startsWith('0')) {
                    prefix = local.slice(0, 3);
                } else if (local.length === 9) {
                    prefix = '0' + local.slice(0, 2);
                }
                if (!prefix) return null;

                const mtn = new Set(['024','025','053','054','055','059']);
                const telecel = new Set(['020','050']);
                const airteltigo = new Set(['026','027','056','057']);

                if (mtn.has(prefix)) return 'MTN MoMo';
                if (telecel.has(prefix)) return 'Telecel Cash';
                if (airteltigo.has(prefix)) return 'AirtelTigo Money';
                return null;
            };

            const getPayoutMethod = () => {
                const picked = Array.from(payoutRadios).find((r) => r.checked);
                return picked ? picked.value : 'MOMO';
            };

            const setPayoutMethod = (value) => {
                Array.from(payoutRadios).forEach((r) => { r.checked = (r.value === value); });
                updateLabels();
            };

            const updateLabels = () => {
                const method = getPayoutMethod();
                if (settlementLabel) settlementLabel.textContent = method === 'BANK' ? 'Bank' : 'MoMo provider';
                if (accountLabel) accountLabel.textContent = method === 'BANK' ? 'Account number' : 'Wallet number';
                if (accountInput) accountInput.placeholder = method === 'BANK' ? 'e.g. bank account number' : 'e.g. 0541900229';
                if (bankSelect) {
                    const options = Array.from(bankSelect.options);
                    options.forEach((option, index) => {
                        if (index === 0) return;
                        const type = option.dataset.type || 'bank';
                        const visible = method === 'BANK' ? true : type === 'momo';
                        option.style.display = visible ? 'block' : 'none';
                    });
                }
            };

            const maybeAutoSelectBank = (networkLabel) => {
                if (!bankSelect || !networkLabel) return;

                const options = Array.from(bankSelect.options);
                const normalizedNetwork = networkLabel.toUpperCase().includes('MTN')
                    ? 'MTN'
                    : (networkLabel.toUpperCase().includes('AIRTEL') || networkLabel.toUpperCase().includes('TIGO')
                        ? 'AIRTELTIGO'
                        : (networkLabel.toUpperCase().includes('TELECEL') || networkLabel.toUpperCase().includes('VODAFONE') ? 'TELECEL' : 'UNKNOWN'));

                const best = options.find((o) => o.dataset.network === normalizedNetwork && o.style.display !== 'none');
                if (best) {
                    bankSelect.value = best.value;
                }
            };

            const refresh = () => {
                if (!accountInput || !hint) return;
                const network = detectGhNetwork(accountInput.value);

                // MoMo-first: if we cannot detect a MoMo prefix, switch to BANK mode (so bank becomes primary).
                if (!network && getPayoutMethod() === 'MOMO') {
                    const digits = (accountInput.value || '').replace(/\D+/g, '');
                    if (digits.length >= 9) {
                        setPayoutMethod('BANK');
                    }
                }

                if (!network || getPayoutMethod() === 'BANK') {
                    hint.textContent = '';
                    hint.classList.add('hidden');
                    return;
                }

                hint.textContent = 'Detected MoMo network: ' + network;
                hint.classList.remove('hidden');
                maybeAutoSelectBank(network);
            };

            if (accountInput) {
                accountInput.addEventListener('input', refresh);
                Array.from(payoutRadios).forEach((r) => r.addEventListener('change', () => {
                    updateLabels();
                    refresh();
                }));
                updateLabels();
                refresh();
            }
        });
    </script>
@endsection
