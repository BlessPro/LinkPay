@php
    $title = 'Products';
@endphp
@extends('layouts.dashboard')

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Products</h2>
            <p class="text-sm text-slate-600">Monitor inventory, sales, and engagement.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('products.orders') }}" class="px-5 py-2 text-sm font-semibold border rounded-full border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                Manage orders
            </a>
            <a href="{{ route('products.create') }}" class="px-5 py-2 text-sm font-semibold text-white rounded-full bg-emerald-600 hover:bg-emerald-500">
                New product
            </a>
        </div>
    </div>

    <div class="mt-4 sm:hidden">
        <button type="button" id="stock-filter-open" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 .53 1.28l-4.47 4.47v4a.75.75 0 0 1-1.2.6l-2-1.5a.75.75 0 0 1-.3-.6v-2.5L3.22 6.28A.75.75 0 0 1 3 5.75Z"/>
            </svg>
            Filter products
        </button>
    </div>

    <div class="mt-4 hidden flex-wrap gap-2 sm:flex">
        @php
            $stockTabs = array_merge(['all' => 'All'], \App\Models\Product::statusOptions());
        @endphp
        @foreach($stockTabs as $key => $label)
            <a
                href="{{ route('products.index', array_merge(request()->query(), ['stock' => $key])) }}"
                class="rounded-full border px-4 py-1.5 text-xs font-semibold {{ ($stockFilter ?? 'all') === $key ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-200 hover:text-emerald-700' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div id="stock-filter-sheet" class="fixed inset-0 z-40 hidden bg-slate-900/35 sm:hidden">
        <div id="stock-filter-backdrop" class="absolute inset-0"></div>
        <div class="absolute inset-x-0 bottom-0 rounded-t-3xl border-t border-slate-200 bg-white p-5 shadow-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Filter products</h3>
                <button type="button" id="stock-filter-close" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">Close</button>
            </div>
            <div class="grid grid-cols-2 gap-2">
                @foreach($stockTabs as $key => $label)
                    <a
                        href="{{ route('products.index', array_merge(request()->query(), ['stock' => $key])) }}"
                        class="rounded-full border px-4 py-2 text-center text-xs font-semibold {{ ($stockFilter ?? 'all') === $key ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-white text-slate-600' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div id="export-offcanvas" class="fixed inset-y-0 right-0 z-40 hidden w-full max-w-sm p-6 bg-white border-l shadow-2xl border-slate-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Custom range</h3>
            <button type="button" id="export-close" class="px-3 py-1 text-xs font-semibold border rounded-full border-slate-200 text-slate-600">Close</button>
        </div>
        <div class="mt-6 space-y-4">
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">From</label>
                <input type="date" id="export-start-input" class="w-full mt-2 text-sm rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div>
                <label class="text-xs uppercase tracking-[0.3em] text-slate-400">To</label>
                <input type="date" id="export-end-input" class="w-full mt-2 text-sm rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <button type="button" id="export-apply" class="w-full px-4 py-3 text-sm font-semibold text-white rounded-full bg-emerald-600 hover:bg-emerald-500">
                Apply range
            </button>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[2fr_1fr] lg:items-stretch">
        <div class="flex flex-col h-full gap-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Revenue</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format($totalRevenue, $currency) }}</p>
                    <p class="mt-1 text-xs text-slate-500">All-time product sales</p>
                </div>
                <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Orders</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $totalOrders }}</p>
                    <p class="mt-1 text-xs text-slate-500">Successful payments</p>
                </div>
                <div class="p-5 bg-white border shadow-sm rounded-2xl border-slate-200">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Customers</p>
                    <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $totalCustomers }}</p>
                    <p class="mt-1 text-xs text-slate-500">Unique contacts</p>
                </div>
            </div>

            <div class="flex flex-col flex-1 p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Product performance</h3>
                        <p class="text-sm text-slate-500">
                            @if($chartRange === '7days')
                                Last 7 days
                            @elseif($chartRange === 'all_time')
                                All time
                            @else
                                Last 30 days
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-600">
                        @php
                            $metricOptions = [
                                'revenue' => 'Revenue',
                                'payments' => 'Payments',
                                'views' => 'Views',
                                'clicks' => 'Clicks',
                                'conversion' => 'Conversion %',
                            ];
                        @endphp
                        <form method="GET" action="{{ route('products.index') }}" class="inline-flex items-center gap-2">
                            <input type="hidden" name="chart_range" value="{{ $chartRange }}">
                            <div class="inline-flex overflow-hidden bg-white border rounded-full border-slate-200">
                                <select name="chart_range" class="px-3 py-2 text-xs font-semibold bg-white rounded-full text-slate-700">
                                    <option value="7days" @selected($chartRange === '7days')>7 days</option>
                                    <option value="30days" @selected($chartRange === '30days')>30 days</option>
                                    <option value="all_time" @selected($chartRange === 'all_time')>All time</option>
                                </select>
                            </div>
                            <button type="submit" class="px-3 py-2 text-xs font-semibold border rounded-full border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                Apply
                            </button>
                        </form>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach($metricOptions as $key => $label)
                                <label class="flex items-center gap-2 px-3 py-2 text-xs font-semibold bg-white border rounded-full border-slate-200 text-slate-600">
                                    <input type="checkbox" name="metric" value="{{ $key }}" class="w-3 h-3 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ $key === 'revenue' ? 'checked' : '' }}>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="flex-1 mt-6 min-h-56 max-h-72">
                    <canvas id="product-chart" class="w-full h-full"></canvas>
                    <div id="chart-empty" class="items-center justify-center hidden w-full h-full text-sm text-slate-400">
                        Chart unavailable. Run npm to rebuild assets.
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col h-full gap-6">
            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Top products</h3>
                    <a href="#product-list" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500">See more</a>
                </div>
                <div class="mt-4 space-y-4">
                    @forelse($topList as $productId => $stats)
                        @php
                            $productName = $productLookup[$productId]->name ?? 'Product';
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-700">{{ $productName }}</span>
                                <span class="font-semibold text-slate-900">{{ \App\Support\Money::format($stats['total'], $currency) }}</span>
                            </div>
                            <div class="h-2 mt-2 rounded-full bg-slate-100">
                                @php
                                    $width = min(100, ((float) $stats['total'] / $maxTopTotal) * 100);
                                @endphp
                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $width }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No sales yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-slate-900">Stock mix</h3>
                    <span class="text-xs text-slate-400">All products</span>
                </div>
                <div class="mt-4 space-y-3 text-sm">
                    @foreach(\App\Models\Product::statusOptions() as $key => $label)
                        @php
                            $count = $statusCounts[$key] ?? 0;
                        @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-slate-600">{{ $label }}</span>
                            <span class="font-semibold text-slate-900">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="p-6 bg-white border shadow-sm rounded-2xl border-slate-200">
                <h3 class="text-sm font-semibold text-slate-900">Export inventory</h3>
                <form method="POST" action="{{ route('products.exportPdf') }}" class="mt-4 space-y-3" id="export-form">
                    @csrf
                    <select name="type" class="w-full px-4 py-2 text-xs font-semibold bg-white border rounded-xl border-slate-200 text-slate-700">
                        <option value="products">Products only</option>
                        <option value="products_status">Products + stock status</option>
                        <option value="products_sales">Products + sales summary</option>
                    </select>
                    <select name="range" id="export-range" class="w-full px-4 py-2 text-xs font-semibold bg-white border rounded-xl border-slate-200 text-slate-700">
                        <option value="today">Today</option>
                        <option value="7days">7 days</option>
                        <option value="28days">28 days</option>
                        <option value="3months">3 months</option>
                        <option value="all_time" selected>All time</option>
                        <option value="custom">Custom</option>
                    </select>
                    <input type="hidden" name="start_date" id="export-start">
                    <input type="hidden" name="end_date" id="export-end">
                    <input type="hidden" name="chart_image" id="export-chart-image">
                    <div class="flex items-center justify-between">
                        <span id="export-label" class="text-xs font-semibold text-slate-500">All time</span>
                        <div class="flex items-center gap-2">
                            <button type="submit" formmethod="GET" formaction="{{ route('products.export') }}" class="px-4 py-2 text-xs font-semibold border rounded-full border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                Export CSV
                            </button>
                            <button type="submit" id="export-pdf" class="px-4 py-2 text-xs font-semibold text-white rounded-full bg-emerald-600 hover:bg-emerald-500">
                                Export PDF
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="product-list" class="mt-8 overflow-hidden bg-white border shadow-sm rounded-2xl border-slate-200">
        <div class="divide-y divide-slate-100">
            @if($products->count())
                @foreach($products as $product)
                    @php
                        $publicUrl = route('public.product', ['product_slug' => $product->slug]);
                        $imageUrl = $product->image_path ? url('storage/'.$product->image_path) : url('/images/og-default.jpg');
                        $waText = "Check this product: {$product->name} at {$publicUrl} "
                            ."Price: ".\App\Support\Money::format($product->price, $currency)
                            ." Image: {$imageUrl}";
                    @endphp
                    <details class="group">
                        <summary class="list-none">
                            <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="overflow-hidden h-14 w-14 rounded-xl bg-slate-100">
                                        @if($product->image_path)
                                            <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="object-cover w-full h-full">
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $product->name }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ \App\Support\Money::format($product->price, $currency) }}
                                            @php
                                                $ordersCount = (int) ($productOrderCounts[$product->id] ?? 0);
                                            @endphp
                                            <span class="ml-2 inline-flex rounded-full bg-slate-100 px-2 py-0.5 font-semibold text-slate-600">
                                                Orders: {{ $ordersCount }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="-mx-1 overflow-x-auto px-1 pb-1 sm:mx-0 sm:overflow-visible sm:px-0 sm:pb-0">
                                    <div class="flex w-max items-center gap-2 sm:w-auto sm:flex-wrap sm:justify-end sm:gap-3">
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $product->statusBadgeClass() }}">
                                        {{ $product->statusLabel() }}
                                    </span>
                                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <span class="shrink-0 px-3 py-1.5 text-xs font-semibold border rounded-full cursor-pointer border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700 sm:px-4 sm:py-2">
                                        Quick edit
                                    </span>
                                    <a
                                        href="https://api.whatsapp.com/send?text={{ rawurlencode($waText) }}"
                                        target="_blank"
                                        rel="noreferrer noopener"
                                        class="shrink-0 px-3 py-1.5 text-xs font-semibold border rounded-full border-slate-200 text-emerald-700 hover:border-emerald-300 hover:text-emerald-600 sm:px-4 sm:py-2"
                                    >
                                        Share to WhatsApp
                                    </a>
                                    <button
                                        type="button"
                                        class="shrink-0 px-3 py-1.5 text-xs font-semibold border rounded-full border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700 product-copy-link sm:px-4 sm:py-2"
                                        data-copy-link="{{ $publicUrl }}"
                                    >
                                        Copy link
                                    </button>
                                    <button
                                        type="button"
                                        class="shrink-0 px-3 py-1.5 text-xs font-semibold border rounded-full border-slate-200 text-slate-700 hover:border-emerald-200 hover:text-emerald-700 product-action-trigger sm:px-4 sm:py-2"
                                        data-product-name="{{ $product->name }}"
                                        data-product-id="{{ $product->id }}"
                                        data-product-slug="{{ $product->slug }}"
                                        data-product-view-url="{{ $publicUrl }}"
                                    >
                                        Manage
                                    </button>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product? This action cannot be undone.');" class="shrink-0">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="shrink-0 px-3 py-1.5 text-xs font-semibold border rounded-full border-rose-200 text-rose-700 hover:border-rose-300 hover:bg-rose-50 sm:px-4 sm:py-2"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                    </div>
                                </div>
                            </div>
                        </summary>
                        <div class="px-6 pb-6">
                            <div class="p-4 border shadow-sm rounded-2xl border-slate-200 bg-slate-50/70">
                                <form method="POST" action="{{ route('products.update', $product) }}" class="grid gap-3 sm:grid-cols-3">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="text-[11px] uppercase tracking-[0.3em] text-slate-400">Name</label>
                                        <input name="name" value="{{ $product->name }}" class="w-full px-3 py-2 mt-2 text-sm bg-white rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] uppercase tracking-[0.3em] text-slate-400">Price</label>
                                        <input name="price" value="{{ $product->price }}" type="number" step="0.01" class="w-full px-3 py-2 mt-2 text-sm bg-white rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                                    </div>
                                    <div>
                                        <label class="text-[11px] uppercase tracking-[0.3em] text-slate-400">Status</label>
                                        <div class="relative mt-2">
                                            <select name="status" class="w-full pr-10 text-sm bg-white appearance-none rounded-xl border-slate-200 text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">
                                                @foreach(\App\Models\Product::statusOptions() as $value => $label)
                                                    <option value="{{ $value }}" @selected($product->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-slate-400">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 sm:col-span-3">
                                        <div class="grid flex-1 gap-2 sm:grid-cols-2">
                                            <input name="stock_quantity" value="{{ $product->stock_quantity }}" type="number" min="0" class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Stock qty">
                                            <input name="low_stock_threshold" value="{{ $product->low_stock_threshold }}" type="number" min="0" class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Low threshold">
                                        </div>
                                        <button type="submit" class="px-4 py-2 text-xs font-semibold text-white rounded-full bg-emerald-600 hover:bg-emerald-500">
                                            Save changes
                                        </button>
                                        <span class="text-xs text-slate-500">Other fields stay unchanged.</span>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </details>
                @endforeach
            @else
                <div class="px-6 py-10 text-sm text-center text-slate-500">No products yet.</div>
            @endif
        </div>
    </div>

    @include('dashboard.products.partials.orders-by-customer')

    @php
        $productEditTemplate = route('products.edit', ['product' => '___ID___']);
        $productDeleteTemplate = route('products.destroy', ['product' => '___ID___']);
    @endphp

    <div id="product-manage-modal" class="fixed inset-0 z-50 items-center justify-center hidden p-4 bg-slate-900/40">
        <div class="w-full max-w-md p-6 bg-white shadow-xl rounded-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Manage product</p>
                    <h3 id="product-manage-title" class="mt-2 text-lg font-semibold text-slate-900">Product</h3>
                </div>
                <button type="button" class="px-3 py-1 text-xs font-semibold border rounded-full border-slate-200 text-slate-600 hover:border-slate-300" data-modal-close>
                    Close
                </button>
            </div>

            <div class="grid gap-3 mt-6">
                <a id="product-manage-view" href="#" target="_blank" rel="noreferrer noopener" class="px-4 py-3 text-sm font-semibold border rounded-xl border-slate-200 text-slate-800 hover:border-emerald-200 hover:text-emerald-700">
                    View public page
                </a>
                <a id="product-manage-edit" href="#" class="px-4 py-3 text-sm font-semibold border rounded-xl border-slate-200 text-slate-800 hover:border-emerald-200 hover:text-emerald-700">
                    Edit in dashboard
                </a>
                <form id="product-manage-delete-form" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-3 text-sm font-semibold border rounded-xl border-rose-200 text-rose-700 hover:border-rose-300">
                        Delete product
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const stockFilterSheet = document.getElementById('stock-filter-sheet');
            const stockFilterOpen = document.getElementById('stock-filter-open');
            const stockFilterClose = document.getElementById('stock-filter-close');
            const stockFilterBackdrop = document.getElementById('stock-filter-backdrop');

            const openStockFilterSheet = () => {
                if (!stockFilterSheet) return;
                stockFilterSheet.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            };

            const closeStockFilterSheet = () => {
                if (!stockFilterSheet) return;
                stockFilterSheet.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            };

            if (stockFilterOpen) {
                stockFilterOpen.addEventListener('click', openStockFilterSheet);
            }
            if (stockFilterClose) {
                stockFilterClose.addEventListener('click', closeStockFilterSheet);
            }
            if (stockFilterBackdrop) {
                stockFilterBackdrop.addEventListener('click', closeStockFilterSheet);
            }

            const modal = document.getElementById('product-manage-modal');
            const modalTitle = document.getElementById('product-manage-title');
            const viewLink = document.getElementById('product-manage-view');
            const editLink = document.getElementById('product-manage-edit');
            const deleteForm = document.getElementById('product-manage-delete-form');
            const editTemplate = @json($productEditTemplate);
            const deleteTemplate = @json($productDeleteTemplate);

            const openModal = () => {
                if (!modal) return;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = () => {
                if (!modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            document.querySelectorAll('[data-modal-close]').forEach((btn) => {
                btn.addEventListener('click', closeModal);
            });

            if (modal) {
                modal.addEventListener('click', (event) => {
                    if (event.target === modal) closeModal();
                });
            }

            document.querySelectorAll('.product-action-trigger').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.dataset.productId;
                    const name = btn.dataset.productName || 'Product';
                    const viewUrl = btn.dataset.productViewUrl || '#';

                    if (modalTitle) modalTitle.textContent = name;
                    if (viewLink) viewLink.href = viewUrl;
                    if (editLink) editLink.href = editTemplate.replace('___ID___', id);
                    if (deleteForm) deleteForm.action = deleteTemplate.replace('___ID___', id);

                    openModal();
                });
            });

            const toast = (message) => {
                const el = document.createElement('div');
                el.className = 'fixed bottom-5 left-1/2 z-[60] -translate-x-1/2 rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-lg';
                el.textContent = message;
                document.body.appendChild(el);
                window.setTimeout(() => el.remove(), 1800);
            };

            document.querySelectorAll('.product-copy-link').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const link = btn.dataset.copyLink;
                    if (!link) return;
                    try {
                        await navigator.clipboard.writeText(link);
                        toast('Link copied');
                    } catch (e) {
                        // Fallback for non-HTTPS / older browsers
                        const input = document.createElement('input');
                        input.value = link;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        input.remove();
                        toast('Link copied');
                    }
                });
            });

            const series = @json($series);
            const chartCanvas = document.getElementById('product-chart');
            const metricInputs = document.querySelectorAll('input[name="metric"]');

            const datasetColors = {
                revenue: '#10b981',
                payments: '#0ea5e9',
                views: '#6366f1',
                clicks: '#f59e0b',
                conversion: '#f97316',
            };

            const emptyState = document.getElementById('chart-empty');
            if (!window.Chart) {
                if (emptyState) {
                    emptyState.classList.remove('hidden');
                    emptyState.classList.add('flex');
                }
            } else if (chartCanvas && series.length) {
                const labels = series.map((row) => row.label);
                const dataMap = {
                    revenue: series.map((row) => Number(row.revenue ?? 0)),
                    payments: series.map((row) => Number(row.payments ?? 0)),
                    views: series.map((row) => Number(row.views ?? 0)),
                    clicks: series.map((row) => Number(row.clicks ?? 0)),
                    conversion: series.map((row) => Number(row.conversion ?? 0)),
                };

                const makeDataset = (metric) => ({
                    label: metric,
                    data: dataMap[metric] ?? [],
                    borderColor: datasetColors[metric],
                    backgroundColor: datasetColors[metric] + '33',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                });

                const selectedMetrics = () => {
                    const selected = Array.from(metricInputs)
                        .filter((input) => input.checked)
                        .map((input) => input.value);
                    return selected.length ? selected : ['revenue'];
                };

                const chart = new Chart(chartCanvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: selectedMetrics().map(makeDataset),
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { mode: 'index', intersect: false },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#94a3b8', maxTicksLimit: 6 },
                            },
                            y: {
                                grid: { color: '#f1f5f9' },
                                ticks: { color: '#94a3b8' },
                            },
                        },
                    },
                });

                metricInputs.forEach((input) => {
                    input.addEventListener('change', () => {
                        chart.data.datasets = selectedMetrics().map(makeDataset);
                        chart.update();
                    });
                });
            } else if (emptyState) {
                emptyState.classList.remove('hidden');
                emptyState.classList.add('flex');
            }

            const rangeSelect = document.getElementById('export-range');
            const label = document.getElementById('export-label');
            const offcanvas = document.getElementById('export-offcanvas');
            const closeBtn = document.getElementById('export-close');
            const applyBtn = document.getElementById('export-apply');
            const startInput = document.getElementById('export-start-input');
            const endInput = document.getElementById('export-end-input');
            const startHidden = document.getElementById('export-start');
            const endHidden = document.getElementById('export-end');
            const chartImageInput = document.getElementById('export-chart-image');
            const exportPdfButton = document.getElementById('export-pdf');

            const formatDate = (date) => date.toISOString().slice(0, 10);
            const setLabel = (text) => { if (label) label.textContent = text; };

            const computeRange = (value) => {
                const today = new Date();
                const end = new Date(today);
                let start = new Date(today);

                if (value === 'today') {
                    return { start, end, label: formatDate(start) };
                }
                if (value === '7days') {
                    start.setDate(start.getDate() - 6);
                } else if (value === '28days') {
                    start.setDate(start.getDate() - 27);
                } else if (value === '3months') {
                    start.setMonth(start.getMonth() - 3);
                } else if (value === 'all_time') {
                    return { start: null, end: null, label: 'All time' };
                }

                return { start, end, label: `${formatDate(start)} to ${formatDate(end)}` };
            };

            const openCanvas = () => offcanvas && offcanvas.classList.remove('hidden');
            const closeCanvas = () => offcanvas && offcanvas.classList.add('hidden');

            if (rangeSelect) {
                rangeSelect.addEventListener('change', () => {
                    const { start, end, label: rangeLabel } = computeRange(rangeSelect.value);
                    if (rangeSelect.value === 'custom') {
                        openCanvas();
                        setLabel('Custom');
                        return;
                    }
                    closeCanvas();
                    startHidden.value = start ? formatDate(start) : '';
                    endHidden.value = end ? formatDate(end) : '';
                    setLabel(rangeLabel);
                });
            }

            if (applyBtn) {
                applyBtn.addEventListener('click', () => {
                    const startVal = startInput?.value;
                    const endVal = endInput?.value;
                    startHidden.value = startVal || '';
                    endHidden.value = endVal || '';
                    if (startVal && endVal) {
                        setLabel(`${startVal} to ${endVal}`);
                    } else {
                        setLabel('Custom');
                    }
                    closeCanvas();
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', closeCanvas);
            }

            if (rangeSelect) {
                const { start, end, label: rangeLabel } = computeRange(rangeSelect.value);
                startHidden.value = start ? formatDate(start) : '';
                endHidden.value = end ? formatDate(end) : '';
                setLabel(rangeLabel);
            }

            if (exportPdfButton) {
                exportPdfButton.addEventListener('click', () => {
                    if (chartCanvas && chartImageInput) {
                        chartImageInput.value = chartCanvas.toDataURL('image/png');
                    }
                });
            }
        });
    </script>
@endsection
