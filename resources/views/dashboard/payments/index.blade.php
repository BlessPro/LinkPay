@php($title = 'Payments')
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
        <div class="mt-4 space-y-3">
            @forelse($payments as $payment)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                        <p class="text-xs text-slate-500">Reference: {{ $payment->reference }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $payment->status }}</span>
                        <span class="text-xs text-slate-500">{{ $payment->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No payments yet.</p>
            @endforelse
        </div>
        <div class="mt-6">
            {{ $payments->links() }}
        </div>
    </div>
@endsection
