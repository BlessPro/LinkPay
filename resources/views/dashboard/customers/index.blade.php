@php
    $title = 'Customers';
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-slate-900">Customer Overview</h2>
        <p class="mt-1 text-sm text-slate-500">Track customer value, retention, and activity from real transactions.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total Customers</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $overview['totalCustomers'] }}</p>
                <p class="text-xs text-emerald-700">+{{ $overview['newThisMonth'] }} this month</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Returning Customers</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $overview['returningCustomers'] }}</p>
                <p class="text-xs text-slate-600">{{ number_format($overview['returnRate'], 1) }}% return rate</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Average Customer Value</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format((string) $overview['avgCustomerValue'], $currency) }}</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Orders per Customer</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($overview['ordersPerCustomer'], 1) }}</p>
            </div>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Customer List</h3>
                <p class="text-sm text-slate-500">Search and filter customers by behavior.</p>
            </div>
            <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Filter</label>
                    <select name="filter" class="mt-1 rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="all" @selected($filter === 'all')>All Customers</option>
                        <option value="new" @selected($filter === 'new')>New Customers</option>
                        <option value="returning" @selected($filter === 'returning')>Returning Customers</option>
                        <option value="inactive" @selected($filter === 'inactive')>Inactive Customers</option>
                        <option value="top_spenders" @selected($filter === 'top_spenders')>Top Spenders</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs uppercase tracking-[0.2em] text-slate-400">Search</label>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Name, phone, or email" class="mt-1 w-56 rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <button type="submit" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Apply</button>
            </form>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2">Orders</th>
                        <th class="px-3 py-2">Total Spent</th>
                        <th class="px-3 py-2">Last Order</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">
                                <a href="{{ route('customers.show', $customer['encoded_key']) }}" class="font-semibold text-slate-900 hover:text-emerald-700">{{ $customer['name'] }}</a>
                                <div class="text-xs text-slate-500">{{ $customer['phone'] ?: ($customer['email'] ?: '-') }}</div>
                            </td>
                            <td class="px-3 py-2 text-slate-600">{{ $customer['orders_count'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ \App\Support\Money::format((string) $customer['total_spent'], $currency) }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $customer['last_purchase_at']?->diffForHumans() ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $customer['status'] === 'Loyal' ? 'bg-emerald-50 text-emerald-700' : ($customer['status'] === 'Inactive' ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700') }}">{{ $customer['status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-5 text-center text-slate-500">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $customers->links() }}</div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Customer Value Segmentation</h3>
            <div class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-slate-600">VIP (&gt; {{ \App\Support\Money::format('500', $currency) }})</span><span class="font-semibold text-slate-900">{{ $segments['vip'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Regular (2-4 orders)</span><span class="font-semibold text-slate-900">{{ $segments['regular'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">New (1 order)</span><span class="font-semibold text-slate-900">{{ $segments['new'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Inactive (30+ days)</span><span class="font-semibold text-slate-900">{{ $segments['inactive'] }}</span></div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-lg font-semibold text-slate-900">Top Customers</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2">Orders</th>
                            <th class="px-3 py-2">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topCustomers as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['orders_count'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ \App\Support\Money::format((string) $row['total_spent'], $currency) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-5 text-center text-slate-500">No customer spend data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Customer Growth</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Month</th>
                            <th class="px-3 py-2">New Customers</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($growth as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['month'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Customer Retention</h3>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-600">Total Customers</span><span class="font-semibold text-slate-900">{{ $retention['total'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Returning Customers</span><span class="font-semibold text-slate-900">{{ $retention['returning'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">Retention Rate</span><span class="font-semibold text-emerald-700">{{ number_format($retention['rate'], 1) }}%</span></div>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Recently Active Customers</h3>
            <div class="mt-4 space-y-3 text-sm">
                @forelse($recentlyActive as $row)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                        <span class="font-medium text-slate-700">{{ $row['name'] }}</span>
                        <span class="text-slate-500">{{ $row['last_activity_at']?->diffForHumans() ?? '-' }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">No activity yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Customer Location</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">City</th>
                            <th class="px-3 py-2">Customers</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($locations as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['city'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-3 py-5 text-center text-slate-500">No location data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
