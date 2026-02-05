<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currency = config('services.paystack.currency', 'GHS');

        $totalReceived = $this->sumPayments(
            $user->payments()->where('status', Payment::STATUS_SUCCESS)
        );

        $invoiceCount = $user->invoices()->count();
        $productCount = $user->products()->count();

        $now = Carbon::now();
        $thisWeek = $this->sumPayments(
            $user->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
        );
        $lastWeek = $this->sumPayments(
            $user->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [
                    $now->copy()->subWeek()->startOfWeek(),
                    $now->copy()->subWeek()->endOfWeek(),
                ])
        );
        $weekChange = $this->percentChange((float) $lastWeek, (float) $thisWeek);

        $thisMonth = $this->sumPayments(
            $user->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
        );
        $lastMonth = $this->sumPayments(
            $user->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [
                    $now->copy()->subMonth()->startOfMonth(),
                    $now->copy()->subMonth()->endOfMonth(),
                ])
        );
        $monthChange = $this->percentChange((float) $lastMonth, (float) $thisMonth);

        $last30 = $this->sumPayments(
            $user->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()])
        );
        $previous30 = $this->sumPayments(
            $user->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [$now->copy()->subDays(59)->startOfDay(), $now->copy()->subDays(30)->endOfDay()])
        );
        $totalChange = $this->percentChange((float) $previous30, (float) $last30);

        $pendingBalance = '0.00';
        $pendingInvoices = $user->invoices()
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_PARTIAL])
            ->get();
        foreach ($pendingInvoices as $invoice) {
            $pendingBalance = Money::add($pendingBalance, $invoice->balanceRemaining());
        }

        $convertedInvoices = $user->invoices()
            ->whereIn('status', [Invoice::STATUS_PARTIAL, Invoice::STATUS_PAID])
            ->count();
        $conversionRate = $invoiceCount > 0
            ? round(($convertedInvoices / $invoiceCount) * 100, 1)
            : 0.0;

        $successfulPaymentCount = $user->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->count();
        $averagePayment = $successfulPaymentCount > 0
            ? number_format(((float) $totalReceived) / $successfulPaymentCount, 2, '.', '')
            : '0.00';

        $topCustomers = $this->buildTopCustomers(
            $user->payments()->where('status', Payment::STATUS_SUCCESS)->get()
        );

        $recentPayments = $user->payments()
            ->latest()
            ->take(6)
            ->get();

        $recentInvoices = $user->invoices()
            ->latest()
            ->take(6)
            ->get();

        $recentProducts = $user->products()
            ->latest()
            ->take(6)
            ->get();

        $activity = $this->buildActivityFeed($user);

        return view('dashboard.index', [
            'totalReceived' => $totalReceived,
            'totalChange' => $totalChange,
            'thisWeek' => $thisWeek,
            'weekChange' => $weekChange,
            'thisMonth' => $thisMonth,
            'monthChange' => $monthChange,
            'pendingBalance' => $pendingBalance,
            'conversionRate' => $conversionRate,
            'conversionCount' => $convertedInvoices,
            'averagePayment' => $averagePayment,
            'topCustomers' => $topCustomers,
            'invoiceCount' => $invoiceCount,
            'productCount' => $productCount,
            'recentPayments' => $recentPayments,
            'recentInvoices' => $recentInvoices,
            'recentProducts' => $recentProducts,
            'activity' => $activity,
            'profile' => $user->sellerProfile,
            'currency' => $currency,
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

    private function percentChange(float $previous, float $current): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    private function buildTopCustomers($payments): array
    {
        $customers = [];
        foreach ($payments as $payment) {
            $email = data_get($payment->raw_payload, 'customer.email')
                ?? data_get($payment->raw_payload, 'metadata.email');

            if (! $email) {
                continue;
            }

            if (! isset($customers[$email])) {
                $customers[$email] = [
                    'email' => $email,
                    'count' => 0,
                    'total' => '0.00',
                ];
            }

            $customers[$email]['count']++;
            $customers[$email]['total'] = Money::add($customers[$email]['total'], (string) $payment->amount);
        }

        return collect($customers)
            ->sortByDesc(fn ($customer) => (float) $customer['total'])
            ->take(5)
            ->values()
            ->all();
    }

    private function buildActivityFeed($user)
    {
        $payments = $user->payments()
            ->latest()
            ->take(6)
            ->get()
            ->map(function (Payment $payment) {
                return [
                    'type' => 'payment',
                    'title' => 'Payment '.$payment->status,
                    'subtitle' => $payment->reference,
                    'amount' => (string) $payment->amount,
                    'created_at' => $payment->created_at,
                ];
            });

        $notifications = $user->sellerNotifications()
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($notification) {
                return [
                    'type' => 'notification',
                    'title' => $notification->title,
                    'subtitle' => $notification->body,
                    'amount' => null,
                    'created_at' => $notification->created_at,
                ];
            });

        return collect($payments->values())
            ->merge($notifications->values())
            ->sortByDesc('created_at')
            ->take(4)
            ->values();
    }
}
