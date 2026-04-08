@php
    $title = 'Payments';
    $transactionFilters = [
        'all' => 'All',
        'new' => 'New',
        'success' => 'Successful',
        'pending' => 'Pending',
        'failed' => 'Failed',
        'refund_requested' => 'Refund requested',
    ];
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Payments</p>
                <h2 class="mt-1 text-xl font-semibold text-slate-900 sm:text-2xl">{{ \App\Support\Money::format($totalReceived, $currency) }}</h2>
                <p class="text-sm text-slate-500">Revenue so far</p>
            </div>
            <a href="{{ route('payments.export') }}" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700" title="Export">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M4.5 15.75v2.25A2.25 2.25 0 0 0 6.75 20.25h10.5A2.25 2.25 0 0 0 19.5 18v-2.25"/></svg>
            </a>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Last 30 days</p>
                <p class="mt-2 text-lg font-semibold text-slate-900 sm:text-xl">{{ \App\Support\Money::format($last30DaysReceived, $currency) }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Pending</p>
                <p class="mt-2 text-lg font-semibold text-slate-900 sm:text-xl">{{ $pendingCount }}</p>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            <button type="button" class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800" onclick="document.getElementById('transactions-list')?.scrollIntoView({behavior: 'smooth', block: 'start'});">
                Refund
            </button>
            <a href="{{ route('payments.export') }}" class="rounded-xl bg-slate-950 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-slate-800">
                Export
            </a>
            <a href="{{ route('invoices.create') }}" class="col-span-2 rounded-xl border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700 sm:col-span-1">
                New invoice
            </a>
        </div>

        @if($errors->has('payment'))
            <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                {{ $errors->first('payment') }}
            </div>
        @endif
        @if(session('status') === 'refund-requested')
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Refund request submitted. We will process it shortly.
            </div>
        @endif

        <div id="transactions-list" class="mt-6 rounded-2xl border border-slate-100 bg-slate-50/60 p-4">
            <details class="group" {{ $activeFilter !== 'all' ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Transactions</h3>
                    <span class="inline-flex items-center gap-2 text-xs font-medium text-slate-500">
                        {{ $transactionFilters[$activeFilter] ?? 'All' }}
                        <svg class="h-4 w-4 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/></svg>
                    </span>
                </summary>

                <div class="mt-3 flex gap-2 overflow-x-auto pb-1">
                    @foreach($transactionFilters as $filterKey => $label)
                        <a
                            href="{{ route('payments.index', ['filter' => $filterKey]) }}"
                            class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-semibold {{ $activeFilter === $filterKey ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600' }}"
                        >
                            {{ $label }} <span class="ml-1 text-[11px] text-slate-400">{{ $transactionFilterCounts[$filterKey] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
            </details>

            <div class="mt-4 space-y-2">
                @forelse($payments as $payment)
                    @php
                        $isRefundRequested = (bool) data_get($payment->raw_payload, 'refund_requested', false);
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $payment->created_at->format('d M Y, h:i A') }}</p>
                            </div>
                            <div class="text-right">
                                <span class="rounded-full px-2 py-1 text-[11px] font-semibold
                                    {{ $payment->status === \App\Models\Payment::STATUS_SUCCESS ? 'bg-emerald-50 text-emerald-700' : '' }}
                                    {{ $payment->status === \App\Models\Payment::STATUS_PENDING ? 'bg-amber-50 text-amber-700' : '' }}
                                    {{ $payment->status === \App\Models\Payment::STATUS_FAILED ? 'bg-rose-50 text-rose-700' : '' }}
                                ">{{ $payment->status }}</span>
                                @if($isRefundRequested)
                                    <p class="mt-1 text-[11px] font-semibold text-fuchsia-600">REFUND REQUESTED</p>
                                @endif
                            </div>
                        </div>
                        <p class="mt-2 break-all text-[11px] text-slate-500">Ref: {{ $payment->reference }}</p>
                        <div class="mt-3 flex items-center gap-2 overflow-x-auto pb-1">
                            @if($payment->status === \App\Models\Payment::STATUS_SUCCESS && ! $isRefundRequested)
                                <form method="POST" action="{{ route('payments.refund.request', $payment) }}">
                                    @csrf
                                    <button type="submit" class="rounded-full border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-50">
                                        Request refund
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('payments.index', ['filter' => $activeFilter]) }}" class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                Details
                            </a>
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-500">No transactions found for this filter.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-5">
            {{ $payments->links() }}
        </div>
    </div>
@endsection

