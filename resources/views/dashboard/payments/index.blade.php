@php($title = 'Payments')
@extends('layouts.dashboard')

@section('content')
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
        <div class="mt-4 flex gap-4 overflow-x-auto">
            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 text-center">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Total received</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format($totalReceived, $currency) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 text-center">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Last 30 days</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format($last30DaysReceived, $currency) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 text-center">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Pending verifications</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $pendingCount }}</p>
                <p class="text-xs text-slate-500">of {{ $payments->total() }} total</p>
            </div>
        </div>
        <div class="mt-6 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Revenue + volume (30d)</h3>
                <p class="text-xs text-slate-500">{{ \App\Support\Money::format($totalReceived, $currency) }} total</p>
            </div>
            <div class="mt-4 h-64">
                <canvas id="payments-chart" class="h-full w-full"></canvas>
            </div>
        </div>
        <div class="mt-6 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Export payments</h3>
                <p class="text-xs text-slate-400">Verified transactions only</p>
            </div>
            <form method="GET" action="{{ route('payments.export') }}" class="mt-4 space-y-3 text-sm text-slate-600">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-xs uppercase tracking-[0.3em] text-slate-400">Range</label>
                    <select name="range" id="payments-export-range" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700">
                        <option value="today" @selected(request('range') === 'today')>Today</option>
                        <option value="7days" @selected(request('range', '30days') === '7days')>7 days</option>
                        <option value="30days" @selected(request('range', '30days') === '30days')>30 days</option>
                        <option value="all_time" @selected(request('range') === 'all_time')>All time</option>
                        <option value="custom" @selected(request('range') === 'custom')>Custom</option>
                    </select>
                </div>
                <div id="payments-export-custom" class="space-y-2 @unless(request('range') === 'custom') hidden @endunless">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Custom window</p>
                    <div class="flex flex-wrap gap-3">
                        <input type="date" name="start_date" value="{{ request('start_date') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-600" />
                        <input type="date" name="end_date" value="{{ request('end_date') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-600" />
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="format" value="csv" class="flex-1 rounded-full border border-emerald-200 px-4 py-2 text-xs font-semibold text-emerald-700 hover:border-emerald-300">
                        Export CSV
                    </button>
                    <button type="submit" name="format" value="pdf" class="flex-1 rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                        Export PDF
                    </button>
                </div>
            </form>
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
            @forelse($payments as $payment)
                <div class="flex flex-col gap-3 rounded-xl border border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }}</p>
                        <p class="text-xs text-slate-500">Reference: {{ $payment->reference }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $payment->status }}</span>
                        <span class="text-xs text-slate-500">{{ $payment->created_at->format('M d, Y') }}</span>
                        @if($payment->status === \App\Models\Payment::STATUS_PENDING)
                            <form method="POST" action="{{ route('payments.verify', $payment) }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                    Verify
                                </button>
                            </form>
                        @endif
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const series = @json($dailySeries);
            const canvas = document.getElementById('payments-chart');
            if (!canvas || !window.Chart || !series.length) {
                return;
            }

            const labels = series.map((row) => row.label);
            const revenueSet = series.map((row) => Number(row.revenue ?? 0));
            const volumeSet = series.map((row) => Number(row.payments ?? 0));

            new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: revenueSet,
                            borderColor: '#10b981',
                            backgroundColor: '#10b98122',
                            fill: true,
                            tension: 0.35,
                        },
                        {
                            label: 'Payments',
                            data: volumeSet,
                            borderColor: '#0ea5e9',
                            backgroundColor: '#0ea5e922',
                            fill: true,
                            tension: 0.35,
                            yAxisID: 'yVolume',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: { mode: 'index', intersect: false },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8' },
                        },
                        y: {
                            type: 'linear',
                            position: 'left',
                            grid: { color: '#eef2ff' },
                            ticks: { color: '#94a3b8' },
                        },
                        yVolume: {
                            type: 'linear',
                            position: 'right',
                            grid: { display: false },
                            ticks: { color: '#0ea5e9' },
                        },
                    },
                },
            });
            const rangeSelect = document.getElementById('payments-export-range');
            const customInputs = document.getElementById('payments-export-custom');
            if (rangeSelect && customInputs) {
                rangeSelect.addEventListener('change', () => {
                    if (rangeSelect.value === 'custom') {
                        customInputs.classList.remove('hidden');
                    } else {
                        customInputs.classList.add('hidden');
                    }
                });
            }
        });
    </script>
@endsection
