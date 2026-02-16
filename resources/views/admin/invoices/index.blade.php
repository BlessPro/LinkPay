@php
    $title = 'Invoices';
@endphp
@extends('layouts.admin')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Invoices</h2>
                <p class="text-sm text-slate-500">Search by token, reference, title, customer, or seller email.</p>
            </div>
            <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex w-full max-w-md items-center gap-2">
                <input
                    type="text"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search invoices..."
                    class="w-full rounded-xl border-slate-200 px-4 py-2 text-sm focus:border-slate-500 focus:ring-slate-400"
                />
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                    Search
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Invoice</th>
                        <th class="px-3 py-3">Seller</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Total</th>
                        <th class="px-3 py-3">Paid</th>
                        <th class="px-3 py-3">Balance</th>
                        <th class="px-3 py-3">Created</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $invoice)
                        <tr>
                            <td class="px-3 py-3">
                                <p class="font-semibold text-slate-900">{{ $invoice->title }}</p>
                                <p class="text-xs text-slate-500">Token: {{ $invoice->token }}</p>
                            </td>
                            <td class="px-3 py-3 text-slate-600">
                                <p class="text-slate-900 font-medium">{{ $invoice->user?->sellerProfile?->business_name ?? $invoice->user?->name }}</p>
                                <p class="text-xs text-slate-500">{{ $invoice->user?->email }}</p>
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $invoice->status }}</span>
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ \App\Support\Money::format($invoice->total_amount, $currency) }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ \App\Support\Money::format($invoice->paid_total, $currency) }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ \App\Support\Money::format($invoice->balanceRemaining(), $currency) }}</td>
                            <td class="px-3 py-3 text-slate-500">{{ $invoice->created_at->format('M d, Y') }}</td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="text-xs font-semibold text-slate-900 hover:text-slate-600">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $invoices->links() }}
        </div>
    </div>
@endsection
