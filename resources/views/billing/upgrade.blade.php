@php
    $title = 'Upgrade';
@endphp

@extends('layouts.dashboard')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Upgrade required</h2>
            <p class="mt-1 text-sm text-slate-600">Your trial has ended or you do not have an active plan.</p>
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
                        Active plan: {{ $user->plan_type }}
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

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Promotion</h3>
                <span class="text-sm font-semibold text-slate-700">{{ $plans['promotion']['price_range'] ?? 'GHS 60-100 / month' }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-600">Public pages + products + analytics-lite. Payments disabled.</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                <li>Unlimited products/services</li>
                <li>WhatsApp chat CTA</li>
                <li>Views + clicks</li>
            </ul>
            <form method="POST" action="{{ route('billing.activate', ['plan' => 'PROMOTION']) }}" class="mt-6">
                @csrf
                <button class="w-full rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    Activate Promotion (MVP)
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-slate-900">Payments</h3>
                <span class="text-sm font-semibold text-slate-700">{{ $plans['payments']['price_range'] ?? 'GHS 30-50 / month' }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-600">Everything enabled. Includes invoices + payment records.</p>
            <p class="mt-2 text-xs text-slate-500">{{ $plans['payments']['commission_text'] ?? '1% per successful payment' }}</p>
            <ul class="mt-4 space-y-2 text-sm text-slate-700">
                <li>Pay Now buttons</li>
                <li>Invoice links + partial payments</li>
                <li>Payment records + notifications</li>
            </ul>
            <form method="POST" action="{{ route('billing.activate', ['plan' => 'PAYMENTS']) }}" class="mt-6">
                @csrf
                <button class="w-full rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Activate Payments (MVP)
                </button>
            </form>
        </div>
    </div>
@endsection

