@php
    $title = 'Seller 360';
    $profile = $seller->sellerProfile;
@endphp
@extends('layouts.admin')

@section('content')
    @if(session('status') === 'seller-paystack-synced')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Paystack subaccount synced successfully.</div>
    @endif
    @if(session('status') === 'seller-suspended')
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">Seller account suspended.</div>
    @endif
    @if(session('status') === 'seller-unsuspended')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Seller account restored.</div>
    @endif
    @if(session('status') === 'seller-notified')
        <div class="mb-6 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700">Message delivered to seller notifications.</div>
    @endif
    @if($errors->has('seller'))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('seller') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Seller</p>
            <p class="mt-3 text-xl font-semibold text-slate-900">{{ $profile?->business_name ?? $seller->name }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ $seller->email ?: '-' }} · {{ $profile?->phone ?? '-' }}</p>
            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">{{ str_replace('_', ' ', $seller->plan_type ?? App\Models\User::PLAN_FREE_TRIAL) }}</span>
                @if($seller->suspended_at)
                    <span class="rounded-full bg-rose-50 px-3 py-1 font-semibold text-rose-700">Suspended</span>
                @endif
                @if($profile?->paystack_subaccount_code)
                    <span class="rounded-full bg-emerald-50 px-3 py-1 font-semibold text-emerald-700">Paystack connected</span>
                @else
                    <span class="rounded-full bg-amber-50 px-3 py-1 font-semibold text-amber-700">Paystack not connected</span>
                @endif
            </div>
            @if($seller->suspended_at && $seller->suspension_note)
                <p class="mt-3 text-xs text-rose-600">Suspension note: {{ $seller->suspension_note }}</p>
            @endif
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Total received</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format($totalReceived, $currency) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Payments</p>
            <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $paymentCount }}</p>
            <p class="text-xs text-slate-500">{{ $pendingCount }} pending</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Admin actions</h3>
            <div class="mt-4 space-y-3">
                <form method="POST" action="{{ route('admin.sellers.sync-paystack', $seller) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:border-slate-300">Create/Sync Paystack subaccount</button>
                </form>

                @if(!$seller->suspended_at)
                    <form method="POST" action="{{ route('admin.sellers.suspend', $seller) }}" class="space-y-2">
                        @csrf
                        <input type="text" name="note" placeholder="Suspension reason (optional)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <button type="submit" class="w-full rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500">Suspend account</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.sellers.unsuspend', $seller) }}">
                        @csrf
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Unsuspend account</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h3 class="text-sm font-semibold text-slate-900">Message seller</h3>
            <form method="POST" action="{{ route('admin.sellers.notify', $seller) }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="text-xs uppercase tracking-[0.2em] text-slate-500">Title</label>
                    <input type="text" name="title" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs uppercase tracking-[0.2em] text-slate-500">Message</label>
                    <textarea name="body" rows="4" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
                </div>
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Send to notifications</button>
            </form>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Catalog</h3>
            <p class="mt-2 text-sm text-slate-600">{{ $productCount }} products</p>
            <div class="mt-3 space-y-2 text-sm text-slate-600">
                @forelse($seller->products as $product)
                    <div class="flex items-center justify-between">
                        <span>{{ $product->name }}</span>
                        <span>{{ $product->status }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">No products.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Invoices</h3>
            <p class="mt-2 text-sm text-slate-600">{{ $invoiceCount }} total</p>
            <div class="mt-3 space-y-2 text-sm text-slate-600">
                @forelse($seller->invoices as $invoice)
                    <div class="flex items-center justify-between">
                        <span>{{ $invoice->title }}</span>
                        <span>{{ $invoice->status }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">No invoices.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Recent payments</h3>
            <div class="mt-3 space-y-2 text-sm text-slate-600">
                @forelse($recentPayments as $payment)
                    <div class="flex items-center justify-between">
                        <span>{{ $payment->status }}</span>
                        <span>{{ \App\Support\Money::format($payment->amount, $currency) }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">No payments.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Unified timeline</h3>
        <div class="mt-4 space-y-3">
            @forelse($timeline as $entry)
                <div class="rounded-xl border border-slate-100 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">{{ ucfirst($entry['type']) }} · {{ $entry['title'] }}</p>
                        <span class="text-xs text-slate-500">{{ optional($entry['created_at'])->format('M d, Y H:i') }}</span>
                    </div>
                    <p class="text-xs text-slate-500">{{ $entry['meta'] }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No timeline events yet.</p>
            @endforelse
        </div>
    </div>
@endsection
