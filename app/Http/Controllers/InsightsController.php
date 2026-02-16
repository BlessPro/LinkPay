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
            'invoiceViews' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_INVOICE_VIEW)->count(),
            'invoiceClicks' => (clone $eventsQuery)->where('event_type', AnalyticsEvent::TYPE_INVOICE_CLICK)->count(),
        ];

        $paymentsQuery = $user->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end]);
        $paymentsTotal = $this->sumPayments(clone $paymentsQuery);
        $paymentsCount = (clone $paymentsQuery)->count();

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

        $customerInsights = $this->topCustomers(clone $paymentsQuery);

        $productStats = $this->buildProductStats($user, $start, $end);
        $invoiceStats = $this->buildInvoiceStats($user, $start, $end);

        $dailySeries = $this->buildDailySeries($user, $start, $end);

        return view('dashboard.insights.index', [
            'currency' => $currency,
            'range' => $range,
            'start' => $start,
            'end' => $end,
            'summary' => $summary,
            'paymentsTotal' => $paymentsTotal,
            'paymentsCount' => $paymentsCount,
            'deviceBreakdown' => $deviceBreakdown,
            'referrers' => $referrers,
            'utmCampaigns' => $utmCampaigns,
            'customerInsights' => $customerInsights,
            'productStats' => $productStats,
            'invoiceStats' => $invoiceStats,
            'dailySeries' => $dailySeries,
        ]);
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
}
