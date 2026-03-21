@php
    $title = 'Insights';
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Insights</h2>
        <form class="mt-4 flex flex-wrap items-end gap-4" method="GET" action="{{ route('insights.index') }}">
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Range</label>
                <select name="range" id="insights-range" class="mt-2 rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                    <option value="7" @selected($range === '7')>Last 7 days</option>
                    <option value="14" @selected($range === '14')>Last 14 days</option>
                    <option value="30" @selected($range === '30')>Last 30 days</option>
                    <option value="90" @selected($range === '90')>Last 90 days</option>
                    <option value="custom" @selected($range === 'custom')>Custom</option>
                </select>
            </div>
            <div id="insights-custom-range" class="{{ $range === 'custom' ? '' : 'hidden' }} flex flex-wrap items-end gap-4">
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Start date</label>
                <input type="date" name="start_date" value="{{ $start->format('Y-m-d') }}" class="mt-2 rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">End date</label>
                <input type="date" name="end_date" value="{{ $end->format('Y-m-d') }}" class="mt-2 rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
            </div>
            <button type="submit" class="rounded-full bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500">
                Apply filter
            </button>
        </form>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Listing views</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $summary['listingViews'] }}</p>
            <p class="text-xs text-slate-500">{{ $summary['listingViewsUnique'] }} unique</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Product impressions</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $summary['productImpressions'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Product clicks</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $summary['productClicks'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Invoice views</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $summary['invoiceViews'] }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Invoice clicks</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $summary['invoiceClicks'] }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Funnel analytics</h3>
            <span class="text-xs uppercase tracking-[0.3em] text-slate-400">View → Paid</span>
        </div>
        <p class="mt-2 text-sm text-slate-600">Overall conversion: <span class="font-semibold text-slate-900">{{ number_format($funnel['overall_conversion'], 1) }}%</span></p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach($funnel['stages'] as $stage)
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ $stage['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($stage['value']) }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-2">Step</th>
                        <th class="px-3 py-2">From</th>
                        <th class="px-3 py-2">To</th>
                        <th class="px-3 py-2">Drop-off</th>
                        <th class="px-3 py-2">Conversion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($funnel['dropoffs'] as $step)
                        <tr>
                            <td class="px-3 py-2 text-slate-700">{{ $step['from'] }} → {{ $step['to'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ number_format($step['from_value']) }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ number_format($step['to_value']) }}</td>
                            <td class="px-3 py-2 text-rose-700">{{ number_format($step['dropoff_pct'], 1) }}%</td>
                            <td class="px-3 py-2 text-emerald-700">{{ number_format($step['conversion_pct'], 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Action hints</h3>
            <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Rule based</span>
        </div>
        <div class="mt-4 space-y-3">
            @foreach($funnelHints as $hint)
                @php
                    $theme = match($hint['level']) {
                        'critical' => 'border-rose-200 bg-rose-50/70 text-rose-900',
                        'warning' => 'border-amber-200 bg-amber-50/70 text-amber-900',
                        'good' => 'border-emerald-200 bg-emerald-50/70 text-emerald-900',
                        default => 'border-slate-200 bg-slate-50 text-slate-900',
                    };
                @endphp
                <div class="rounded-xl border px-4 py-3 {{ $theme }}">
                    <p class="text-sm font-semibold">{{ $hint['title'] }}</p>
                    <p class="mt-1 text-sm">{{ $hint['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-emerald-600">Payments</p>
            <p class="mt-3 text-2xl font-semibold text-emerald-800">{{ $paymentsCount }}</p>
            <p class="text-sm text-emerald-700">{{ \App\Support\Money::format($paymentsTotal, $currency) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Top customers</p>
            <div class="mt-3 space-y-2">
                @forelse($customerInsights as $customer)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $customer['email'] }}</span>
                        <span class="text-slate-900 font-semibold">{{ \App\Support\Money::format($customer['total'], $currency) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No payments yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Devices</p>
            <div class="mt-3 space-y-2">
                @forelse($deviceBreakdown as $device)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ ucfirst($device->device_type ?? 'unknown') }}</span>
                        <span class="font-semibold text-slate-900">{{ $device->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No device data yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Traffic sources</h3>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Referrers</span>
            </div>
            <div class="mt-4 space-y-2">
                @forelse($referrers as $referrer)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">{{ $referrer->referrer_host }}</span>
                        <span class="font-semibold text-slate-900">{{ $referrer->total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No referrers captured.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-900">Campaigns</h3>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">UTM</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($utmCampaigns as $campaign)
                    <div class="rounded-xl border border-slate-100 px-4 py-3 text-sm">
                        <p class="font-semibold text-slate-900">{{ $campaign->utm_source }} / {{ $campaign->utm_medium }}</p>
                        <p class="text-xs text-slate-500">{{ $campaign->utm_campaign ?? 'N/A' }}</p>
                        <p class="mt-2 text-xs text-slate-400">{{ $campaign->total }} events</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No campaigns tracked yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Product funnel</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Clicks</th>
                            <th class="px-3 py-2">Add to cart</th>
                            <th class="px-3 py-2">Checkout</th>
                            <th class="px-3 py-2">Paid</th>
                            <th class="px-3 py-2">Conv.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($productFunnel as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['name'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['clicks'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['add_to_cart'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['checkout_started'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['paid'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ number_format($row['conversion'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-sm text-slate-500">No product funnel data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Source funnel (UTM source)</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Source</th>
                            <th class="px-3 py-2">Views</th>
                            <th class="px-3 py-2">Clicks</th>
                            <th class="px-3 py-2">Add to cart</th>
                            <th class="px-3 py-2">Checkout</th>
                            <th class="px-3 py-2">CTR</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sourceFunnel as $row)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $row['utm_source'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['views'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['clicks'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['add_to_cart'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $row['checkout_started'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ number_format($row['ctr'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-sm text-slate-500">No source funnel data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-lg font-semibold text-slate-900">Daily performance</h3>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-2">Day</th>
                        <th class="px-3 py-2">Listing views</th>
                        <th class="px-3 py-2">Product clicks</th>
                        <th class="px-3 py-2">Invoice clicks</th>
                        <th class="px-3 py-2">Payments</th>
                        <th class="px-3 py-2">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($dailySeries as $row)
                        <tr>
                            <td class="px-3 py-2 text-slate-700">{{ $row['label'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row['listingViews'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row['productClicks'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row['invoiceClicks'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $row['payments'] }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ \App\Support\Money::format($row['revenue'], $currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Product conversions</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Impressions</th>
                            <th class="px-3 py-2">Clicks</th>
                            <th class="px-3 py-2">Payments</th>
                            <th class="px-3 py-2">Conversion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($productStats as $stat)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $stat['name'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $stat['impressions'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $stat['clicks'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $stat['payments'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ number_format($stat['conversion'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-sm text-slate-500">No product data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Invoice conversions</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Invoice</th>
                            <th class="px-3 py-2">Views</th>
                            <th class="px-3 py-2">Clicks</th>
                            <th class="px-3 py-2">Payments</th>
                            <th class="px-3 py-2">Conversion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($invoiceStats as $stat)
                            <tr>
                                <td class="px-3 py-2 text-slate-700">{{ $stat['title'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $stat['views'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $stat['clicks'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ $stat['payments'] }}</td>
                                <td class="px-3 py-2 text-slate-600">{{ number_format($stat['conversion'], 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-sm text-slate-500">No invoice data yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const rangeSelect = document.getElementById('insights-range');
        const customRange = document.getElementById('insights-custom-range');
        if (rangeSelect && customRange) {
            const toggleCustom = () => {
                customRange.classList.toggle('hidden', rangeSelect.value !== 'custom');
            };
            rangeSelect.addEventListener('change', toggleCustom);
            toggleCustom();
        }
    </script>
@endsection
