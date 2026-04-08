@php
    $title = 'Upgrade';
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
            <h2 class="text-lg font-semibold text-slate-900">Upgrade required</h2>
            <p class="mt-1 text-sm text-slate-600">Your trial has ended or your plan is inactive.</p>
        </div>
        <a href="{{ route('billing.show') }}" class="rounded-full border border-slate-200 px-5 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
            Manage billing
        </a>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Status</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">
                    @if($user->isOnTrial())
                        Trial active (ends {{ $user->trial_ends_at?->toFormattedDateString() }})
                    @elseif($user->hasActivePlan())
                        Active plan: {{ $user->planDisplayName() }}
                    @else
                        No active plan
                    @endif
                </p>
            </div>
            @if(session('billing_required'))
                <span class="rounded-full bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">
                    {{ session('billing_required') }}
                </span>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        @foreach($planCards as $plan)
            @php
                $cfg = $plan['cfg'];
            @endphp
            <div class="rounded-2xl border {{ $plan['code'] === \App\Models\User::PLAN_GROWTH ? 'border-emerald-200 bg-white' : 'border-slate-200 bg-white' }} p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">{{ $cfg['name'] ?? $plan['code'] }}</h3>
                    <span class="text-sm font-semibold text-slate-700">{{ $cfg['price_text'] ?? '' }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ data_get($cfg, 'best_for', 'Subscription plan') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-700">
                    <li>Products: {{ data_get($cfg, 'limits.products', '--') }}</li>
                    <li>Orders: {{ data_get($cfg, 'limits.orders', '--') }}</li>
                    <li>Team members: {{ data_get($cfg, 'limits.team_members', '--') }}</li>
                    <li>Transaction fee: {{ data_get($cfg, 'transaction_fee', '--') }}</li>
                </ul>
                <form method="POST" action="{{ route('billing.activate', ['plan' => $plan['code']]) }}" class="mt-6">
                    @csrf
                    <button class="w-full rounded-full {{ $plan['code'] === \App\Models\User::PLAN_GROWTH ? 'bg-emerald-600 text-white hover:bg-emerald-500' : 'border border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700' }} px-5 py-3 text-sm font-semibold">
                        Activate {{ $cfg['name'] ?? $plan['code'] }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>
@endsection

