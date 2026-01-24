@php
    $title = 'System Overview';
    $dailyMaxRevenue = max(1.0, (float) (collect($dailySeries)->map(fn ($row) => (float) $row['revenue'])->max() ?? 0));
    $dailyMaxCount = max(1, (int) (collect($dailySeries)->map(fn ($row) => (int) $row['count'])->max() ?? 0));
    $weeklyMaxRevenue = max(1.0, (float) (collect($weeklySeries)->map(fn ($row) => (float) $row['revenue'])->max() ?? 0));
    $weeklyMaxCount = max(1, (int) (collect($weeklySeries)->map(fn ($row) => (int) $row['count'])->max() ?? 0));
    $monthlyMaxRevenue = max(1.0, (float) (collect($monthlySeries)->map(fn ($row) => (float) $row['revenue'])->max() ?? 0));
    $monthlyMaxCount = max(1, (int) (collect($monthlySeries)->map(fn ($row) => (int) $row['count'])->max() ?? 0));
@endphp
@extends('layouts.admin')

@section('content')
    <div class="grid gap-6 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Total revenue</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">
                {{ \App\Support\Money::format($totalReceived, $currency) }}
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Platform fees</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">
                {{ \App\Support\Money::format($platformFeesTotal, $currency) }}
            </p>
            <p class="mt-1 text-xs text-slate-500">Est. flat fee total</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Payments</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $totalPayments }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Total sellers</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $totalSellers }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Connected sellers</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $connectedSellers }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-600">Pending invoices</p>
            <p class="mt-3 text-2xl font-semibold text-amber-800">{{ $pendingInvoices }}</p>
        </div>
        <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-sky-600">Partial invoices</p>
            <p class="mt-3 text-2xl font-semibold text-sky-800">{{ $partialInvoices }}</p>
        </div>
        <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-rose-600">Failed payments</p>
            <p class="mt-3 text-2xl font-semibold text-rose-800">{{ $failedPayments }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Last {{ $compare7['label'] }}</p>
            <p class="mt-3 text-lg font-semibold text-slate-900">
                {{ \App\Support\Money::format($compare7['currentRevenue'], $currency) }}
            </p>
            <p class="mt-1 text-sm text-slate-500">
                {{ $compare7['currentCount'] }} payments
            </p>
            <p class="mt-3 text-sm text-slate-600">
                Revenue change: {{ number_format($compare7['revenueChange'], 1) }}%
            </p>
            <p class="text-sm text-slate-600">
                Volume change: {{ number_format($compare7['countChange'], 1) }}%
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Last {{ $compare30['label'] }}</p>
            <p class="mt-3 text-lg font-semibold text-slate-900">
                {{ \App\Support\Money::format($compare30['currentRevenue'], $currency) }}
            </p>
            <p class="mt-1 text-sm text-slate-500">
                {{ $compare30['currentCount'] }} payments
            </p>
            <p class="mt-3 text-sm text-slate-600">
                Revenue change: {{ number_format($compare30['revenueChange'], 1) }}%
            </p>
            <p class="text-sm text-slate-600">
                Volume change: {{ number_format($compare30['countChange'], 1) }}%
            </p>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Recent payments</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Success only</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentPayments as $payment)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->user?->email }}</p>
                        </div>
                        <span class="text-xs text-slate-500">{{ $payment->created_at->format('M d, Y') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Pending payments</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Force verify</span>
            </div>
            @if($errors->has('payment'))
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                    {{ $errors->first('payment') }}
                </div>
            @endif
            @if(session('status') === 'payment-verified')
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    Payment verified successfully.
                </div>
            @endif
            <div class="mt-4 space-y-3">
                @forelse($pendingPayments as $payment)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                            <p class="text-xs text-slate-500">{{ $payment->reference }}</p>
                        </div>
                        <form method="POST" action="{{ route('payments.verify', $payment) }}">
                            @csrf
                            <button type="submit" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-slate-300">
                                Verify
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No pending payments.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Daily revenue</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Last 14 days</span>
            </div>
            <div class="mt-4 flex h-28 items-end gap-1">
                @foreach($dailySeries as $point)
                    @php
                        $height = $dailyMaxRevenue > 0 ? (((float) $point['revenue'] / $dailyMaxRevenue) * 100) : 0;
                    @endphp
                    <div class="flex-1">
                        <div class="h-24 rounded-t-lg bg-emerald-400/80" style="height: {{ max(6, $height) }}%" title="{{ $point['label'] }}: {{ \App\Support\Money::format($point['revenue'], $currency) }}"></div>
                        <p class="mt-1 text-[10px] text-slate-400 text-center">{{ $point['label'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex h-20 items-end gap-1">
                @foreach($dailySeries as $point)
                    @php
                        $height = $dailyMaxCount > 0 ? (((int) $point['count'] / $dailyMaxCount) * 100) : 0;
                    @endphp
                    <div class="flex-1">
                        <div class="h-16 rounded-t-lg bg-slate-300" style="height: {{ max(6, $height) }}%" title="{{ $point['label'] }}: {{ $point['count'] }} payments"></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Weekly revenue</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Last 8 weeks</span>
            </div>
            <div class="mt-4 flex h-28 items-end gap-2">
                @foreach($weeklySeries as $point)
                    @php
                        $height = $weeklyMaxRevenue > 0 ? (((float) $point['revenue'] / $weeklyMaxRevenue) * 100) : 0;
                    @endphp
                    <div class="flex-1">
                        <div class="h-24 rounded-t-lg bg-emerald-400/80" style="height: {{ max(6, $height) }}%" title="{{ $point['label'] }}: {{ \App\Support\Money::format($point['revenue'], $currency) }}"></div>
                        <p class="mt-1 text-[10px] text-slate-400 text-center">{{ $point['label'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex h-20 items-end gap-2">
                @foreach($weeklySeries as $point)
                    @php
                        $height = $weeklyMaxCount > 0 ? (((int) $point['count'] / $weeklyMaxCount) * 100) : 0;
                    @endphp
                    <div class="flex-1">
                        <div class="h-16 rounded-t-lg bg-slate-300" style="height: {{ max(6, $height) }}%" title="{{ $point['label'] }}: {{ $point['count'] }} payments"></div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Monthly revenue</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Last 6 months</span>
            </div>
            <div class="mt-4 flex h-28 items-end gap-2">
                @foreach($monthlySeries as $point)
                    @php
                        $height = $monthlyMaxRevenue > 0 ? (((float) $point['revenue'] / $monthlyMaxRevenue) * 100) : 0;
                    @endphp
                    <div class="flex-1">
                        <div class="h-24 rounded-t-lg bg-emerald-400/80" style="height: {{ max(6, $height) }}%" title="{{ $point['label'] }}: {{ \App\Support\Money::format($point['revenue'], $currency) }}"></div>
                        <p class="mt-1 text-[10px] text-slate-400 text-center">{{ $point['label'] }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex h-20 items-end gap-2">
                @foreach($monthlySeries as $point)
                    @php
                        $height = $monthlyMaxCount > 0 ? (((int) $point['count'] / $monthlyMaxCount) * 100) : 0;
                    @endphp
                    <div class="flex-1">
                        <div class="h-16 rounded-t-lg bg-slate-300" style="height: {{ max(6, $height) }}%" title="{{ $point['label'] }}: {{ $point['count'] }} payments"></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Recent invoices</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Latest</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentInvoices as $invoice)
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $invoice->title }}</p>
                            <p class="text-xs text-slate-500">{{ $invoice->user?->email }}</p>
                        </div>
                        <span class="text-xs text-slate-500">{{ $invoice->created_at->format('M d, Y') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No invoices yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Sellers</h2>
            <span class="text-xs uppercase tracking-[0.3em] text-slate-400">System users</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Business</th>
                        <th class="px-3 py-3">Email</th>
                        <th class="px-3 py-3">Phone</th>
                        <th class="px-3 py-3">Products</th>
                        <th class="px-3 py-3">Invoices</th>
                        <th class="px-3 py-3">Total received</th>
                        <th class="px-3 py-3">Paystack</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sellers as $seller)
                        <tr>
                            <td class="px-3 py-3 text-slate-900 font-semibold">{{ $seller->sellerProfile?->business_name ?? $seller->name }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $seller->email }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $seller->sellerProfile?->phone ?? '-' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $seller->products_count }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $seller->invoices_count }}</td>
                            <td class="px-3 py-3 text-slate-600">
                                {{ \App\Support\Money::format((string) ($seller->total_received ?? '0.00'), $currency) }}
                            </td>
                            <td class="px-3 py-3">
                                @if($seller->sellerProfile?->paystack_subaccount_code)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Connected</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Not connected</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-sm text-slate-500">No sellers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">
            {{ $sellers->links() }}
        </div>
    </div>
@endsection
