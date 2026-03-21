<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsEvent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsightsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currency = config('services.paystack.currency', 'GHS');

        $range = $request->query('range', '7');
        if ($range !== 'custom' && ctype_digit((string) $range)) {
            $days = max(1, (int) $range);
            $start = Carbon::now()->subDays($days - 1)->startOfDay();
            $end = Carbon::now()->endOfDay();
        } else {
            $start = $request->query('start_date')
                ? Carbon::parse($request->query('start_date'))->startOfDay()
                : Carbon::now()->subDays(6)->startOfDay();
            $end = $request->query('end_date')
                ? Carbon::parse($request->query('end_date'))->endOfDay()
                : Carbon::now()->endOfDay();
        }

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $eventsQuery = AnalyticsEvent::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end]);

        $summary = [
            'listingViews' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_LISTING_VIEW)->count(),
            'listingViewsUnique' => $this->uniqueSessions($eventsQuery, AnalyticsEvent::TYPE_LISTING_VIEW),
            'productImpressions' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)->count(),
            'productClicks' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)->count(),
            'addToCart' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_ADD_TO_CART)->count(),
            'checkoutStarted' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_CHECKOUT_STARTED)->count(),
            'invoiceViews' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_INVOICE_VIEW)->count(),
            'invoiceClicks' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_INVOICE_CLICK)->count(),
        ];

        $paymentsQuery = $user->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end]);
        $paymentsTotal = $this->sumPayments(clone $paymentsQuery);
        $paymentsCount = (clone $paymentsQuery)->count();
        $funnel = $this->buildFunnel($summary, $paymentsCount);
        $funnelHints = $this->buildFunnelHints($funnel);

        $deviceBreakdown = (clone $eventsQuery)
            ->select('device_type', DB::raw('count(*) as total'))
            ->groupBy('device_type')
            ->orderByDesc('total')
            ->get();

        $referrers = (clone $eventsQuery)
            ->whereNotNull('referrer_host')
            ->select('referrer_host', DB::raw('count(*) as total'))
            ->groupBy('referrer_host')
            ->orderByDesc('total')
            ->take(8)
            ->get();

        $utmCampaigns = (clone $eventsQuery)
            ->whereNotNull('utm_source')
            ->select('utm_source', 'utm_medium', 'utm_campaign', DB::raw('count(*) as total'))
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('total')
            ->take(8)
            ->get();
        $sourceFunnel = $this->buildSourceFunnel($eventsQuery);

        $customerInsights = $this->topCustomers(clone $paymentsQuery);

        $productStats = $this->buildProductStats($user, $start, $end);
        $productFunnel = $this->buildProductFunnel($eventsQuery, $user, $start, $end);
        $invoiceStats = $this->buildInvoiceStats($user, $start, $end);

        $dailySeries = $this->buildDailySeries($user, $start, $end);

        return view('dashboard.insights.index', [
            'currency' => $currency,
            'range' => $range,
            'start' => $start,
            'end' => $end,
            'summary' => $summary,
            'funnel' => $funnel,
            'funnelHints' => $funnelHints,
            'paymentsTotal' => $paymentsTotal,
            'paymentsCount' => $paymentsCount,
            'deviceBreakdown' => $deviceBreakdown,
            'referrers' => $referrers,
            'utmCampaigns' => $utmCampaigns,
            'sourceFunnel' => $sourceFunnel,
            'customerInsights' => $customerInsights,
            'productStats' => $productStats,
            'productFunnel' => $productFunnel,
            'invoiceStats' => $invoiceStats,
            'dailySeries' => $dailySeries,
        ]);
    }

    private function buildFunnel(array $summary, int $paymentsCount): array
    {
        $stages = [
            ['key' => 'listing_view', 'label' => 'Views', 'value' => (int) ($summary['listingViews'] ?? 0)],
            ['key' => 'product_click', 'label' => 'Clicks', 'value' => (int) ($summary['productClicks'] ?? 0)],
            ['key' => 'add_to_cart', 'label' => 'Add to Cart', 'value' => (int) ($summary['addToCart'] ?? 0)],
            ['key' => 'checkout_started', 'label' => 'Checkout', 'value' => (int) ($summary['checkoutStarted'] ?? 0)],
            ['key' => 'paid', 'label' => 'Paid', 'value' => $paymentsCount],
        ];

        $dropoffs = [];
        for ($i = 1; $i < count($stages); $i++) {
            $from = $stages[$i - 1];
            $to = $stages[$i];
            $fromValue = (int) $from['value'];
            $toValue = (int) $to['value'];
            $dropPct = $fromValue > 0 ? round((($fromValue - $toValue) / $fromValue) * 100, 1) : 0.0;
            $convPct = $fromValue > 0 ? round(($toValue / $fromValue) * 100, 1) : 0.0;

            $dropoffs[] = [
                'from' => $from['label'],
                'to' => $to['label'],
                'from_value' => $fromValue,
                'to_value' => $toValue,
                'dropoff_pct' => max(0.0, $dropPct),
                'conversion_pct' => max(0.0, $convPct),
            ];
        }

        $overallConversion = (int) ($stages[0]['value'] ?? 0) > 0
            ? round(($paymentsCount / (int) $stages[0]['value']) * 100, 1)
            : 0.0;

        return [
            'stages' => $stages,
            'dropoffs' => $dropoffs,
            'overall_conversion' => $overallConversion,
        ];
    }

    private function buildFunnelHints(array $funnel): array
    {
        $hints = [];
        $stages = collect($funnel['stages'] ?? [])->keyBy('key');
        $dropoffs = collect($funnel['dropoffs'] ?? []);

        $views = (int) ($stages->get('listing_view')['value'] ?? 0);
        $clicks = (int) ($stages->get('product_click')['value'] ?? 0);
        $cartAdds = (int) ($stages->get('add_to_cart')['value'] ?? 0);
        $checkout = (int) ($stages->get('checkout_started')['value'] ?? 0);
        $paid = (int) ($stages->get('paid')['value'] ?? 0);

        $viewToClick = $views > 0 ? ($clicks / $views) * 100 : 0.0;
        $clickToCart = $clicks > 0 ? ($cartAdds / $clicks) * 100 : 0.0;
        $cartToCheckout = $cartAdds > 0 ? ($checkout / $cartAdds) * 100 : 0.0;
        $checkoutToPaid = $checkout > 0 ? ($paid / $checkout) * 100 : 0.0;

        if ($views >= 30 && $viewToClick < 12) {
            $hints[] = [
                'level' => 'warning',
                'title' => 'Low click-through from listing',
                'body' => 'Many views are not becoming product clicks. Improve first product image, title clarity, and opening price anchor.',
            ];
        }

        if ($clicks >= 20 && $clickToCart < 20) {
            $hints[] = [
                'level' => 'warning',
                'title' => 'Product interest but weak add-to-cart',
                'body' => 'Clicks are not converting to cart additions. Revisit product descriptions, trust signals, and delivery info placement.',
            ];
        }

        if ($cartAdds >= 10 && $cartToCheckout < 40) {
            $hints[] = [
                'level' => 'warning',
                'title' => 'Cart friction detected',
                'body' => 'Cart additions are not moving to checkout. Simplify cart UI and surface checkout CTA earlier.',
            ];
        }

        if ($checkout >= 8 && $checkoutToPaid < 55) {
            $hints[] = [
                'level' => 'critical',
                'title' => 'Checkout-to-paid drop is high',
                'body' => 'Users start checkout but many do not pay. Verify payment flow reliability and payment instructions clarity.',
            ];
        }

        if (($funnel['overall_conversion'] ?? 0) >= 25 && $paid >= 10) {
            $hints[] = [
                'level' => 'good',
                'title' => 'Strong overall conversion',
                'body' => 'Current flow is performing well. Scale traffic sources that already bring high-intent visitors.',
            ];
        }

        if (empty($hints)) {
            $largestDrop = $dropoffs->sortByDesc('dropoff_pct')->first();
            if ($largestDrop) {
                $hints[] = [
                    'level' => 'info',
                    'title' => 'Top optimization target',
                    'body' => $largestDrop['from'].' -> '.$largestDrop['to'].' has the biggest drop-off ('.number_format((float) $largestDrop['dropoff_pct'], 1).'%). Focus improvements there first.',
                ];
            } else {
                $hints[] = [
                    'level' => 'info',
                    'title' => 'Not enough funnel data yet',
                    'body' => 'Collect more events to unlock stronger recommendations. Keep sharing product links and monitor next 7 days.',
                ];
            }
        }

        return $hints;
    }

    private function uniqueSessions($query, string $eventType): int
    {
        return (clone $query)
            ->where('event_type', $eventType)
            ->distinct('session_hash')
            ->count('session_hash');
    }

    private function sumPayments($query): string
    {
        $sum = '0.00';
        $query->get()->each(function (Payment $payment) use (&$sum) {
            $sum = Money::add($sum, (string) $payment->amount);
        });

        return $sum;
    }

    private function topCustomers($paymentsQuery): array
    {
        $customers = [];
        $paymentsQuery->get()->each(function (Payment $payment) use (&$customers) {
            $email = data_get($payment->raw_payload, 'customer.email')
                ?? data_get($payment->raw_payload, 'metadata.email');
            if (! $email) {
                return;
            }
            if (! isset($customers[$email])) {
                $customers[$email] = ['email' => $email, 'count' => 0, 'total' => '0.00'];
            }
            $customers[$email]['count']++;
            $customers[$email]['total'] = Money::add($customers[$email]['total'], (string) $payment->amount);
        });

        return collect($customers)
            ->sortByDesc(fn ($row) => (float) $row['total'])
            ->take(8)
            ->values()
            ->all();
    }

    private function buildProductStats($user, Carbon $start, Carbon $end): array
    {
        $events = AnalyticsEvent::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('entity_type', 'product')
            ->get()
            ->groupBy('entity_id');

        $products = Product::where('user_id', $user->id)->get()->keyBy('id');

        $payments = Payment::where('user_id', $user->id)
            ->whereNotNull('product_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy('product_id');

        $stats = [];
        foreach ($products as $productId => $product) {
            $eventGroup = $events->get((string) $productId, collect());
            $impressions = $eventGroup->where('event_type', AnalyticsEvent::TYPE_PRODUCT_IMPRESSION)->count();
            $clicks = $eventGroup->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)->count();
            $paymentGroup = $payments->get($productId, collect());
            $paymentCount = $paymentGroup->count();
            $paymentTotal = '0.00';
            foreach ($paymentGroup as $payment) {
                $paymentTotal = Money::add($paymentTotal, (string) $payment->amount);
            }

            $stats[] = [
                'name' => $product->name,
                'impressions' => $impressions,
                'clicks' => $clicks,
                'payments' => $paymentCount,
                'total' => $paymentTotal,
                'conversion' => $clicks > 0 ? round(($paymentCount / $clicks) * 100, 1) : 0.0,
            ];
        }

        return collect($stats)->sortByDesc('total')->values()->all();
    }

    private function buildInvoiceStats($user, Carbon $start, Carbon $end): array
    {
        $events = AnalyticsEvent::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->where('entity_type', 'invoice')
            ->get()
            ->groupBy('entity_id');

        $invoices = Invoice::where('user_id', $user->id)->get()->keyBy('id');

        $payments = Payment::where('user_id', $user->id)
            ->whereNotNull('invoice_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->groupBy('invoice_id');

        $stats = [];
        foreach ($invoices as $invoiceId => $invoice) {
            $eventGroup = $events->get((string) $invoiceId, collect());
            $views = $eventGroup->where('event_type', AnalyticsEvent::TYPE_INVOICE_VIEW)->count();
            $clicks = $eventGroup->where('event_type', AnalyticsEvent::TYPE_INVOICE_CLICK)->count();
            $paymentGroup = $payments->get($invoiceId, collect());
            $paymentCount = $paymentGroup->count();
            $paymentTotal = '0.00';
            foreach ($paymentGroup as $payment) {
                $paymentTotal = Money::add($paymentTotal, (string) $payment->amount);
            }

            $stats[] = [
                'title' => $invoice->title,
                'views' => $views,
                'clicks' => $clicks,
                'payments' => $paymentCount,
                'total' => $paymentTotal,
                'conversion' => $views > 0 ? round(($paymentCount / $views) * 100, 1) : 0.0,
            ];
        }

        return collect($stats)->sortByDesc('total')->values()->all();
    }

    private function buildDailySeries($user, Carbon $start, Carbon $end): array
    {
        $eventRows = AnalyticsEvent::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("date(created_at) as day, event_type, count(*) as total")
            ->groupBy('day', 'event_type')
            ->orderBy('day')
            ->get();

        $paymentRows = Payment::where('user_id', $user->id)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
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
        $period = CarbonPeriod::create($start, '1 day', $end);
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $series[] = [
                'day' => $key,
                'label' => $date->format('M d'),
                'listingViews' => $eventMap[$key][AnalyticsEvent::TYPE_LISTING_VIEW] ?? 0,
                'productClicks' => $eventMap[$key][AnalyticsEvent::TYPE_PRODUCT_CLICK] ?? 0,
                'invoiceClicks' => $eventMap[$key][AnalyticsEvent::TYPE_INVOICE_CLICK] ?? 0,
                'payments' => $paymentMap[$key]['count'] ?? 0,
                'revenue' => $paymentMap[$key]['total'] ?? '0.00',
            ];
        }

        return $series;
    }

    private function buildProductFunnel($eventsQuery, $user, Carbon $start, Carbon $end): array
    {
        $eventRows = (clone $eventsQuery)
            ->where('entity_type', 'product')
            ->whereIn('event_type', [
                AnalyticsEvent::TYPE_PRODUCT_CLICK,
                AnalyticsEvent::TYPE_ADD_TO_CART,
                AnalyticsEvent::TYPE_CHECKOUT_STARTED,
            ])
            ->get()
            ->groupBy('entity_id');

        $products = Product::where('user_id', $user->id)
            ->get(['id', 'name'])
            ->keyBy('id');

        $payments = Payment::query()
            ->where('user_id', $user->id)
            ->whereNotNull('product_id')
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('product_id, count(*) as total')
            ->groupBy('product_id')
            ->pluck('total', 'product_id');

        return $products->map(function ($product) use ($eventRows, $payments) {
            $group = $eventRows->get((string) $product->id, collect());
            $clicks = $group->where('event_type', AnalyticsEvent::TYPE_PRODUCT_CLICK)->count();
            $cartAdds = $group->where('event_type', AnalyticsEvent::TYPE_ADD_TO_CART)->count();
            $checkout = $group->where('event_type', AnalyticsEvent::TYPE_CHECKOUT_STARTED)->count();
            $paid = (int) ($payments[$product->id] ?? 0);

            return [
                'name' => $product->name,
                'clicks' => $clicks,
                'add_to_cart' => $cartAdds,
                'checkout_started' => $checkout,
                'paid' => $paid,
                'conversion' => $clicks > 0 ? round(($paid / $clicks) * 100, 1) : 0.0,
            ];
        })
            ->sortByDesc('paid')
            ->take(10)
            ->values()
            ->all();
    }

    private function buildSourceFunnel($eventsQuery): array
    {
        return (clone $eventsQuery)
            ->whereNotNull('utm_source')
            ->whereIn('event_type', [
                AnalyticsEvent::TYPE_LISTING_VIEW,
                AnalyticsEvent::TYPE_PRODUCT_CLICK,
                AnalyticsEvent::TYPE_ADD_TO_CART,
                AnalyticsEvent::TYPE_CHECKOUT_STARTED,
            ])
            ->selectRaw("
                utm_source,
                SUM(CASE WHEN event_type = ? THEN 1 ELSE 0 END) as views,
                SUM(CASE WHEN event_type = ? THEN 1 ELSE 0 END) as clicks,
                SUM(CASE WHEN event_type = ? THEN 1 ELSE 0 END) as add_to_cart,
                SUM(CASE WHEN event_type = ? THEN 1 ELSE 0 END) as checkout_started
            ", [
                AnalyticsEvent::TYPE_LISTING_VIEW,
                AnalyticsEvent::TYPE_PRODUCT_CLICK,
                AnalyticsEvent::TYPE_ADD_TO_CART,
                AnalyticsEvent::TYPE_CHECKOUT_STARTED,
            ])
            ->groupBy('utm_source')
            ->orderByDesc('views')
            ->take(10)
            ->get()
            ->map(function ($row) {
                $views = (int) $row->views;
                $clicks = (int) $row->clicks;

                return [
                    'utm_source' => $row->utm_source,
                    'views' => $views,
                    'clicks' => $clicks,
                    'add_to_cart' => (int) $row->add_to_cart,
                    'checkout_started' => (int) $row->checkout_started,
                    'ctr' => $views > 0 ? round(($clicks / $views) * 100, 1) : 0.0,
                ];
            })
            ->all();
    }
}
