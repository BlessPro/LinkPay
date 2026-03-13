<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GoalsTargetController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currency = config('services.paystack.currency', 'GHS');

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

        $thisMonthRevenue = $this->sumSuccessfulPayments($user->id, $monthStart, $monthEnd);
        $lastMonthRevenue = $this->sumSuccessfulPayments($user->id, $lastMonthStart, $lastMonthEnd);
        $revenueGrowth = $this->percentGrowth($thisMonthRevenue, $lastMonthRevenue);

        $revenueTarget = round($lastMonthRevenue * 1.2, 2);
        $revenueProgress = $this->progressPercent($thisMonthRevenue, $revenueTarget);

        $ordersThisMonth = $this->countSuccessfulOrders($user->id, $monthStart, $monthEnd);
        $ordersLastMonth = $this->countSuccessfulOrders($user->id, $lastMonthStart, $lastMonthEnd);
        $ordersGrowth = $this->percentGrowth((float) $ordersThisMonth, (float) $ordersLastMonth);

        $weeksInMonth = (int) ceil($monthStart->daysInMonth / 7);
        $avgWeeklyOrderRate = $this->averageWeeklyOrderRate($user->id, 8);
        $ordersTarget = (int) ceil($avgWeeklyOrderRate * $weeksInMonth);

        $productPerformance = $this->buildProductPerformance($user->id, $monthStart, $monthEnd);
        $salesTrend = $this->buildWeeklySalesTrend($user->id, $monthStart, $monthEnd);
        $weekdayPerformance = $this->buildWeekdayPerformance($user->id, $monthStart, $monthEnd);
        $customerInsights = $this->buildCustomerInsights($user->id, $monthStart, $monthEnd);

        $topProduct = $productPerformance['topProducts'][0] ?? null;
        $bestLink = collect($productPerformance['linkPerformance'])->sortByDesc('conversion')->first();

        return view('dashboard.goals.index', [
            'currency' => $currency,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'thisMonthRevenue' => $thisMonthRevenue,
            'lastMonthRevenue' => $lastMonthRevenue,
            'revenueGrowth' => $revenueGrowth,
            'revenueTarget' => $revenueTarget,
            'revenueProgress' => $revenueProgress,
            'ordersThisMonth' => $ordersThisMonth,
            'ordersLastMonth' => $ordersLastMonth,
            'ordersGrowth' => $ordersGrowth,
            'ordersTarget' => $ordersTarget,
            'topProducts' => $productPerformance['topProducts'],
            'weakProducts' => $productPerformance['weakProducts'],
            'weeklySales' => $salesTrend,
            'bestSalesDay' => $weekdayPerformance['best'],
            'worstSalesDay' => $weekdayPerformance['worst'],
            'customerInsights' => $customerInsights,
            'linkPerformance' => $productPerformance['linkPerformance'],
            'topTrafficProducts' => $productPerformance['topTrafficProducts'],
            'businessSummary' => [
                'revenueGrowth' => $revenueGrowth,
                'ordersGrowth' => $ordersGrowth,
                'topProduct' => $topProduct['name'] ?? 'No sales yet',
                'topProductShare' => $topProduct['share'] ?? 0,
                'topDay' => $weekdayPerformance['best']['day'] ?? 'N/A',
                'bestLink' => $bestLink['link'] ?? null,
                'bestLinkName' => $bestLink['name'] ?? null,
                'bestLinkConversion' => $bestLink['conversion'] ?? 0,
            ],
        ]);
    }

    private function sumSuccessfulPayments(int $userId, Carbon $start, Carbon $end): float
    {
        return (float) Payment::query()
            ->where('user_id', $userId)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }

    private function countSuccessfulOrders(int $userId, Carbon $start, Carbon $end): int
    {
        return Order::query()
            ->where('user_id', $userId)
            ->where('payment_status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    private function averageWeeklyOrderRate(int $userId, int $weeks): float
    {
        $start = now()->subWeeks($weeks)->startOfDay();
        $end = now()->endOfDay();

        $count = Order::query()
            ->where('user_id', $userId)
            ->where('payment_status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return $weeks > 0 ? $count / $weeks : 0.0;
    }

    private function buildProductPerformance(int $userId, Carbon $start, Carbon $end): array
    {
        $products = Product::query()
            ->where('user_id', $userId)
            ->get(['id', 'name', 'slug'])
            ->keyBy('id');

        $viewsRows = AnalyticsEvent::query()
            ->where('user_id', $userId)
            ->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)
            ->where('entity_type', 'product')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('entity_id, COUNT(*) as total')
            ->groupBy('entity_id')
            ->get();

        $clickRows = AnalyticsEvent::query()
            ->where('user_id', $userId)
            ->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)
            ->where('entity_type', 'product')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('entity_id, COUNT(*) as total')
            ->groupBy('entity_id')
            ->get();

        $stats = [];

        $ensure = function (string $key, string $name, ?string $slug) use (&$stats): void {
            if (isset($stats[$key])) {
                return;
            }

            $stats[$key] = [
                'key' => $key,
                'name' => $name,
                'slug' => $slug,
                'views' => 0,
                'clicks' => 0,
                'units' => 0,
                'purchases' => 0,
                'revenue' => 0.0,
                'share' => 0.0,
                'conversion' => 0.0,
                'link' => $slug ? '/p/'.$slug : null,
            ];
        };

        foreach ($products as $product) {
            $ensure((string) $product->id, $product->name, $product->slug);
        }

        foreach ($viewsRows as $row) {
            $entityId = (string) $row->entity_id;
            $product = $products->get((int) $entityId);
            $ensure($entityId, $product?->name ?? 'Product #'.$entityId, $product?->slug);
            $stats[$entityId]['views'] = (int) $row->total;
        }

        foreach ($clickRows as $row) {
            $entityId = (string) $row->entity_id;
            $product = $products->get((int) $entityId);
            $ensure($entityId, $product?->name ?? 'Product #'.$entityId, $product?->slug);
            $stats[$entityId]['clicks'] = (int) $row->total;
        }

        $orderItems = OrderItem::query()
            ->whereHas('order', function ($query) use ($userId, $start, $end) {
                $query->where('user_id', $userId)
                    ->where('payment_status', Payment::STATUS_SUCCESS)
                    ->whereBetween('created_at', [$start, $end]);
            })
            ->selectRaw('product_id, product_name, SUM(quantity) as units, SUM(line_total) as revenue')
            ->groupBy('product_id', 'product_name')
            ->get();

        foreach ($orderItems as $item) {
            $productId = $item->product_id ? (string) $item->product_id : null;
            $product = $productId ? $products->get((int) $productId) : null;
            $key = $productId ?: 'name:'.strtolower((string) $item->product_name);
            $ensure($key, $product?->name ?? (string) $item->product_name, $product?->slug);

            $units = (int) ($item->units ?? 0);
            $stats[$key]['units'] += $units;
            $stats[$key]['purchases'] += $units;
            $stats[$key]['revenue'] += (float) ($item->revenue ?? 0);
        }

        $directProductPayments = Payment::query()
            ->where('user_id', $userId)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereNull('order_id')
            ->whereNotNull('product_id')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('product_id, COUNT(*) as units, SUM(amount) as revenue')
            ->groupBy('product_id')
            ->get();

        foreach ($directProductPayments as $payment) {
            $productId = (string) $payment->product_id;
            $product = $products->get((int) $productId);
            $ensure($productId, $product?->name ?? 'Product #'.$productId, $product?->slug);

            $units = (int) ($payment->units ?? 0);
            $stats[$productId]['units'] += $units;
            $stats[$productId]['purchases'] += $units;
            $stats[$productId]['revenue'] += (float) ($payment->revenue ?? 0);
        }

        $all = collect(array_values($stats));
        $totalRevenue = (float) $all->sum('revenue');

        $all = $all->map(function (array $row) use ($totalRevenue) {
            $views = (int) $row['views'];
            $clicks = (int) $row['clicks'];
            $purchases = (int) $row['purchases'];

            $row['share'] = $totalRevenue > 0 ? round(($row['revenue'] / $totalRevenue) * 100, 1) : 0.0;
            $row['conversion'] = $views > 0 ? round(($purchases / $views) * 100, 1) : 0.0;
            $row['link_conversion'] = $clicks > 0 ? round(($purchases / $clicks) * 100, 1) : 0.0;

            return $row;
        });

        return [
            'topProducts' => $all
                ->filter(fn (array $row) => $row['revenue'] > 0)
                ->sortByDesc('revenue')
                ->values()
                ->take(8)
                ->all(),
            'weakProducts' => $all
                ->filter(fn (array $row) => $row['views'] > 0 && $row['conversion'] < 3.0)
                ->sortBy([
                    ['conversion', 'asc'],
                    ['views', 'desc'],
                ])
                ->values()
                ->take(8)
                ->all(),
            'linkPerformance' => $all
                ->filter(fn (array $row) => $row['clicks'] > 0)
                ->sortByDesc('link_conversion')
                ->values()
                ->take(10)
                ->map(function (array $row) {
                    return [
                        'name' => $row['name'],
                        'link' => $row['link'] ?? '/p',
                        'clicks' => $row['clicks'],
                        'purchases' => $row['purchases'],
                        'conversion' => $row['link_conversion'],
                    ];
                })
                ->all(),
            'topTrafficProducts' => $all
                ->filter(fn (array $row) => $row['views'] > 0)
                ->sortByDesc('views')
                ->values()
                ->take(8)
                ->map(fn (array $row) => [
                    'name' => $row['name'],
                    'views' => $row['views'],
                ])
                ->all(),
        ];
    }

    private function buildWeeklySalesTrend(int $userId, Carbon $start, Carbon $end): array
    {
        $rows = Payment::query()
            ->where('user_id', $userId)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get(['amount', 'created_at']);

        $weeksInMonth = (int) ceil($start->daysInMonth / 7);
        $buckets = [];
        for ($i = 1; $i <= $weeksInMonth; $i++) {
            $buckets[$i] = 0.0;
        }

        foreach ($rows as $payment) {
            $day = (int) $payment->created_at->day;
            $index = (int) floor(($day - 1) / 7) + 1;
            $buckets[$index] = ($buckets[$index] ?? 0.0) + (float) $payment->amount;
        }

        $trend = [];
        foreach ($buckets as $week => $revenue) {
            $trend[] = [
                'week' => 'Week '.$week,
                'revenue' => round($revenue, 2),
            ];
        }

        return $trend;
    }

    private function buildWeekdayPerformance(int $userId, Carbon $start, Carbon $end): array
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->where('payment_status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at']);

        $days = [
            'Monday' => 0,
            'Tuesday' => 0,
            'Wednesday' => 0,
            'Thursday' => 0,
            'Friday' => 0,
            'Saturday' => 0,
            'Sunday' => 0,
        ];

        foreach ($orders as $order) {
            $day = $order->created_at->format('l');
            $days[$day] = ($days[$day] ?? 0) + 1;
        }

        $nonZero = collect($days)->filter(fn (int $count) => $count > 0);
        if ($nonZero->isEmpty()) {
            return [
                'best' => ['day' => 'N/A', 'count' => 0],
                'worst' => ['day' => 'N/A', 'count' => 0],
            ];
        }

        return [
            'best' => [
                'day' => (string) $nonZero->sortDesc()->keys()->first(),
                'count' => (int) $nonZero->max(),
            ],
            'worst' => [
                'day' => (string) $nonZero->sort()->keys()->first(),
                'count' => (int) $nonZero->min(),
            ],
        ];
    }

    private function buildCustomerInsights(int $userId, Carbon $start, Carbon $end): array
    {
        $current = Payment::query()
            ->with('order:id,customer_phone')
            ->where('user_id', $userId)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $previous = Payment::query()
            ->with('order:id,customer_phone')
            ->where('user_id', $userId)
            ->where('status', Payment::STATUS_SUCCESS)
            ->where('created_at', '<', $start)
            ->get();

        $currentKeys = $current
            ->map(fn (Payment $payment) => $this->customerKey($payment))
            ->filter()
            ->unique()
            ->values();

        $previousKeys = $previous
            ->map(fn (Payment $payment) => $this->customerKey($payment))
            ->filter()
            ->unique()
            ->values();

        $returning = $currentKeys->filter(fn (string $key) => $previousKeys->contains($key))->count();
        $new = $currentKeys->count() - $returning;
        $total = $currentKeys->count();

        return [
            'new' => max(0, $new),
            'returning' => max(0, $returning),
            'total' => $total,
            'returningRate' => $total > 0 ? round(($returning / $total) * 100, 1) : 0.0,
        ];
    }

    private function customerKey(Payment $payment): ?string
    {
        $phone = $payment->order?->customer_phone
            ?? data_get($payment->raw_payload, 'customer.phone')
            ?? data_get($payment->raw_payload, 'metadata.customer.phone')
            ?? data_get($payment->raw_payload, 'metadata.phone');

        if (is_string($phone) && trim($phone) !== '') {
            return 'phone:'.preg_replace('/\s+/', '', trim($phone));
        }

        $email = data_get($payment->raw_payload, 'customer.email')
            ?? data_get($payment->raw_payload, 'metadata.customer.email')
            ?? data_get($payment->raw_payload, 'metadata.email');

        if (is_string($email) && trim($email) !== '') {
            return 'email:'.strtolower(trim($email));
        }

        return null;
    }

    private function percentGrowth(float $current, float $previous): float
    {
        if ($previous <= 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function progressPercent(float $current, float $target): float
    {
        if ($target <= 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round(min(100, ($current / $target) * 100), 1);
    }
}
