@php
    $title = 'System Overview';
    $dailyMaxRevenue = max(1.0, (float) (collect($dailySeries)->map(fn ($row) => (float) $row['revenue'])->max() ?? 0));
    $dailyMaxCount = max(1, (int) (collect($dailySeries)->map(fn ($row) => (int) $row['count'])->max() ?? 0));
    $deliveryRate = $twilioTotal24h > 0 ? round((($twilioTotal24h - $twilioFailed24h) / $twilioTotal24h) * 100, 1) : 100;
    $webhookFailureRate = $webhookTotal24h > 0 ? round(($webhookFailed24h / $webhookTotal24h) * 100, 1) : 0;
@endphp
@extends('layouts.admin')

@section('content')
    @if(session('status') === 'retry-success')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Payment verification retried successfully and marked as settled.
        </div>
    @endif
    @if(session('status') === 'already-success')
        <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">
            Payment is already marked as success.
        </div>
    @endif
    @if(session('status') === 'manual-confirm-success')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Payment manually confirmed and removed from fallback queue.
        </div>
    @endif
    @if($errors->has('payment'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first('payment') }}
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Revenue (success)</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format($totalReceived, $currency) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Commission</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format($commissionTotal, $currency) }}</p>
            <p class="mt-1 text-xs text-slate-500">1% model</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Sellers</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $totalSellers }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $connectedSellers }} connected</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Payments</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $totalPayments }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Products / invoices</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">{{ $totalProducts }} / {{ $totalInvoices }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-6">
        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-700">Pending invoices</p>
            <p class="mt-3 text-2xl font-semibold text-amber-900">{{ $pendingInvoices }}</p>
        </div>
        <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-sky-700">Partial invoices</p>
            <p class="mt-3 text-2xl font-semibold text-sky-900">{{ $partialInvoices }}</p>
        </div>
        <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-5">
            <p class="text-xs uppercase tracking-[0.3em] text-rose-700">Failed payments</p>
            <p class="mt-3 text-2xl font-semibold text-rose-900">{{ $failedPayments }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Webhook failures (24h)</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $webhookFailed24h }}/{{ $webhookTotal24h }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $webhookFailureRate }}%</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Message delivery (24h)</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $deliveryRate }}%</p>
            <p class="mt-1 text-xs text-slate-500">{{ $twilioTotal24h - $twilioFailed24h }}/{{ $twilioTotal24h }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Failed jobs (24h)</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $failedJobs24h }}</p>
            <a href="{{ route('admin.jobs.failed') }}" class="mt-2 inline-flex text-xs font-semibold text-slate-600 hover:text-slate-900">
                Open queue
            </a>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Last {{ $compare7['label'] }}</p>
            <p class="mt-3 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($compare7['currentRevenue'], $currency) }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $compare7['currentCount'] }} payments</p>
            <p class="mt-3 text-sm text-slate-600">Revenue change: {{ number_format($compare7['revenueChange'], 1) }}%</p>
            <p class="text-sm text-slate-600">Volume change: {{ number_format($compare7['countChange'], 1) }}%</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Last {{ $compare30['label'] }}</p>
            <p class="mt-3 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($compare30['currentRevenue'], $currency) }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $compare30['currentCount'] }} payments</p>
            <p class="mt-3 text-sm text-slate-600">Revenue change: {{ number_format($compare30['revenueChange'], 1) }}%</p>
            <p class="text-sm text-slate-600">Volume change: {{ number_format($compare30['countChange'], 1) }}%</p>
        </div>
    </div>

    <div id="exceptions" class="mt-10 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Payment fallback queue</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Failed only</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($exceptionPayments as $payment)
                    <div class="rounded-xl border border-slate-100 px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($payment->amount, $currency) }} · {{ $payment->status }}</p>
                                <p class="text-xs text-slate-500">{{ $payment->reference }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ $payment->user?->sellerProfile?->business_name ?? $payment->user?->email ?? 'Unknown seller' }}</p>
                                @if($payment->user)
                                    <a href="{{ route('admin.sellers.show', $payment->user) }}" class="mt-2 inline-flex rounded-full border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:border-slate-300">
                                        Open seller
                                    </a>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <form method="POST" action="{{ route('admin.payments.retry', $payment) }}">
                                    @csrf
                                    <button type="submit" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-slate-300">Retry verify</button>
                                </form>
                                <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input
                                        type="text"
                                        name="note"
                                        placeholder="Optional note"
                                        class="w-32 rounded-full border-slate-200 px-3 py-1 text-xs focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                    <button type="submit" class="rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-500">Confirm paid</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No failed fallback payments right now.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Webhook monitor</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Paystack</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($webhookEvents as $event)
                    <div class="rounded-xl border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">{{ $event->event ?? 'unknown' }}</p>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $event->status === 'failed' ? 'bg-rose-50 text-rose-700' : ($event->status === 'processed' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700') }}">
                                {{ $event->status }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $event->reference ?: 'No reference' }}</p>
                        @if($event->error_message)
                            <p class="mt-1 text-xs text-rose-600">{{ $event->error_message }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No webhook events logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Daily revenue and volume</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">14 days</span>
            </div>
            <div class="mt-4 flex h-28 items-end gap-1">
                @foreach($dailySeries as $point)
                    @php
                        $height = $dailyMaxRevenue > 0 ? (((float) $point['revenue'] / $dailyMaxRevenue) * 100) : 0;
                    @endphp
                    <div class="flex-1">
                        <div class="h-24 rounded-t-lg bg-emerald-400/80" style="height: {{ max(6, $height) }}%" title="{{ $point['label'] }}: {{ \App\Support\Money::format($point['revenue'], $currency) }}"></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-3 flex h-20 items-end gap-1">
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
            <h2 class="text-lg font-semibold text-slate-900">Plan mix</h2>
            <div class="mt-4 space-y-3 text-sm">
                @foreach([App\Models\User::PLAN_FREE_TRIAL, App\Models\User::PLAN_STARTER, App\Models\User::PLAN_GROWTH, App\Models\User::PLAN_ENTERPRISE] as $plan)
                    <div class="flex items-center justify-between">
                        <span class="text-slate-600">{{ ucwords(strtolower(str_replace('_', ' ', $plan))) }}</span>
                        <span class="font-semibold text-slate-900">{{ $planCounts[$plan] ?? 0 }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-10 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Twilio delivery log</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Latest</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($twilioRecent as $message)
                    <div class="rounded-xl border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">{{ strtoupper($message->channel) }} · {{ $message->status ?? 'unknown' }}</p>
                            <p class="text-xs text-slate-500">{{ optional($message->sent_at)->format('M d H:i') ?? '-' }}</p>
                        </div>
                        <p class="text-xs text-slate-500">{{ $message->to }}</p>
                        @if($message->error_code)
                            <p class="mt-1 text-xs text-rose-600">Error {{ $message->error_code }}: {{ $message->error_message }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No Twilio logs yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Admin audit log</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Latest actions</span>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentAudits as $audit)
                    <div class="rounded-xl border border-slate-100 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">{{ $audit->action }}</p>
                        <p class="text-xs text-slate-500">{{ $audit->title }} · {{ $audit->adminUser?->email }}</p>
                        <p class="text-xs text-slate-400">{{ $audit->created_at->format('M d, Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No admin audit entries yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div id="sellers" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-slate-900">Sellers</h2>
            <span class="text-xs uppercase tracking-[0.3em] text-slate-400">System users</span>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs uppercase tracking-[0.2em] text-slate-400">
                    <tr>
                        <th class="px-3 py-3">Business</th>
                        <th class="px-3 py-3">Contact</th>
                        <th class="px-3 py-3">Plan</th>
                        <th class="px-3 py-3">Products</th>
                        <th class="px-3 py-3">Invoices</th>
                        <th class="px-3 py-3">Total received</th>
                        <th class="px-3 py-3">Paystack</th>
                        <th class="px-3 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sellers as $seller)
                        <tr>
                            <td class="px-3 py-3 font-semibold text-slate-900">{{ $seller->sellerProfile?->business_name ?? $seller->name }}</td>
                            <td class="px-3 py-3 text-slate-600">
                                <div>{{ $seller->email ?: '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $seller->sellerProfile?->phone ?? '-' }}</div>
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $seller->planDisplayName() }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $seller->products_count }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $seller->invoices_count }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ \App\Support\Money::format((string) ($seller->total_received ?? '0.00'), $currency) }}</td>
                            <td class="px-3 py-3">
                                @if($seller->suspended_at)
                                    <span class="mr-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">Suspended</span>
                                @endif
                                @if($seller->sellerProfile?->paystack_subaccount_code)
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Connected</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Not connected</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <a href="{{ route('admin.sellers.show', $seller) }}" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-slate-300">Open 360</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500">No sellers yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $sellers->links() }}</div>
    </div>
@endsection

