@php($title = 'Invoice')
@extends('layouts.dashboard')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Invoice</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $invoice->title }}</h2>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $invoice->status }}</span>
            </div>
            <p class="mt-4 text-sm text-slate-600">{{ $invoice->description }}</p>
            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Total</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($invoice->total_amount, $currency) }}</p>
                </div>
                <div class="rounded-xl border border-slate-100 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Paid</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($invoice->paid_total, $currency) }}</p>
                </div>
            </div>
            <div class="mt-6 rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                <p class="text-sm text-emerald-700">Shareable invoice link:</p>
                <a href="{{ route('public.invoice', $invoice->token) }}" class="mt-2 block break-all text-sm font-semibold text-emerald-700 hover:text-emerald-600">
                    {{ route('public.invoice', $invoice->token) }}
                </a>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Payments</h3>
            <div class="mt-4 space-y-3">
                @forelse($invoice->payments as $payment)
                    <div class="rounded-xl border border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Ref: {{ $payment->reference }}</p>
                        <p class="mt-1 text-xs text-slate-500">Status: {{ $payment->status }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments yet.</p>
                @endforelse
            </div>
            <div class="mt-6 rounded-xl border border-slate-100 bg-slate-50/70 px-4 py-3">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Invoice insights (30 days)</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                        <p class="text-xs text-slate-400">Views</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $stats['views'] }} ({{ $stats['viewsUnique'] }} unique)</p>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                        <p class="text-xs text-slate-400">Clicks</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $stats['clicks'] }} ({{ $stats['clicksUnique'] }} unique)</p>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                        <p class="text-xs text-slate-400">Payments</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $stats['payments'] }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                        <p class="text-xs text-slate-400">Conversion</p>
                        <p class="text-sm font-semibold text-slate-900">{{ number_format($stats['conversion'], 1) }}%</p>
                    </div>
                </div>
                <p class="mt-3 text-sm text-slate-600">Total paid: {{ \App\Support\Money::format($stats['paymentTotal'], $currency) }}</p>
            </div>
        </div>
    </div>
@endsection
