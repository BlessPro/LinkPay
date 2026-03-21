@php
    $title = 'Payment Reconciliation';
    $statusCounts = $report['statusCounts'];
    $visiblePaymentIds = collect($report['exceptions'] ?? [])->pluck('payment_id')->filter()->unique();
    $visibleActionableIds = collect($report['exceptions'] ?? [])
        ->filter(fn ($row) => ! empty($row['payment_id']) && (($row['local_status'] ?? null) !== \App\Models\Payment::STATUS_SUCCESS))
        ->pluck('payment_id')
        ->unique();
    $bulkRetryCount = $visibleActionableIds->count();
    $bulkMarkFailedCount = $visibleActionableIds->count();
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
    @if(session('status') === 'reconciliation-bulk-retry')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Bulk retry complete: retried {{ data_get(session('bulk_retry'), 'retried', 0) }}, settled {{ data_get(session('bulk_retry'), 'settled', 0) }}, unresolved {{ data_get(session('bulk_retry'), 'failed', 0) }}.
        </div>
    @endif
    @if(session('status') === 'reconciliation-bulk-mark-failed')
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Bulk mark failed complete: marked {{ data_get(session('bulk_mark_failed'), 'marked', 0) }}, skipped-success {{ data_get(session('bulk_mark_failed'), 'skipped', 0) }}.
        </div>
    @endif
    @if($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ $errors->first() }}
        </div>
    @endif
    @if($alertLevel === 'critical')
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            Critical reconciliation alert: {{ $report['severityBuckets']['critical'] ?? 0 }} aged critical exception(s) detected (threshold {{ $criticalThreshold }}).
        </div>
    @elseif($alertLevel === 'high')
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            High reconciliation alert: {{ $report['severityBuckets']['high'] ?? 0 }} high-severity exception(s) detected (threshold {{ $highThreshold }}).
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('admin.payments.reconciliation') }}" class="grid gap-3 md:grid-cols-[140px_1fr_1fr_auto] md:items-end">
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
            <div>
                <label for="type" class="mb-1 block text-xs uppercase tracking-[0.2em] text-slate-400">Exception type</label>
                <select id="type" name="type" class="w-full rounded-xl border-slate-200 text-sm focus:border-slate-400 focus:ring-slate-400">
                    <option value="">All types</option>
                    @foreach(['missing_in_db', 'missing_in_paystack', 'amount_mismatch', 'status_mismatch', 'duplicate_reference'] as $type)
                        <option value="{{ $type }}" @selected(($report['type'] ?? null) === $type)>
                            {{ str_replace('_', ' ', $type) }}
                        </option>
                    @endforeach
                </select>
                <label class="mt-2 inline-flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="aged_only" value="1" @checked($report['agedOnly'] ?? false)>
                    Aged only (24h+)
                </label>
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Run report</button>
        </form>
        <div class="mt-3">
            <a
                href="{{ route('admin.payments.reconciliation.export', ['days' => $report['days'], 'seller_id' => $report['sellerId'], 'type' => $report['type'], 'aged_only' => $report['agedOnly'] ? 1 : 0]) }}"
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

    <div class="mt-4 grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-rose-700">Critical (aged)</p>
            <p class="mt-2 text-2xl font-semibold text-rose-900">{{ $report['severityBuckets']['critical'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-amber-700">High</p>
            <p class="mt-2 text-2xl font-semibold text-amber-900">{{ $report['severityBuckets']['high'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-sky-100 bg-sky-50/70 p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-sky-700">Medium</p>
            <p class="mt-2 text-2xl font-semibold text-sky-900">{{ $report['severityBuckets']['medium'] ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Visible exceptions</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $report['exceptionTotal'] ?? 0 }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-slate-900">Exceptions</h2>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs uppercase tracking-[0.2em] text-slate-400">Severity + age prioritized</span>
                <form method="POST" action="{{ route('admin.payments.reconciliation.bulk-retry') }}">
                    @csrf
                    <input type="hidden" name="days" value="{{ $report['days'] }}">
                    <input type="hidden" name="seller_id" value="{{ $report['sellerId'] }}">
                    <input type="hidden" name="type" value="{{ $report['type'] }}">
                    <input type="hidden" name="aged_only" value="{{ $report['agedOnly'] ? 1 : 0 }}">
                    <button type="submit" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold {{ $bulkRetryCount > 0 ? 'text-slate-700 hover:border-slate-300' : 'text-slate-400 cursor-not-allowed' }}" {{ $bulkRetryCount > 0 ? '' : 'disabled' }}>
                        Retry visible ({{ $bulkRetryCount }})
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.payments.reconciliation.bulk-mark-failed') }}" class="flex items-center gap-2">
                    @csrf
                    <input type="hidden" name="days" value="{{ $report['days'] }}">
                    <input type="hidden" name="seller_id" value="{{ $report['sellerId'] }}">
                    <input type="hidden" name="type" value="{{ $report['type'] }}">
                    <input type="hidden" name="aged_only" value="{{ $report['agedOnly'] ? 1 : 0 }}">
                    <input type="text" name="note" value="Bulk failed from reconciliation workflow" class="w-48 rounded-full border-slate-200 px-3 py-1 text-xs focus:border-amber-400 focus:ring-amber-400" placeholder="Reason note" />
                    <button type="submit" class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold {{ $bulkMarkFailedCount > 0 ? 'text-amber-700 hover:bg-amber-100' : 'text-amber-300 cursor-not-allowed' }}" {{ $bulkMarkFailedCount > 0 ? '' : 'disabled' }}>
                        Mark visible failed ({{ $bulkMarkFailedCount }})
                    </button>
                </form>
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-500">
            Preview: {{ $visiblePaymentIds->count() }} visible exception payment(s), {{ $bulkRetryCount }} actionable (non-success) payment(s).
        </p>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-600">
                <p class="font-semibold text-slate-800">Playbook: missing in DB</p>
                <p>Check webhook logs and payment callback path. Usually indicates callback reached Paystack but local persistence failed.</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-600">
                <p class="font-semibold text-slate-800">Playbook: status mismatch</p>
                <p>Run retry verify first. If Paystack says success and local is failed, settle manually only with evidence.</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-600">
                <p class="font-semibold text-slate-800">Playbook: amount mismatch</p>
                <p>Validate currency/minor units and reference integrity before confirming or marking failed.</p>
            </div>
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3 text-xs text-slate-600">
                <p class="font-semibold text-slate-800">Playbook: duplicate reference</p>
                <p>Keep one canonical payment, mark stale duplicates failed with note for audit trail.</p>
            </div>
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
                        <th class="px-3 py-3">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['exceptions'] as $row)
                        <tr>
                            <td class="px-3 py-3">
                                @php
                                    $typeClass = match ($row['type']) {
                                        'missing_in_db' => 'bg-rose-100 text-rose-800',
                                        'status_mismatch' => 'bg-amber-100 text-amber-800',
                                        'amount_mismatch' => 'bg-indigo-100 text-indigo-800',
                                        'missing_in_paystack' => 'bg-sky-100 text-sky-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $typeClass }}">{{ str_replace('_', ' ', $row['type']) }}</span>
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
                                            <input type="hidden" name="note" value="Confirmed from reconciliation workflow">
                                            <button type="submit" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Mark success</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.payments.mark-failed', $row['payment_id']) }}">
                                            @csrf
                                            <input type="hidden" name="note" value="Marked failed from reconciliation workflow">
                                            <button type="submit" class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100">Mark failed</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">No local payment</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <details class="group">
                                    <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-slate-900">View</summary>
                                    <div class="mt-2 space-y-1 rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs text-slate-600">
                                        <p><span class="font-semibold text-slate-700">Created:</span> {{ optional($row['created_at'])->toDateTimeString() }}</p>
                                        <p><span class="font-semibold text-slate-700">Age (h):</span> {{ $row['age_hours'] ?? 0 }}</p>
                                        <p><span class="font-semibold text-slate-700">Severity score:</span> {{ $row['severity_score'] ?? 0 }}</p>
                                        <p><span class="font-semibold text-slate-700">Payment ID:</span> {{ $row['payment_id'] ?? '-' }}</p>
                                        <p><span class="font-semibold text-slate-700">Seller ID:</span> {{ $row['seller_id'] ?? '-' }}</p>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-sm text-slate-500">No exceptions found in selected window.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
