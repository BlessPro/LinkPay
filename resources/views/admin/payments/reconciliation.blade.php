@php
    $title = 'Payment Reconciliation';
    $statusCounts = $report['statusCounts'];
@endphp
@extends('layouts.admin')

@section('content')
    @if(session('status') === 'manual-confirm-success')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Payment manually marked as success.
        </div>
    @endif
    @if(session('status') === 'manual-mark-failed-success')
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Payment manually marked as failed.
        </div>
    @endif
    @if(session('status') === 'retry-success')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Payment verification retried successfully.
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('admin.payments.reconciliation') }}" class="grid gap-3 md:grid-cols-[180px_1fr_auto] md:items-end">
            <div>
                <label for="days" class="mb-1 block text-xs uppercase tracking-[0.2em] text-slate-400">Window</label>
                <select id="days" name="days" class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-400">
                    @foreach([1, 3, 7, 14, 30] as $window)
                        <option value="{{ $window }}" @selected((int) $report['days'] === $window)>{{ $window }} day{{ $window === 1 ? '' : 's' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="seller_id" class="mb-1 block text-xs uppercase tracking-[0.2em] text-slate-400">Seller</label>
                <select id="seller_id" name="seller_id" class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-400">
                    <option value="">All sellers</option>
                    @foreach($sellers as $seller)
                        <option value="{{ $seller->id }}" @selected((int) ($report['sellerId'] ?? 0) === (int) $seller->id)>
                            {{ $seller->sellerProfile?->business_name ?? $seller->name }} ({{ $seller->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Run report</button>
        </form>
        <div class="mt-3">
            <a
                href="{{ route('admin.payments.reconciliation.export', ['days' => $report['days'], 'seller_id' => $report['sellerId']]) }}"
                class="inline-flex rounded-full border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-slate-300"
            >
                Export exceptions CSV
            </a>
        </div>
        <p class="mt-3 text-xs text-slate-500">
            Period: {{ $report['from']->toFormattedDateString() }} - {{ $report['to']->toFormattedDateString() }}.
            Local: {{ number_format($report['localTotal']) }} | Paystack: {{ number_format($report['paystackTotal']) }}
        </p>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Matched</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-900">{{ $statusCounts['matched'] }}</p>
        </div>
        <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-rose-700">Missing in DB</p>
            <p class="mt-2 text-2xl font-semibold text-rose-900">{{ $statusCounts['missing_in_db'] }}</p>
        </div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-700">Missing in Paystack</p>
            <p class="mt-2 text-2xl font-semibold text-amber-900">{{ $statusCounts['missing_in_paystack'] }}</p>
        </div>
        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-indigo-700">Amount mismatch</p>
            <p class="mt-2 text-2xl font-semibold text-indigo-900">{{ $statusCounts['amount_mismatch'] }}</p>
        </div>
        <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-sky-700">Status mismatch</p>
            <p class="mt-2 text-2xl font-semibold text-sky-900">{{ $statusCounts['status_mismatch'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Duplicates</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $statusCounts['duplicate_reference'] }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Exceptions</h2>
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Ordered by latest</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Type</th>
                        <th class="px-3 py-3">Reference</th>
                        <th class="px-3 py-3">Seller</th>
                        <th class="px-3 py-3">Local</th>
                        <th class="px-3 py-3">Paystack</th>
                        <th class="px-3 py-3">Message</th>
                        <th class="px-3 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['exceptions'] as $row)
                        <tr>
                            <td class="px-3 py-3">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', $row['type']) }}</span>
                            </td>
                            <td class="px-3 py-3 font-semibold text-slate-900">{{ $row['reference'] }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $row['seller_name'] ?? '-' }}</td>
                            <td class="px-3 py-3 text-slate-600">
                                {{ $row['local_status'] ?? '-' }}
                                @if($row['local_amount'])
                                    <div class="text-xs text-slate-400">{{ \App\Support\Money::format((string) $row['local_amount'], $currency) }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                {{ $row['paystack_status'] ?? '-' }}
                                @if($row['paystack_amount'])
                                    <div class="text-xs text-slate-400">{{ \App\Support\Money::format((string) $row['paystack_amount'], $currency) }}</div>
                                @endif
                                @if(!empty($row['is_aged']))
                                    <div class="mt-1 inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700">Aged {{ $row['age_hours'] }}h</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $row['message'] }}</td>
                            <td class="px-3 py-3">
                                @if($row['payment_id'])
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.payments.retry', $row['payment_id']) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-slate-300">Retry verify</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.payments.confirm', $row['payment_id']) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Mark success</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.payments.mark-failed', $row['payment_id']) }}">
                                            @csrf
                                            <button type="submit" class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100">Mark failed</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">No local payment</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">No exceptions found in selected window.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
