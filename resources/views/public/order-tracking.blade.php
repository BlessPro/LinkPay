@php
    $title = 'Track order';
@endphp
@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Order tracking</p>
            <h1 class="mt-2 text-3xl font-semibold text-slate-900">Track your order</h1>
            <p class="mt-2 text-sm text-slate-600">Enter your order reference and checkout phone number.</p>

            <form method="GET" action="{{ route('public.orders.track') }}" class="mt-5 grid gap-3 sm:grid-cols-2">
                <div>
                    <label for="reference" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Order reference</label>
                    <input
                        id="reference"
                        name="reference"
                        value="{{ $reference }}"
                        placeholder="e.g. 0f7d6f94-..."
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>
                <div>
                    <label for="phone_number" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone number</label>
                    <input
                        id="phone_number"
                        name="phone_number"
                        value="{{ $phoneInput }}"
                        placeholder="0541900229"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                        Track order
                    </button>
                </div>
            </form>

            @if($lookupError)
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    {{ $lookupError }}
                </div>
            @endif
        </div>

        @if($order)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Order details</p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">{{ $order->reference }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $order->status === \App\Models\Order::STATUS_ACCEPTED ? 'bg-emerald-100 text-emerald-700' : ($order->status === \App\Models\Order::STATUS_CANNOT_FULFILL ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                        {{ str_replace('_', ' ', $order->status) }}
                    </span>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-100 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Total</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">{{ \App\Support\Money::format((string) $order->total, $currency) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Delivery</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">{{ $order->delivery_required ? 'Required' : 'Pickup' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-100 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Placed</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">{{ $order->created_at?->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Timeline</p>
                <div class="mt-4 space-y-4">
                    @foreach($timeline as $entry)
                        <div class="flex gap-3 rounded-2xl border border-slate-100 px-4 py-3">
                            <div class="mt-1 h-2.5 w-2.5 rounded-full {{ $entry['state'] === 'done' ? 'bg-emerald-500' : ($entry['state'] === 'failed' ? 'bg-rose-500' : 'bg-amber-500') }}"></div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $entry['title'] }}</p>
                                <p class="text-sm text-slate-600">{{ $entry['body'] }}</p>
                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $entry['time'] ? $entry['time']->format('M d, Y h:i A') : 'Pending update' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection

