@php
    $title = 'Notifications';
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Orders awaiting your decision</h2>
        <div class="mt-4 space-y-3">
            @forelse(($pendingOrders ?? collect()) as $order)
                <div class="rounded-xl border border-slate-100 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="text-sm font-semibold text-slate-900">Order {{ $order->reference }}</p>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $order->status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $order->customer_name ?: 'Customer' }} · {{ $order->customer_phone }} · {{ \App\Support\Money::format((string) $order->total, $currency ?? 'GHS') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Delivery: {{ $order->delivery_required ? 'Yes' : 'No' }}
                        @if($order->delivery_note) · {{ $order->delivery_note }} @endif
                    </p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-slate-600">
                        @foreach($order->items as $item)
                            <li>{{ $item->product_name }} x{{ $item->quantity }} ({{ \App\Support\Money::format((string) $item->line_total, $currency ?? 'GHS') }})</li>
                        @endforeach
                    </ul>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('notifications.orders.accept', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white hover:bg-emerald-500">Accept order</button>
                        </form>
                        <form method="POST" action="{{ route('notifications.orders.reject', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Cannot fulfill</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No pending paid orders right now.</p>
            @endforelse
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Notifications</h2>
        <div class="mt-4 space-y-3">
            @forelse($notifications as $notification)
                <div class="rounded-xl border border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ $notification->title }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $notification->body }}</p>
                    @if(is_array($notification->data) && count($notification->data))
                        <dl class="mt-3 grid gap-1 text-xs text-slate-500 sm:grid-cols-2">
                            @foreach($notification->data as $key => $value)
                                @continue(is_array($value) || is_object($value) || $value === null || $value === '')
                                <div class="flex items-center gap-2">
                                    <dt class="font-semibold text-slate-600">{{ str_replace('_', ' ', ucfirst((string) $key)) }}:</dt>
                                    <dd class="truncate">{{ (string) $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                    <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->format('M d, Y H:i') }}</p>
                </div>
            @empty
                <p class="text-sm text-slate-500">No notifications yet.</p>
            @endforelse
        </div>
        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
