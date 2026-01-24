@php($title = 'Dashboard')
@extends('layouts.dashboard')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-emerald-100 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-emerald-500">Total received</p>
            <p class="mt-4 text-3xl font-semibold text-slate-900">
                {{ \App\Support\Money::format($totalReceived, $currency) }}
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Invoices</p>
            <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $invoiceCount }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Products</p>
            <p class="mt-4 text-3xl font-semibold text-slate-900">{{ $productCount }}</p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Recent invoices</h2>
                <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentInvoices as $invoice)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $invoice->title }}</p>
                            <p class="text-xs text-slate-500">Status: {{ $invoice->status }}</p>
                        </div>
                        <a href="{{ route('invoices.show', $invoice) }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Open</a>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No invoices yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Recent payments</h2>
                <a href="{{ route('payments.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentPayments as $payment)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->reference }}</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $payment->status }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
