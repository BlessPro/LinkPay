<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProductRequest;
use App\Models\AnalyticsEvent;
use App\Models\Product;
use App\Models\Payment;
use App\Services\OgImageService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $products = $user->products()->latest()->paginate(10);
        $chartRange = $request->query('chart_range', '30days');
        $series = $this->buildProductSeries($user->id, $chartRange);

        $payments = Payment::where('user_id', $user->id)
            ->whereNotNull('product_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->get();

        $totalRevenue = '0.00';
        foreach ($payments as $payment) {
            $totalRevenue = Money::add($totalRevenue, (string) $payment->amount);
        }

        $customerKeys = $payments->map(function (Payment $payment) {
            return data_get($payment->raw_payload, 'customer.email')
                ?? data_get($payment->raw_payload, 'customer.phone');
        })->filter()->unique();

        $statusCounts = $user->products()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $topProducts = $payments->groupBy('product_id')
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
        $productLookup = $user->products()->get(['id', 'name'])->keyBy('id');
        $topList = collect($topProducts)->sortByDesc('total')->take(4);
        $maxTopTotal = max(1, (float) ($topList->map(fn ($row) => (float) $row['total'])->max() ?? 0));

        return view('dashboard.products.index', [
            'products' => $products,
            'currency' => config('services.paystack.currency', 'GHS'),
            'series' => $series,
            'chartRange' => $chartRange,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $payments->count(),
            'totalCustomers' => $customerKeys->count(),
            'statusCounts' => $statusCounts,
            'topProducts' => $topProducts,
            'productLookup' => $productLookup,
            'topList' => $topList,
            'maxTopTotal' => $maxTopTotal,
        ]);
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
            $payments = Payment::where('user_id', $user->id)
                ->whereNotNull('product_id')
                ->where('status', Payment::STATUS_SUCCESS)
                ->when($start && $end, fn ($q) => $q->whereBetween('created_at', [$start, $end]))
                ->get()
                ->groupBy('product_id');

            $rows[] = ['Product', 'Price', 'Payments', 'Revenue', 'Range'];
            foreach ($products as $product) {
                $group = $payments->get($product->id, collect());
                $total = '0.00';
                foreach ($group as $payment) {
                    $total = Money::add($total, (string) $payment->amount);
                }
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    (string) $group->count(),
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

        $totalRevenue = '0.00';
        foreach ($payments as $payment) {
            $totalRevenue = Money::add($totalRevenue, (string) $payment->amount);
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
                $total = '0.00';
                foreach ($group as $payment) {
                    $total = Money::add($total, (string) $payment->amount);
                }
                $rows[] = [
                    $product->name,
                    (string) $product->price,
                    (string) $group->count(),
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
            'totalOrders' => $payments->count(),
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
        $data['slug'] = $this->generateProductSlug($data['name']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

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
        $data['slug'] = $this->generateProductSlug($data['name'], $product);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

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
        $end = Carbon::now()->endOfDay();
        $start = match ($range) {
            '7days' => Carbon::now()->subDays(6)->startOfDay(),
            'all_time' => null,
            default => Carbon::now()->subDays(29)->startOfDay(),
        };

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
            $allDays = collect($eventRows)->pluck('day')->merge(collect($paymentRows)->pluck('day'))->unique()->sort();
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

        $paymentTotal = '0.00';
        foreach ($payments as $payment) {
            $paymentTotal = Money::add($paymentTotal, (string) $payment->amount);
        }

        return [
            'impressions' => $impressions,
            'impressionsUnique' => $impressionsUnique,
            'clicks' => $clicks,
            'clicksUnique' => $clicksUnique,
            'payments' => $payments->count(),
            'paymentTotal' => $paymentTotal,
            'conversion' => $clicks > 0 ? round(($payments->count() / $clicks) * 100, 1) : 0.0,
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
}
