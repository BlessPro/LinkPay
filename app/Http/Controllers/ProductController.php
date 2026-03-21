<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Payment;
use App\Services\OgImageService;
use App\Services\InventoryService;
use App\Support\Money;
use App\Support\Phone;
use App\Support\WhatsApp;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function orders(Request $request)
    {
        $user = $request->user();

        return view('dashboard.products.orders', [
            'ordersByCustomer' => $this->buildOrdersByCustomer($user->id),
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $stockFilter = (string) $request->query('stock', 'all');
        $allowedStockFilters = array_merge(['all'], array_keys(Product::statusOptions()));
        if (! in_array($stockFilter, $allowedStockFilters, true)) {
            $stockFilter = 'all';
        }

        $products = $user->products()
            ->when($stockFilter !== 'all', fn ($query) => $query->where('status', $stockFilter))
            ->latest()
            ->paginate(10)
            ->withQueryString();
        $chartRange = $request->query('chart_range', '30days');
        [$chartStart, $chartEnd] = $this->resolveChartDateRange($chartRange);
        $series = $this->buildProductSeries($user->id, $chartRange);

        $directPayments = Payment::where('user_id', $user->id)
            ->whereNotNull('product_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->get();

        $orderProductSales = $this->paidOrderItemsQuery($user->id)
            ->selectRaw('order_items.product_id, sum(order_items.quantity) as units, sum(order_items.line_total) as total')
            ->groupBy('order_items.product_id')
            ->get();

        $totalRevenue = '0.00';
        foreach ($directPayments as $payment) {
            $totalRevenue = Money::add($totalRevenue, (string) $payment->amount);
        }
        foreach ($orderProductSales as $sale) {
            $totalRevenue = Money::add($totalRevenue, (string) ($sale->total ?? '0.00'));
        }

        $customerKeys = $directPayments->map(function (Payment $payment) {
            return data_get($payment->raw_payload, 'customer.email')
                ?? data_get($payment->raw_payload, 'customer.phone');
        })->filter();
        $orderCustomerKeys = $user->orders()
            ->where('payment_status', Payment::STATUS_SUCCESS)
            ->get()
            ->map(fn (Order $order) => $order->customer_phone ?: $order->customer_name)
            ->filter();
        $customerKeys = $customerKeys->merge($orderCustomerKeys)->unique();

        $statusCounts = $user->products()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $topProducts = $directPayments->groupBy('product_id')
            ->map(function ($group) {
                $sum = '0.00';
                foreach ($group as $payment) {
                    $sum = Money::add($sum, (string) $payment->amount);
                }
                return [
                    'count' => $group->count(),
                    'total' => $sum,
                ];
            });
        foreach ($orderProductSales as $sale) {
            $productId = (int) $sale->product_id;
            $current = $topProducts->get($productId, ['count' => 0, 'total' => '0.00']);
            $topProducts->put($productId, [
                'count' => (int) $current['count'] + (int) ($sale->units ?? 0),
                'total' => Money::add((string) $current['total'], (string) ($sale->total ?? '0.00')),
            ]);
        }
        $productLookup = $user->products()->get(['id', 'name'])->keyBy('id');
        $topList = collect($topProducts)->sortByDesc('total')->take(4);
        $maxTopTotal = max(1, (float) ($topList->map(fn ($row) => (float) $row['total'])->max() ?? 0));

        $ordersByCustomer = $this->buildOrdersByCustomer($user->id);

        $directOrderCounts = Payment::query()
            ->where('user_id', $user->id)
            ->whereNotNull('product_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->when($chartStart && $chartEnd, fn ($q) => $q->whereBetween('created_at', [$chartStart, $chartEnd]))
            ->selectRaw('product_id, count(*) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $cartOrderCounts = $this->paidOrderItemsQuery($user->id, $chartStart, $chartEnd)
            ->selectRaw('order_items.product_id, sum(order_items.quantity) as total')
            ->groupBy('order_items.product_id')
            ->pluck('total', 'order_items.product_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        $productOrderCounts = [];
        foreach ($directOrderCounts as $productId => $total) {
            $productOrderCounts[(int) $productId] = (int) $total;
        }
        foreach ($cartOrderCounts as $productId => $total) {
            $productId = (int) $productId;
            $productOrderCounts[$productId] = (int) ($productOrderCounts[$productId] ?? 0) + (int) $total;
        }

        return view('dashboard.products.index', [
            'products' => $products,
            'stockFilter' => $stockFilter,
            'currency' => config('services.paystack.currency', 'GHS'),
            'series' => $series,
            'chartRange' => $chartRange,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $directPayments->count() + (int) $user->orders()->where('payment_status', Payment::STATUS_SUCCESS)->count(),
            'totalCustomers' => $customerKeys->count(),
            'statusCounts' => $statusCounts,
            'topProducts' => $topProducts,
            'productLookup' => $productLookup,
            'topList' => $topList,
            'maxTopTotal' => $maxTopTotal,
            'ordersByCustomer' => $ordersByCustomer,
            'productOrderCounts' => $productOrderCounts,
        ]);
    }

    private function buildOrdersByCustomer(int $userId)
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->with(['items.product'])
            ->latest()
            ->take(80)
            ->get();

        return $orders
            ->groupBy(function (Order $order) {
                return $order->customer_phone ?: 'unknown-'.$order->id;
            })
            ->map(function ($group) {
                /** @var \Illuminate\Support\Collection<int, Order> $group */
                $first = $group->first();
                $groupTotal = '0.00';
                foreach ($group as $order) {
                    $groupTotal = Money::add($groupTotal, (string) $order->total);
                }

                $phone = Phone::normalize((string) ($first?->customer_phone ?? ''), '+233');
                $customerName = $first?->customer_name ?: 'Customer';
                $message = 'Hello '.$customerName.', regarding your order(s) on 8Kommerce.';

                return [
                    'customer_name' => $customerName,
                    'customer_phone' => $phone,
                    'orders_count' => $group->count(),
                    'group_total' => $groupTotal,
                    'whatsapp_url' => $phone ? WhatsApp::chatUrl($phone, $message) : null,
                    'call_url' => $phone ? 'tel:'.$phone : null,
                    'orders' => $group,
                ];
            })
            ->values();
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type', 'products');
        $range = $request->query('range', 'all_time');
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');

        [$start, $end] = $this->resolveDateRange($range, $customStart, $customEnd);

        $products = $user->products()->latest()->get();

        $rows = [];
        if ($type === 'products_status') {
            $rows[] = ['Product', 'Price', 'Status', 'Active', 'Created at'];
            foreach ($products as $product) {
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    $product->statusLabel(),
                    $product->is_active ? 'Active' : 'Inactive',
                    $product->created_at->toDateString(),
                ];
            }
            $filename = 'products_status.csv';
        } elseif ($type === 'products_sales') {
            $directPayments = Payment::where('user_id', $user->id)
                ->whereNotNull('product_id')
                ->where('status', Payment::STATUS_SUCCESS)
                ->when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
                ->get()
                ->groupBy('product_id');
            $orderSales = $this->paidOrderItemsQuery($user->id, $start, $end)
                ->selectRaw('order_items.product_id, sum(order_items.quantity) as units, sum(order_items.line_total) as total')
                ->groupBy('order_items.product_id')
                ->get()
                ->keyBy('product_id');

            $rows[] = ['Product', 'Price', 'Payments', 'Revenue', 'Range'];
            foreach ($products as $product) {
                $group = $directPayments->get($product->id, collect());
                $directTotal = '0.00';
                foreach ($group as $payment) {
                    $directTotal = Money::add($directTotal, (string) $payment->amount);
                }
                $orderTotal = (string) ($orderSales->get($product->id)->total ?? '0.00');
                $total = Money::add($directTotal, $orderTotal);
                $paymentsCount = $group->count() + (int) ($orderSales->get($product->id)->units ?? 0);
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    (string) $paymentsCount,
                    $total,
                    $this->rangeLabel($start, $end, $range),
                ];
            }
            $filename = 'products_sales.csv';
        } else {
            $rows[] = ['Product', 'Price', 'Active', 'Created at'];
            foreach ($products as $product) {
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    $product->is_active ? 'Active' : 'Inactive',
                    $product->created_at->toDateString(),
                ];
            }
            $filename = 'products.csv';
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $type = $request->input('type', 'products');
        $range = $request->input('range', 'all_time');
        $customStart = $request->input('start_date');
        $customEnd = $request->input('end_date');
        $chartImage = $request->input('chart_image');

        [$start, $end] = $this->resolveDateRange($range, $customStart, $customEnd);

        $products = $user->products()->latest()->get();
        $rangeLabel = $this->rangeLabel($start, $end, $range);

        $paymentsQuery = Payment::where('user_id', $user->id)
            ->whereNotNull('product_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]));
        $payments = $paymentsQuery->get();
        $orderSales = $this->paidOrderItemsQuery($user->id, $start, $end)
            ->selectRaw('order_items.product_id, sum(order_items.quantity) as units, sum(order_items.line_total) as total')
            ->groupBy('order_items.product_id')
            ->get()
            ->keyBy('product_id');

        $totalRevenue = '0.00';
        foreach ($payments as $payment) {
            $totalRevenue = Money::add($totalRevenue, (string) $payment->amount);
        }
        foreach ($orderSales as $sale) {
            $totalRevenue = Money::add($totalRevenue, (string) ($sale->total ?? '0.00'));
        }

        $eventsQuery = AnalyticsEvent::where('user_id', $user->id)
            ->where('entity_type', 'product')
            ->when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]));

        $views = (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)->count();
        $clicks = (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)->count();
        $conversion = $clicks > 0 ? round(($payments->count() / $clicks) * 100, 2) : 0.0;

        $rows = [];
        if ($type === 'products_status') {
            $rows[] = ['Product', 'Price', 'Status', 'Active', 'Created at'];
            foreach ($products as $product) {
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    $product->statusLabel(),
                    $product->is_active ? 'Active' : 'Inactive',
                    $product->created_at->toDateString(),
                ];
            }
        } elseif ($type === 'products_sales') {
            $grouped = $payments->groupBy('product_id');
            $rows[] = ['Product', 'Price', 'Payments', 'Revenue', 'Range'];
            foreach ($products as $product) {
                $group = $grouped->get($product->id, collect());
                $directTotal = '0.00';
                foreach ($group as $payment) {
                    $directTotal = Money::add($directTotal, (string) $payment->amount);
                }
                $orderTotal = (string) ($orderSales->get($product->id)->total ?? '0.00');
                $total = Money::add($directTotal, $orderTotal);
                $paymentsCount = $group->count() + (int) ($orderSales->get($product->id)->units ?? 0);
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    (string) $paymentsCount,
                    $total,
                    $rangeLabel,
                ];
            }
        } else {
            $rows[] = ['Product', 'Price', 'Active', 'Created at'];
            foreach ($products as $product) {
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    $product->is_active ? 'Active' : 'Inactive',
                    $product->created_at->toDateString(),
                ];
            }
        }

        $safeChartImage = null;
        if (is_string($chartImage) && str_starts_with($chartImage, 'data:image/png;base64,')) {
            $safeChartImage = $chartImage;
        }

        $pdf = Pdf::loadView('dashboard.products.export-pdf', [
            'seller' => $user,
            'currency' => config('services.paystack.currency', 'GHS'),
            'rangeLabel' => $rangeLabel,
            'type' => $type,
            'rows' => $rows,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $payments->count() + (int) $orderSales->sum('units'),
            'totalViews' => $views,
            'totalClicks' => $clicks,
            'conversion' => $conversion,
            'chartImage' => $safeChartImage,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('products-report.pdf');
    }

    public function create()
    {
        return view('dashboard.products.create');
    }

    public function store(CreateProductRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['is_active'] = $request->boolean('is_active');
        $data['status'] = $request->input('status', Product::STATUS_IN_STOCK);
        $data['stock_quantity'] = max(0, (int) $request->input('stock_quantity', 0));
        $data['low_stock_threshold'] = max(0, (int) $request->input('low_stock_threshold', 5));
        $data['slug'] = $this->generateProductSlug($data['name']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);
        app(InventoryService::class)->syncStatusAndAlert($product, $request->user(), false);

        // Pre-render a large OG image for WhatsApp previews.
        try {
            app(OgImageService::class)->generateProduct($product);
            $product->loadMissing('user.sellerProfile');
            if ($product->user?->sellerProfile) {
                app(OgImageService::class)->generateSeller($product->user->sellerProfile);
            }
        } catch (\Throwable $e) {
            // Ignore OG failures.
        }

        return redirect()->route('products.index')->with('status', 'product-created');
    }

    public function edit(Product $product)
    {
        $this->authorizeProduct($product);

        $stats = $this->buildStats($product);

        return view('dashboard.products.edit', [
            'product' => $product,
            'stats' => $stats,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function update(CreateProductRequest $request, Product $product)
    {
        $this->authorizeProduct($product);

        $data = $request->validated();
        // Quick-edit forms may not send is_active; preserve current value in that case.
        $data['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : $product->is_active;
        $data['status'] = $request->input('status', Product::STATUS_IN_STOCK);
        if ($request->has('stock_quantity')) {
            $data['stock_quantity'] = max(0, (int) $request->input('stock_quantity', $product->stock_quantity));
        }
        if ($request->has('low_stock_threshold')) {
            $data['low_stock_threshold'] = max(0, (int) $request->input('low_stock_threshold', $product->low_stock_threshold));
        }
        $data['slug'] = $this->generateProductSlug($data['name'], $product);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);
        app(InventoryService::class)->syncStatusAndAlert($product->fresh(), $request->user(), true);

        // Regenerate OG image after edits.
        try {
            $product->refresh()->loadMissing('user.sellerProfile');
            app(OgImageService::class)->generateProduct($product);
            if ($product->user?->sellerProfile) {
                app(OgImageService::class)->generateSeller($product->user->sellerProfile);
            }
        } catch (\Throwable $e) {
            // Ignore OG failures.
        }

        return redirect()->route('products.index')->with('status', 'product-updated');
    }

    public function destroy(Product $product)
    {
        $this->authorizeProduct($product);

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('products.index')->with('status', 'product-deleted');
    }

    private function authorizeProduct(Product $product): void
    {
        abort_unless($product->user_id === auth()->id(), 403);
    }

    private function resolveDateRange(string $range, ?string $start, ?string $end): array
    {
        $now = Carbon::now();

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            '28days' => [$now->copy()->subDays(27)->startOfDay(), $now->copy()->endOfDay()],
            '3months' => [$now->copy()->subMonths(3)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                $start ? Carbon::parse($start)->startOfDay() : null,
                $end ? Carbon::parse($end)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    private function rangeLabel(?Carbon $start, ?Carbon $end, string $range): string
    {
        if (! $start || ! $end) {
            return $range === 'all_time' ? 'All time' : 'Custom';
        }

        return $start->toDateString().' to '.$end->toDateString();
    }

    private function buildProductSeries(int $userId, string $range = '30days'): array
    {
        [$start, $end] = $this->resolveChartDateRange($range);

        $eventQuery = AnalyticsEvent::where('user_id', $userId)
            ->where('entity_type', 'product')
            ->when($start, fn ($q) => $q->whereBetween('created_at', [$start, $end]));
        $eventRows = $eventQuery
            ->selectRaw("date(created_at) as day, event_type, count(*) as total")
            ->groupBy('day', 'event_type')
            ->orderBy('day')
            ->get();

        $paymentQuery = Payment::where('user_id', $userId)
            ->whereNotNull('product_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->when($start, fn ($q) => $q->whereBetween('created_at', [$start, $end]));
        $paymentRows = $paymentQuery
            ->selectRaw("date(created_at) as day, count(*) as count, sum(amount) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();
        $orderRows = $this->paidOrderItemsQuery($userId, $start, $end)
            ->selectRaw("date(coalesce(orders.paid_at, orders.created_at)) as day, sum(order_items.quantity) as count, sum(order_items.line_total) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $eventMap = [];
        foreach ($eventRows as $row) {
            $eventMap[$row->day][$row->event_type] = (int) $row->total;
        }

        $paymentMap = [];
        foreach ($paymentRows as $row) {
            $paymentMap[$row->day] = [
                'count' => (int) $row->count,
                'total' => $row->total ? (string) $row->total : '0.00',
            ];
        }
        foreach ($orderRows as $row) {
            $existing = $paymentMap[$row->day] ?? ['count' => 0, 'total' => '0.00'];
            $paymentMap[$row->day] = [
                'count' => (int) $existing['count'] + (int) $row->count,
                'total' => Money::add((string) $existing['total'], (string) ($row->total ?: '0.00')),
            ];
        }

        $series = [];

        if ($start) {
            $period = \Carbon\CarbonPeriod::create($start, '1 day', $end);
            foreach ($period as $date) {
                $key = $date->format('Y-m-d');
                $views = $eventMap[$key][\App\Models\AnalyticsEvent::TYPE_PRODUCT_IMPRESSION] ?? 0;
                $clicks = $eventMap[$key][\App\Models\AnalyticsEvent::TYPE_PRODUCT_CLICK] ?? 0;
                $payments = $paymentMap[$key]['count'] ?? 0;
                $revenue = $paymentMap[$key]['total'] ?? '0.00';
                $conversion = $clicks > 0 ? round(($payments / $clicks) * 100, 2) : 0.0;

                $series[] = [
                    'day' => $key,
                    'label' => $date->format('M d'),
                    'views' => $views,
                    'clicks' => $clicks,
                    'payments' => $payments,
                    'revenue' => (float) $revenue,
                    'conversion' => $conversion,
                ];
            }
        } else {
            $allDays = collect($eventRows)->pluck('day')
                ->merge(collect($paymentRows)->pluck('day'))
                ->merge(collect($orderRows)->pluck('day'))
                ->unique()
                ->sort();
            foreach ($allDays as $key) {
                $views = $eventMap[$key][\App\Models\AnalyticsEvent::TYPE_PRODUCT_IMPRESSION] ?? 0;
                $clicks = $eventMap[$key][\App\Models\AnalyticsEvent::TYPE_PRODUCT_CLICK] ?? 0;
                $payments = $paymentMap[$key]['count'] ?? 0;
                $revenue = $paymentMap[$key]['total'] ?? '0.00';
                $conversion = $clicks > 0 ? round(($payments / $clicks) * 100, 2) : 0.0;

                $series[] = [
                    'day' => $key,
                    'label' => Carbon::parse($key)->format('M d'),
                    'views' => $views,
                    'clicks' => $clicks,
                    'payments' => $payments,
                    'revenue' => (float) $revenue,
                    'conversion' => $conversion,
                ];
            }
        }

        return $series;
    }

    private function resolveChartDateRange(string $range): array
    {
        $end = Carbon::now()->endOfDay();
        $start = match ($range) {
            '7days' => Carbon::now()->subDays(6)->startOfDay(),
            'all_time' => null,
            default => Carbon::now()->subDays(29)->startOfDay(),
        };

        return [$start, $end];
    }

    private function buildStats(Product $product): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $events = AnalyticsEvent::where('user_id', $product->user_id)
            ->where('entity_type', 'product')
            ->where('entity_id', (string) $product->id)
            ->whereBetween('created_at', [$start, $end]);

        $impressions = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)->count();
        $impressionsUnique = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)
            ->distinct('session_hash')->count('session_hash');
        $clicks = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)->count();
        $clicksUnique = (clone $events)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)
            ->distinct('session_hash')->count('session_hash');

        $payments = Payment::where('product_id', $product->id)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get();
        $orderSales = $this->paidOrderItemsQuery($product->user_id, $start, $end)
            ->where('order_items.product_id', $product->id)
            ->selectRaw('sum(order_items.quantity) as units, sum(order_items.line_total) as total')
            ->first();

        $paymentTotal = '0.00';
        foreach ($payments as $payment) {
            $paymentTotal = Money::add($paymentTotal, (string) $payment->amount);
        }
        $paymentTotal = Money::add($paymentTotal, (string) ($orderSales->total ?? '0.00'));
        $paymentsCount = $payments->count() + (int) ($orderSales->units ?? 0);

        return [
            'impressions' => $impressions,
            'impressionsUnique' => $impressionsUnique,
            'clicks' => $clicks,
            'clicksUnique' => $clicksUnique,
            'payments' => $paymentsCount,
            'paymentTotal' => $paymentTotal,
            'conversion' => $clicks > 0 ? round(($paymentsCount / $clicks) * 100, 1) : 0.0,
        ];
    }

    private function generateProductSlug(string $name, ?Product $product = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 1;

        while (Product::where('slug', $slug)
            ->when($product, fn ($query) => $query->where('id', '!=', $product->id))
            ->exists()) {
            $counter++;
            $slug = $base.'-'.$counter;
        }

        return $slug;
    }

    private function paidOrderItemsQuery(int $userId, ?Carbon $start = null, ?Carbon $end = null)
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.user_id', $userId)
            ->where('orders.payment_status', Payment::STATUS_SUCCESS)
            ->when($start && $end, function ($query) use ($start, $end) {
                $query->whereBetween('orders.paid_at', [$start, $end]);
            });
    }
}
