@php
    $title = 'Billing';
    $planCards = [
        ['code' => \App\Models\User::PLAN_STARTER, 'cfg' => $plans['starter'] ?? []],
        ['code' => \App\Models\User::PLAN_GROWTH, 'cfg' => $plans['growth'] ?? []],
        ['code' => \App\Models\User::PLAN_ENTERPRISE, 'cfg' => $plans['enterprise'] ?? []],
    ];
@endphp

@extends('layouts.dashboard')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Billing</h2>
            <p class="mt-1 text-sm text-slate-600">Manage your subscription plan.</p>
        </div>
        <a href="{{ route('pricing') }}" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
            View pricing
        </a>
    </div>

    @if(session('status') === 'plan-activated')
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800">
            Plan activated.
        </div>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Current status</p>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Plan</span>
                    <span class="font-semibold text-slate-900">{{ $user->planDisplayName() }}</span>
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
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Switch plan</h3>
                <span class="text-xs font-semibold text-slate-500">Monthly subscription</span>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-3">
                @foreach($planCards as $plan)
                    @php
                        $cfg = $plan['cfg'];
                    @endphp
                    <div class="rounded-2xl border {{ $plan['code'] === \App\Models\User::PLAN_GROWTH ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' }} p-5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">{{ $cfg['name'] ?? $plan['code'] }}</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $cfg['price_text'] ?? '' }}</p>
                        </div>
                        <ul class="mt-3 space-y-1 text-xs text-slate-600">
                            <li>Products: {{ data_get($cfg, 'limits.products', '--') }}</li>
                            <li>Orders: {{ data_get($cfg, 'limits.orders', '--') }}</li>
                            <li>Team: {{ data_get($cfg, 'limits.team_members', '--') }}</li>
                            <li>Fee: {{ data_get($cfg, 'transaction_fee', '--') }}</li>
                        </ul>
                        <form method="POST" action="{{ route('billing.activate', ['plan' => $plan['code']]) }}" class="mt-4">
                            @csrf
                            <button class="w-full rounded-full {{ $plan['code'] === \App\Models\User::PLAN_GROWTH ? 'bg-emerald-600 text-white hover:bg-emerald-500' : 'border border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700' }} px-4 py-2 text-xs font-semibold">
                                Activate {{ $cfg['name'] ?? $plan['code'] }}
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection

