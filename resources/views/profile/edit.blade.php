@php($title = 'Profile')
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
                        <input name="phone" value="{{ old('phone', $profile->phone) }}" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                        @error('phone') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Paystack payout</p>
                        <p class="mt-2 text-xs text-slate-500">Fill in to connect your Paystack subaccount.</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium text-slate-700">Settlement bank code</label>
                                <input name="settlement_bank_code" value="{{ old('settlement_bank_code', $profile->settlement_bank_code) }}" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                                @error('settlement_bank_code') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="text-sm font-medium text-slate-700">Account number</label>
                                <input name="account_number" value="{{ old('account_number', $profile->account_number) }}" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
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
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
@endsection
