@php
    $title = 'Billing';
@endphp

@extends('layouts.dashboard')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Billing</h2>
            <p class="mt-1 text-sm text-slate-600">Manage your plan. MVP uses simulated activation.</p>
        </div>
        <a href="{{ route('pricing') }}" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
            View pricing
        </a>
    </div>

    @if(session('status') === 'plan-activated')
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            Plan activated (MVP simulation).
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Current status</p>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Plan</span>
                    <span class="font-semibold text-slate-900">{{ $user->plan_type ?: 'None' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Trial</span>
                    <span class="font-semibold text-slate-900">
                        @if($user->isOnTrial())
                            Active (ends {{ $user->trial_ends_at?->toFormattedDateString() }})
                        @elseif($user->trialExpired())
                            Expired
                        @else
                            --
                        @endif
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Plan ends</span>
                    <span class="font-semibold text-slate-900">{{ $user->plan_ends_at?->toFormattedDateString() ?: '--' }}</span>
                </div>
            </div>

            @if(! $user->hasActiveAccess())
                <a href="{{ route('billing.upgrade') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Upgrade now
                </a>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Switch plan</h3>
                <span class="text-xs font-semibold text-slate-500">MVP simulation</span>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">Promotion</p>
                        <p class="text-sm font-semibold text-slate-700">{{ $plans['promotion']['price_range'] ?? 'GHS 60-100 / month' }}</p>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">Public page + products. Payments disabled.</p>
                    <form method="POST" action="{{ route('billing.activate', ['plan' => 'PROMOTION']) }}" class="mt-4">
                        @csrf
                        <button class="w-full rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                            Activate Promotion
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">Payments</p>
                        <p class="text-sm font-semibold text-slate-700">{{ $plans['payments']['price_range'] ?? 'GHS 30-50 / month' }}</p>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">Payments + invoices + records.</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $plans['payments']['commission_text'] ?? '1% per successful payment' }}</p>
                    <form method="POST" action="{{ route('billing.activate', ['plan' => 'PAYMENTS']) }}" class="mt-4">
                        @csrf
                        <button class="w-full rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                            Activate Payments
                        </button>
                    </form>
                </div>
            </div>

            <p class="mt-6 text-xs text-slate-500">
                Next: connect Paystack subscriptions here. The plan state is already persisted in the database.
            </p>
        </div>
    </div>
@endsection

