@php
    $title = 'Customer Profile';
@endphp
@extends('layouts.dashboard')

@section('content')
    <a href="{{ route('customers.index') }}" class="inline-flex items-center rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
        Back to Customers
    </a>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">{{ $customer['name'] }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 text-sm">
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Phone</p><p class="mt-1 text-slate-700">{{ $customer['phone'] ?: '-' }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Email</p><p class="mt-1 text-slate-700">{{ $customer['email'] ?: '-' }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Location</p><p class="mt-1 text-slate-700">{{ $customer['location'] ?: '-' }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">First Purchase</p><p class="mt-1 text-slate-700">{{ $customer['first_purchase_at']?->format('M d, Y') ?: '-' }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Last Purchase</p><p class="mt-1 text-slate-700">{{ $customer['last_purchase_at']?->format('M d, Y') ?: '-' }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total Orders</p><p class="mt-1 text-slate-700">{{ $customer['orders_count'] }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total Spent</p><p class="mt-1 text-slate-700">{{ \App\Support\Money::format((string) $customer['total_spent'], $currency) }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Avg Order Value</p><p class="mt-1 text-slate-700">{{ \App\Support\Money::format((string) $customer['avg_order_value'], $currency) }}</p></div>
            <div><p class="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p><p class="mt-1 text-slate-700">{{ $customer['status'] }}</p></div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-slate-900">Purchase History</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Amount</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($customer['purchase_history'] as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['date']->format('M d, Y') }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['product'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ \App\Support\Money::format((string) $row['amount'], $currency) }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['status'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-5 text-center text-slate-500">No purchases yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Favorite Products</h3>
            <div class="mt-4 space-y-3 text-sm">
                @forelse($customer['favorite_products'] as $product)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2">
                        <span class="text-slate-700">{{ $product['name'] }}</span>
                        <span class="font-semibold text-slate-900">{{ $product['count'] }} purchase{{ $product['count'] === 1 ? '' : 's' }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">No favorite products yet.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
