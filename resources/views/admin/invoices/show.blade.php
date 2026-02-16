@php
    $title = 'Invoice Details';
@endphp
@extends('layouts.admin')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Invoice</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $invoice->title }}</h2>
                    <p class="mt-2 text-sm text-slate-600">{{ $invoice->description ?: 'No description provided.' }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700">{{ $invoice->status }}</span>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Total</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($invoice->total_amount, $currency) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Paid</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($invoice->paid_total, $currency) }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Balance</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($invoice->balanceRemaining(), $currency) }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Payment mode</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $invoice->payment_mode }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Deposit</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">
                        {{ $invoice->deposit_amount ? \App\Support\Money::format($invoice->deposit_amount, $currency) : 'N/A' }}
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Public link</p>
                    <a href="{{ $publicUrl }}" target="_blank" class="mt-2 block truncate text-sm font-semibold text-emerald-600 hover:text-emerald-500">
                        {{ $publicUrl }}
                    </a>
                </div>
            </div>

            <div class="mt-8">
                <h3 class="text-sm font-semibold text-slate-900">Payments</h3>
                <div class="mt-4 space-y-3">
                    @forelse($invoice->payments as $payment)
                        <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-100 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                                <p class="text-xs text-slate-500">Ref: {{ $payment->reference }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $payment->status }}</p>
                                <p class="text-xs text-slate-500">{{ $payment->paid_at?->format('M d, Y H:i') ?? $payment->created_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No payments recorded for this invoice.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Seller</p>
                <h3 class="mt-3 text-lg font-semibold text-slate-900">
                    {{ $invoice->user?->sellerProfile?->business_name ?? $invoice->user?->name }}
                </h3>
                <p class="mt-2 text-sm text-slate-500">{{ $invoice->user?->email }}</p>
                <p class="text-sm text-slate-500">{{ $invoice->user?->sellerProfile?->phone ?? 'No phone on file.' }}</p>
                <p class="mt-3 text-xs uppercase tracking-[0.3em] text-slate-400">Paystack</p>
                @if($invoice->user?->sellerProfile?->paystack_subaccount_code)
                    <p class="mt-2 text-sm font-semibold text-emerald-600">Connected</p>
                @else
                    <p class="mt-2 text-sm font-semibold text-amber-600">Not connected</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Engagement</p>
                <div class="mt-4 space-y-3 text-sm text-slate-700">
                    <div class="flex items-center justify-between">
                        <span>Views</span>
                        <span class="font-semibold text-slate-900">{{ $views }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Unique views</span>
                        <span class="font-semibold text-slate-900">{{ $uniqueViews }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Pay clicks</span>
                        <span class="font-semibold text-slate-900">{{ $clicks }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Payments</span>
                        <span class="font-semibold text-slate-900">{{ $paymentCount }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Total paid</span>
                        <span class="font-semibold text-slate-900">{{ \App\Support\Money::format($paymentTotal, $currency) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
