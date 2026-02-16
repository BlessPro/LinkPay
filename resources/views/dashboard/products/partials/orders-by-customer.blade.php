<div id="orders-by-customer" class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-6 py-5">
        <h3 class="text-lg font-semibold text-slate-900">Orders by customer</h3>
        <p class="mt-1 text-xs text-slate-500">Grouped customer orders with contact actions and item images.</p>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse(($ordersByCustomer ?? collect()) as $group)
            <div class="px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $group['customer_name'] }}</p>
                        <p class="text-xs text-slate-500">{{ $group['customer_phone'] ?? 'No phone' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ $group['orders_count'] }} order(s)
                        </span>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            {{ \App\Support\Money::format($group['group_total'], $currency) }}
                        </span>
                        @if($group['whatsapp_url'])
                            <a href="{{ $group['whatsapp_url'] }}" target="_blank" rel="noreferrer noopener" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                WhatsApp
                            </a>
                        @endif
                        @if($group['call_url'])
                            <a href="{{ $group['call_url'] }}" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                Call
                            </a>
                        @endif
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach($group['orders'] as $order)
                        <details id="order-{{ $order->id }}" class="rounded-xl border border-slate-200 bg-slate-50/70">
                            <summary class="list-none cursor-pointer px-4 py-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-800">Order {{ $order->reference }}</p>
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $order->status }}</span>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-emerald-700">{{ \App\Support\Money::format((string) $order->total, $currency) }}</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $order->created_at->format('M d, Y H:i') }}
                                    @if($order->customer_location) - {{ $order->customer_location }} @endif
                                    - Delivery: {{ $order->delivery_required ? 'Yes' : 'No' }}
                                </p>
                            </summary>
                            <div class="grid gap-3 border-t border-slate-200 px-4 py-4 sm:grid-cols-2">
                                @foreach($order->items as $item)
                                    <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-3">
                                        <div class="h-12 w-12 overflow-hidden rounded-lg bg-slate-100">
                                            @if($item->product?->image_path)
                                                <img src="{{ asset('storage/'.$item->product->image_path) }}" alt="{{ $item->product_name }}" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-slate-800">{{ $item->product_name }}</p>
                                            <p class="text-xs text-slate-500">Qty {{ $item->quantity }} - {{ \App\Support\Money::format((string) $item->line_total, $currency) }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="px-6 py-10 text-center text-sm text-slate-500">No orders yet.</div>
        @endforelse
    </div>
</div>
