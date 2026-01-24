<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalSellers = User::has('sellerProfile')->count();
        $connectedSellers = SellerProfile::whereNotNull('paystack_subaccount_code')->count();
        $totalProducts = Product::count();
        $totalInvoices = Invoice::count();
        $totalPayments = Payment::where('status', Payment::STATUS_SUCCESS)->count();
        $totalReceived = $this->sumPayments(Payment::where('status', Payment::STATUS_SUCCESS));

        $platformFee = (string) config('services.paystack.platform_fee_flat', '0');
        $platformFeesTotal = Money::compare($platformFee, '0.00') === 1
            ? Money::multiply($platformFee, $totalPayments)
            : '0.00';

        $pendingInvoices = Invoice::where('status', Invoice::STATUS_PENDING)->count();
        $partialInvoices = Invoice::where('status', Invoice::STATUS_PARTIAL)->count();
        $failedPayments = Payment::where('status', Payment::STATUS_FAILED)->count();

        $recentPayments = Payment::where('status', Payment::STATUS_SUCCESS)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $recentInvoices = Invoice::with('user')
            ->latest()
            ->take(10)
            ->get();

        $pendingPayments = Payment::where('status', Payment::STATUS_PENDING)
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $dailySeries = $this->buildDailySeries();
        $weeklySeries = $this->buildWeeklySeries();
        $monthlySeries = $this->buildMonthlySeries();

        $compare7 = $this->buildComparison(7);
        $compare30 = $this->buildComparison(30);

        $sellers = User::with('sellerProfile')
            ->withCount(['products', 'invoices'])
            ->withSum([
                'payments as total_received' => function ($query) {
                    $query->where('status', Payment::STATUS_SUCCESS);
                },
            ], 'amount')
            ->orderByDesc('created_at')
            ->paginate(12);

        return view('admin.dashboard', [
            'totalSellers' => $totalSellers,
            'connectedSellers' => $connectedSellers,
            'totalProducts' => $totalProducts,
            'totalInvoices' => $totalInvoices,
            'totalPayments' => $totalPayments,
            'totalReceived' => $totalReceived,
            'platformFeesTotal' => $platformFeesTotal,
            'pendingInvoices' => $pendingInvoices,
            'partialInvoices' => $partialInvoices,
            'failedPayments' => $failedPayments,
            'recentPayments' => $recentPayments,
            'recentInvoices' => $recentInvoices,
            'pendingPayments' => $pendingPayments,
            'dailySeries' => $dailySeries,
            'weeklySeries' => $weeklySeries,
            'monthlySeries' => $monthlySeries,
            'compare7' => $compare7,
            'compare30' => $compare30,
            'sellers' => $sellers,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    private function sumPayments($query): string
    {
        $sum = '0.00';
        $query->get()->each(function (Payment $payment) use (&$sum) {
            $sum = Money::add($sum, (string) $payment->amount);
        });

        return $sum;
    }

    private function buildDailySeries(): array
    {
        $start = Carbon::now()->startOfDay()->subDays(13);
        $end = Carbon::now()->endOfDay();

        return $this->buildSeries(
            $start,
            $end,
            '1 day',
            fn (Carbon $date) => $date->format('Y-m-d'),
            fn (Carbon $date) => $date->format('M d')
        );
    }

    private function buildWeeklySeries(): array
    {
        $start = Carbon::now()->startOfWeek()->subWeeks(7);
        $end = Carbon::now()->endOfWeek();

        return $this->buildSeries(
            $start,
            $end,
            '1 week',
            fn (Carbon $date) => $date->format('o-W'),
            fn (Carbon $date) => 'Wk '.$date->format('W')
        );
    }

    private function buildMonthlySeries(): array
    {
        $start = Carbon::now()->startOfMonth()->subMonths(5);
        $end = Carbon::now()->endOfMonth();

        return $this->buildSeries(
            $start,
            $end,
            '1 month',
            fn (Carbon $date) => $date->format('Y-m'),
            fn (Carbon $date) => $date->format('M Y')
        );
    }

    private function buildSeries(Carbon $start, Carbon $end, string $step, callable $keyFn, callable $labelFn): array
    {
        $payments = Payment::where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $buckets = [];
        foreach ($payments as $payment) {
            $key = $keyFn($payment->created_at);
            if (! isset($buckets[$key])) {
                $buckets[$key] = ['revenue' => '0.00', 'count' => 0];
            }
            $buckets[$key]['revenue'] = Money::add($buckets[$key]['revenue'], (string) $payment->amount);
            $buckets[$key]['count']++;
        }

        $series = [];
        $period = CarbonPeriod::create($start, $step, $end);
        foreach ($period as $date) {
            $key = $keyFn($date);
            $series[] = [
                'label' => $labelFn($date),
                'revenue' => $buckets[$key]['revenue'] ?? '0.00',
                'count' => $buckets[$key]['count'] ?? 0,
            ];
        }

        return $series;
    }

    private function buildComparison(int $days): array
    {
        $now = Carbon::now();
        $currentStart = $now->copy()->subDays($days - 1)->startOfDay();
        $currentEnd = $now->copy()->endOfDay();
        $previousStart = $now->copy()->subDays(($days * 2) - 1)->startOfDay();
        $previousEnd = $now->copy()->subDays($days)->endOfDay();

        $currentQuery = Payment::where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$currentStart, $currentEnd]);
        $previousQuery = Payment::where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$previousStart, $previousEnd]);

        $currentRevenue = $this->sumPayments(clone $currentQuery);
        $previousRevenue = $this->sumPayments(clone $previousQuery);
        $currentCount = (clone $currentQuery)->count();
        $previousCount = (clone $previousQuery)->count();

        $revenueChange = $this->percentChange((float) $previousRevenue, (float) $currentRevenue);
        $countChange = $this->percentChange($previousCount, $currentCount);

        return [
            'currentRevenue' => $currentRevenue,
            'previousRevenue' => $previousRevenue,
            'currentCount' => $currentCount,
            'previousCount' => $previousCount,
            'revenueChange' => $revenueChange,
            'countChange' => $countChange,
            'label' => $days.' days',
        ];
    }

    private function percentChange(float $previous, float $current): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }
}
