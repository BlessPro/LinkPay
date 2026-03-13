@php
    $title = 'Goals and Target';
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Business Performance</p>
        <h2 class="mt-2 text-xl font-semibold text-slate-900">Linkpay Goals & Targets</h2>
        <p class="mt-2 text-sm text-slate-500">
            Period: {{ $monthStart->format('M d, Y') }} - {{ $monthEnd->format('M d, Y') }}.
            Targets are auto-derived from your actual performance history.
        </p>
    </div>

    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-emerald-900">Business Growth Summary</h3>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Revenue Growth</p>
                <p class="mt-2 text-xl font-semibold text-emerald-900">{{ $businessSummary['revenueGrowth'] >= 0 ? '+' : '' }}{{ number_format($businessSummary['revenueGrowth'], 1) }}%</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Orders Growth</p>
                <p class="mt-2 text-xl font-semibold text-emerald-900">{{ $businessSummary['ordersGrowth'] >= 0 ? '+' : '' }}{{ number_format($businessSummary['ordersGrowth'], 1) }}%</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Top Product</p>
                <p class="mt-2 text-base font-semibold text-emerald-900">{{ $businessSummary['topProduct'] }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Top Day</p>
                <p class="mt-2 text-base font-semibold text-emerald-900">{{ $businessSummary['topDay'] }}</p>
            </div>
        </div>

        <div class="mt-4 grid gap-2 text-sm text-emerald-900">
            @if(($businessSummary['topProductShare'] ?? 0) > 30)
                <p>Top performer highlight: {{ $businessSummary['topProduct'] }} contributes more than 30% of total revenue.</p>
            @endif
            @if($businessSummary['revenueGrowth'] > 20)
                <p>Strong growth indicator: Revenue growth is above 20% month-over-month.</p>
            @endif
            @if(($businessSummary['topProductShare'] ?? 0) <= 0 && $businessSummary['revenueGrowth'] <= 20)
                <p>Keep pushing traffic and conversion improvements to unlock stronger growth signals.</p>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Monthly Revenue Performance</h3>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">This Month</span><span class="font-semibold text-slate-900">{{ \App\Support\Money::format((string) $thisMonthRevenue, $currency) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Last Month</span><span class="font-semibold text-slate-900">{{ \App\Support\Money::format((string) $lastMonthRevenue, $currency) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Growth</span><span class="font-semibold {{ $revenueGrowth >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $revenueGrowth >= 0 ? '+' : '' }}{{ number_format($revenueGrowth, 1) }}%</span></div>
            </div>
            <div class="mt-4">
                <div class="h-3 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-emerald-500" style="width: {{ max(0, min(100, $revenueProgress)) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($revenueProgress, 1) }}% progress to target {{ \App\Support\Money::format((string) $revenueTarget, $currency) }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Orders Performance</h3>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Orders This Month</span><span class="font-semibold text-slate-900">{{ $ordersThisMonth }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Orders Last Month</span><span class="font-semibold text-slate-900">{{ $ordersLastMonth }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Growth</span><span class="font-semibold {{ $ordersGrowth >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $ordersGrowth >= 0 ? '+' : '' }}{{ number_format($ordersGrowth, 1) }}%</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Target Orders</span><span class="font-semibold text-slate-900">{{ $ordersTarget }}</span></div>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Best Performing Products</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Units</th>
                            <th class="px-3 py-2">Revenue</th>
                            <th class="px-3 py-2">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topProducts as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['units'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ \App\Support\Money::format((string) $row['revenue'], $currency) }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-900">{{ number_format($row['share'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">No product sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(isset($topProducts[0]))
                <p class="mt-3 text-sm text-emerald-700">
                    {{ $topProducts[0]['name'] }} generated {{ number_format($topProducts[0]['share'], 1) }}% of your revenue this month.
                </p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Weak Product Detection</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Views</th>
                            <th class="px-3 py-2">Purchases</th>
                            <th class="px-3 py-2">Conversion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($weakProducts as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['views'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['purchases'] }}</td>
                                <td class="px-3 py-2 font-semibold text-rose-700">{{ number_format($row['conversion'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">No weak products detected under 3% conversion.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Customer Insights</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">New</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $customerInsights['new'] }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Returning</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $customerInsights['returning'] }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Returning Rate</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($customerInsights['returningRate'], 1) }}%</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Best Selling Day</h3>
            <div class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-500">Best Sales Day</span><span class="font-semibold text-emerald-700">{{ $bestSalesDay['day'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Orders</span><span class="font-semibold text-slate-900">{{ $bestSalesDay['count'] }}</span></div>
                <div class="border-t border-slate-100 pt-2"></div>
                <div class="flex justify-between"><span class="text-slate-500">Worst Sales Day</span><span class="font-semibold text-rose-700">{{ $worstSalesDay['day'] }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Orders</span><span class="font-semibold text-slate-900">{{ $worstSalesDay['count'] }}</span></div>
            </div>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Sales Trend (Weekly)</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-2">Week</th>
                        <th class="px-3 py-2">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($weeklySales as $row)
                        <tr>
                            <td class="px-3 py-2 text-slate-700">{{ $row['week'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ \App\Support\Money::format((string) $row['revenue'], $currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Link Performance</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Product Link</th>
                            <th class="px-3 py-2">Clicks</th>
                            <th class="px-3 py-2">Purchases</th>
                            <th class="px-3 py-2">Conversion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($linkPerformance as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['link'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['clicks'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['purchases'] }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-900">{{ number_format($row['conversion'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">No product link clicks yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($businessSummary['bestLink'])
                <p class="mt-3 text-sm text-emerald-700">
                    {{ $businessSummary['bestLinkName'] }} has the highest link conversion at {{ number_format($businessSummary['bestLinkConversion'], 1) }}%.
                </p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Top Traffic Products</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Views</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topTrafficProducts as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['views'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-3 py-4 text-center text-slate-500">No product traffic yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection
