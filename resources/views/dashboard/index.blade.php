@php
    $title = 'Dashboard';
    $listingUrl = $profile ? route('public.listing', $profile->public_slug) : null;
@endphp

@once
    <style>
        @keyframes dashboard-marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .dashboard-marquee-track {
            display: inline-flex;
            min-width: max-content;
            animation: dashboard-marquee 16s linear infinite;
        }
    </style>
@endonce
@extends('layouts.dashboard')

@section('content')
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5 lg:gap-6">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-emerald-500">Orders</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">
                {{ number_format($ordersCount) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">Confirmed in {{ now()->format('F') }}</p>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50/60 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-blue-500">Amount received</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">
                {{ \App\Support\Money::format($amountReceived, $currency) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">Successful payments this month</p>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-indigo-500">Traffic</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">
                {{ number_format($trafficCount) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">Views and clicks this month</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-5 shadow-sm">
            <p class="text-xs uppercase tracking-[0.3em] text-amber-600">New customers</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">
                {{ number_format($newCustomersCount) }}
            </p>
            <p class="mt-2 text-xs text-slate-500">Unique buyers this month</p>
        </div>
        <div class="col-span-2 overflow-hidden rounded-xl border border-fuchsia-200 bg-fuchsia-50/70 p-3 shadow-sm lg:hidden">
            <div class="dashboard-marquee-track items-center gap-10 text-sm font-semibold text-fuchsia-700">
                <span>Conversion Rate {{ number_format($salesConversionRate, 1) }}%</span>
                <span>Orders {{ number_format($ordersCount) }}</span>
                <span>Traffic {{ number_format($trafficCount) }}</span>
                <span>New Customers {{ number_format($newCustomersCount) }}</span>
                <span>Conversion Rate {{ number_format($salesConversionRate, 1) }}%</span>
                <span>Orders {{ number_format($ordersCount) }}</span>
                <span>Traffic {{ number_format($trafficCount) }}</span>
                <span>New Customers {{ number_format($newCustomersCount) }}</span>
            </div>
        </div>
        <div class="hidden rounded-2xl border border-fuchsia-200 bg-fuchsia-50/60 p-5 shadow-sm lg:block">
            <p class="text-xs uppercase tracking-[0.3em] text-fuchsia-500">Conversion rate</p>
            <p class="mt-4 text-2xl font-semibold text-slate-900">
                {{ number_format($salesConversionRate, 1) }}%
            </p>
            <p class="mt-2 text-xs text-slate-500">Orders ÷ Traffic ({{ now()->format('F') }})</p>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-amber-700">Inventory alerts</p>
                <p class="mt-1 text-sm text-amber-900">Low stock and sold out products that need attention.</p>
            </div>
            <a href="{{ route('products.index', ['stock' => \App\Models\Product::STATUS_LOW_STOCK]) }}" class="rounded-full border border-amber-300 bg-white px-4 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">Review low stock</a>
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($inventoryAlerts as $item)
                <div class="rounded-xl border border-amber-100 bg-white px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">{{ $item->name }}</p>
                    <p class="mt-1 text-xs text-slate-600">
                        {{ $item->status === \App\Models\Product::STATUS_SOLD_OUT ? 'Sold out' : 'Low stock' }}
                        · Qty {{ $item->stock_quantity }}
                        · Threshold {{ $item->low_stock_threshold }}
                    </p>
                    <a href="{{ route('products.edit', $item) }}" class="mt-2 inline-flex text-xs font-semibold text-amber-700 hover:text-amber-800">Restock / Edit</a>
                </div>
            @empty
                <div class="col-span-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    No low-stock alerts right now.
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-8 grid items-stretch gap-6 lg:grid-cols-2">
        <div class="min-h-0 h-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Customer insights</h2>
                <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Top customers</span>
            </div>
            <div class="mt-4 flex flex-col gap-4 flex-1 min-h-0">
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-emerald-600">Average payment</p>
                    <p class="mt-2 text-lg font-semibold text-emerald-800">{{ \App\Support\Money::format($averagePayment, $currency) }}</p>
                </div>
                <div class="space-y-3 flex-1 overflow-y-auto pr-1">
                    @forelse($topCustomers as $customer)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $customer['email'] }}</p>
                                <p class="text-xs text-slate-500">{{ $customer['count'] }} payments</p>
                            </div>
                            <span class="text-sm font-semibold text-slate-700">
                                {{ \App\Support\Money::format($customer['total'], $currency) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No customer data yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="min-h-0 h-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Recent activity</h2>
                <a href="{{ route('notifications.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($activity as $item)
                    <div class="rounded-xl border border-slate-100 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">{{ $item['title'] }}</p>
                            <span class="text-xs text-slate-500">{{ $item['created_at']->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ $item['subtitle'] }}</p>
                        @if($item['type'] === 'payment')
                            <p class="mt-2 text-sm font-semibold text-emerald-700">{{ \App\Support\Money::format($item['amount'], $currency) }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No activity yet.</p>
                @endforelse
            </div>
            <div class="mt-4">
                <a href="{{ route('notifications.index') }}" class="inline-flex items-center rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    View full activity
                </a>
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Invoices to share</h2>
                <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentInvoices as $invoice)
                    @php
                        $invoiceLink = route('public.invoice', $invoice->token);
                        $invoiceMessage = "Hi, here is your invoice for {$invoice->title}. Pay here: {$invoiceLink}";
                    @endphp
                    <div class="flex flex-col gap-3 rounded-xl border border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $invoice->title }}</p>
                            <p class="text-xs text-slate-500">Status: {{ $invoice->status }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Open</a>
                            <button type="button" data-copy="{{ $invoiceMessage }}" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                Copy WhatsApp
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No invoices yet.</p>
                @endforelse
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">Products to share</h2>
                <a href="{{ route('products.index') }}" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">View all</a>
            </div>
            <div class="mt-4 space-y-3">
                @forelse($recentProducts as $product)
                    @php
                        $productMessage = $listingUrl
                            ? "Hi, you can pay for {$product->name} here: {$listingUrl}"
                            : "Hi, you can pay for {$product->name} on our 8Kommerce page.";
                    @endphp
                    <div class="flex flex-col gap-3 rounded-xl border border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                            <p class="text-xs text-slate-500">{{ \App\Support\Money::format($product->price, $currency) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('products.edit', $product) }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">Edit</a>
                            <button type="button" data-copy="{{ $productMessage }}" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                Copy WhatsApp
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No products yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(button.dataset.copy);
                    const original = button.textContent;
                    button.textContent = 'Copied';
                    setTimeout(() => { button.textContent = original; }, 1200);
                } catch (e) {
                    alert('Copy failed. Please copy manually.');
                }
            });
        });
    </script>
@endsection
