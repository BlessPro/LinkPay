<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SellerProfile;
use App\Models\TwilioMessageLog;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\PaymentService;
use App\Services\PaymentReconciliationService;
use App\Services\PaystackService;
use App\Services\SellerNotifier;
use App\Support\Money;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalSellers = User::has('sellerProfile')->count();
        $connectedSellers = SellerProfile::whereNotNull('paystack_subaccount_code')->count();
        $totalProducts = \App\Models\Product::count();
        $totalInvoices = Invoice::count();
        $totalPayments = Payment::where('status', Payment::STATUS_SUCCESS)->count();
        $totalReceived = $this->sumPayments(Payment::where('status', Payment::STATUS_SUCCESS));
        $commissionTotal = $this->sumField(Payment::where('status', Payment::STATUS_SUCCESS), 'commission_amount');

        $pendingInvoices = Invoice::where('status', Invoice::STATUS_PENDING)->count();
        $partialInvoices = Invoice::where('status', Invoice::STATUS_PARTIAL)->count();
        $failedPayments = Payment::where('status', Payment::STATUS_FAILED)->count();

        $exceptionPayments = Payment::query()
            ->with('user.sellerProfile')
            ->where('status', Payment::STATUS_FAILED)
            ->latest()
            ->take(30)
            ->get();

        $webhookWindowStart = now()->subDay();
        $webhookTotal24h = WebhookEvent::where('received_at', '>=', $webhookWindowStart)->count();
        $webhookFailed24h = WebhookEvent::where('received_at', '>=', $webhookWindowStart)
            ->where('status', WebhookEvent::STATUS_FAILED)
            ->count();
        $webhookEvents = WebhookEvent::with('payment.user')
            ->latest('received_at')
            ->take(12)
            ->get();

        $twilioWindowStart = now()->subDay();
        $twilioTotal24h = TwilioMessageLog::where('sent_at', '>=', $twilioWindowStart)->count();
        $twilioFailed24h = TwilioMessageLog::where('sent_at', '>=', $twilioWindowStart)
            ->whereIn('status', ['failed', 'undelivered'])
            ->count();
        $twilioRecent = TwilioMessageLog::with('user')
            ->latest('sent_at')
            ->take(12)
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

        $planCounts = User::query()
            ->selectRaw('COALESCE(plan_type, ?) as plan_type, COUNT(*) as total', [User::PLAN_FREE_TRIAL])
            ->groupBy('plan_type')
            ->pluck('total', 'plan_type')
            ->toArray();

        $recentAudits = AdminAuditLog::with('adminUser')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', [
            'totalSellers' => $totalSellers,
            'connectedSellers' => $connectedSellers,
            'totalProducts' => $totalProducts,
            'totalInvoices' => $totalInvoices,
            'totalPayments' => $totalPayments,
            'totalReceived' => $totalReceived,
            'commissionTotal' => $commissionTotal,
            'pendingInvoices' => $pendingInvoices,
            'partialInvoices' => $partialInvoices,
            'failedPayments' => $failedPayments,
            'exceptionPayments' => $exceptionPayments,
            'webhookTotal24h' => $webhookTotal24h,
            'webhookFailed24h' => $webhookFailed24h,
            'webhookEvents' => $webhookEvents,
            'twilioTotal24h' => $twilioTotal24h,
            'twilioFailed24h' => $twilioFailed24h,
            'twilioRecent' => $twilioRecent,
            'dailySeries' => $dailySeries,
            'weeklySeries' => $weeklySeries,
            'monthlySeries' => $monthlySeries,
            'compare7' => $compare7,
            'compare30' => $compare30,
            'sellers' => $sellers,
            'planCounts' => $planCounts,
            'recentAudits' => $recentAudits,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function seller(User $seller): View
    {
        $seller->load([
            'sellerProfile',
            'products' => fn ($query) => $query->latest()->take(10),
            'invoices' => fn ($query) => $query->latest()->take(10),
        ]);

        $payments = $seller->payments()->latest()->take(20)->get();
        $successfulPayments = $seller->payments()->where('status', Payment::STATUS_SUCCESS);
        $pendingPayments = $seller->payments()->where('status', Payment::STATUS_PENDING);

        $timeline = collect();
        foreach ($payments as $payment) {
            $timeline->push([
                'type' => 'payment',
                'title' => 'Payment '.$payment->status,
                'meta' => $payment->reference,
                'created_at' => $payment->created_at,
            ]);
        }

        $notifications = $seller->sellerNotifications()->latest()->take(20)->get();
        foreach ($notifications as $notification) {
            $timeline->push([
                'type' => 'notification',
                'title' => $notification->title,
                'meta' => $notification->type,
                'created_at' => $notification->created_at,
            ]);
        }

        $messages = $seller->twilioMessageLogs()->latest('sent_at')->take(20)->get();
        foreach ($messages as $message) {
            $timeline->push([
                'type' => 'message',
                'title' => strtoupper($message->channel).' '.$message->status,
                'meta' => $message->to,
                'created_at' => $message->sent_at ?? $message->created_at,
            ]);
        }

        $timeline = $timeline->sortByDesc('created_at')->take(20)->values();

        return view('admin.sellers.show', [
            'seller' => $seller,
            'currency' => config('services.paystack.currency', 'GHS'),
            'totalReceived' => $this->sumPayments(clone $successfulPayments),
            'paymentCount' => (clone $successfulPayments)->count(),
            'pendingCount' => (clone $pendingPayments)->count(),
            'invoiceCount' => $seller->invoices()->count(),
            'productCount' => $seller->products()->count(),
            'timeline' => $timeline,
            'recentPayments' => $payments,
        ]);
    }

    public function reconciliation(Request $request, PaymentReconciliationService $reconciliation): View
    {
        $days = (int) $request->query('days', 7);
        $sellerId = $request->query('seller_id');
        $sellerId = is_numeric($sellerId) ? (int) $sellerId : null;

        $report = $reconciliation->buildReport($days, $sellerId);
        $sellers = User::query()
            ->has('sellerProfile')
            ->with('sellerProfile:id,user_id,business_name')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.payments.reconciliation', [
            'report' => $report,
            'sellers' => $sellers,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function reconciliationExport(Request $request, PaymentReconciliationService $reconciliation): Response
    {
        $days = (int) $request->query('days', 7);
        $sellerId = $request->query('seller_id');
        $sellerId = is_numeric($sellerId) ? (int) $sellerId : null;
        $report = $reconciliation->buildReport($days, $sellerId);

        $rows = [];
        $rows[] = [
            'type',
            'reference',
            'seller_name',
            'local_status',
            'local_amount',
            'paystack_status',
            'paystack_amount',
            'age_hours',
            'is_aged',
            'message',
            'created_at',
        ];

        foreach ($report['exceptions'] as $exception) {
            $rows[] = [
                $exception['type'] ?? '',
                $exception['reference'] ?? '',
                $exception['seller_name'] ?? '',
                $exception['local_status'] ?? '',
                $exception['local_amount'] ?? '',
                $exception['paystack_status'] ?? '',
                $exception['paystack_amount'] ?? '',
                (string) ($exception['age_hours'] ?? ''),
                ! empty($exception['is_aged']) ? 'yes' : 'no',
                $exception['message'] ?? '',
                isset($exception['created_at']) ? $exception['created_at']->toDateTimeString() : '',
            ];
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
            'Content-Disposition' => 'attachment; filename="reconciliation-exceptions.csv"',
        ]);
    }

    public function retryPayment(
        Request $request,
        Payment $payment,
        PaystackService $paystack,
        PaymentService $paymentsService
    ): RedirectResponse {
        if ($payment->status === Payment::STATUS_SUCCESS) {
            return back()->with('status', 'already-success');
        }

        $admin = $request->user();

        try {
            $verification = $paystack->verifyTransaction($payment->reference);
            if (data_get($verification, 'data.status') === 'success') {
                $paymentsService->markSuccess($payment, data_get($verification, 'data', []));
                $this->audit($admin->id, 'payment.retry_verify.success', $payment, $request, [
                    'reference' => $payment->reference,
                ]);

                return back()->with('status', 'retry-success');
            }

            $this->audit($admin->id, 'payment.retry_verify.not_success', $payment, $request, [
                'reference' => $payment->reference,
                'verified_status' => data_get($verification, 'data.status'),
            ]);

            return back()->withErrors(['payment' => 'Paystack verify did not return success for this reference.']);
        } catch (\Throwable $exception) {
            $this->audit($admin->id, 'payment.retry_verify.error', $payment, $request, [
                'reference' => $payment->reference,
                'message' => $exception->getMessage(),
            ]);

            return back()->withErrors(['payment' => 'Retry failed: '.$exception->getMessage()]);
        }
    }

    public function confirmPayment(
        Request $request,
        Payment $payment,
        PaymentService $paymentsService
    ): RedirectResponse {
        if ($payment->status === Payment::STATUS_SUCCESS) {
            return back()->with('status', 'already-success');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $admin = $request->user();

        $verifiedData = [
            'status' => 'success',
            'channel' => 'admin_manual_confirmation',
            'paid_at' => now()->toIso8601String(),
            'metadata' => [
                'admin_confirmed' => true,
                'admin_confirmed_by' => $admin?->id,
                'admin_note' => $validated['note'] ?? null,
            ],
        ];

        try {
            $paymentsService->markSuccess($payment, $verifiedData);
            $this->audit($admin->id, 'payment.manual_confirm.success', $payment, $request, [
                'reference' => $payment->reference,
                'note' => $validated['note'] ?? null,
            ]);

            return back()->with('status', 'manual-confirm-success');
        } catch (\Throwable $exception) {
            $this->audit($admin->id, 'payment.manual_confirm.error', $payment, $request, [
                'reference' => $payment->reference,
                'message' => $exception->getMessage(),
                'note' => $validated['note'] ?? null,
            ]);

            return back()->withErrors(['payment' => 'Manual confirmation failed: '.$exception->getMessage()]);
        }
    }

    public function markPaymentFailed(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status === Payment::STATUS_FAILED) {
            return back()->with('status', 'already-failed');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $payment->status = Payment::STATUS_FAILED;
        $payment->verified_at = now();
        $payment->raw_payload = array_replace_recursive($payment->raw_payload ?? [], [
            'admin_manual_update' => [
                'marked_failed' => true,
                'by_admin' => $request->user()->id,
                'note' => $validated['note'] ?? null,
                'at' => now()->toIso8601String(),
            ],
        ]);
        $payment->save();

        $this->audit(
            (int) $request->user()->id,
            'payment.manual_mark_failed.success',
            $payment,
            $request,
            [
                'reference' => $payment->reference,
                'note' => $validated['note'] ?? null,
            ]
        );

        return back()->with('status', 'manual-mark-failed-success');
    }

    public function syncSellerPaystack(Request $request, User $seller, PaystackService $paystack): RedirectResponse
    {
        $profile = $seller->sellerProfile;
        if (! $profile) {
            return back()->withErrors(['seller' => 'Seller profile not found.']);
        }

        if (! $profile->settlement_bank_code || ! $profile->account_number || ! $profile->account_name) {
            return back()->withErrors(['seller' => 'Seller payout details are incomplete.']);
        }

        try {
            $response = $paystack->createOrUpdateSubaccount($profile);
            $profile->paystack_subaccount_code = $response['subaccount_code'] ?? $profile->paystack_subaccount_code;
            $profile->percent_charge = $response['percentage_charge'] ?? $profile->percent_charge;
            $profile->save();

            $this->auditGeneric(
                (int) $request->user()->id,
                'seller.paystack.sync',
                User::class,
                (string) $seller->id,
                'Paystack sync '.$seller->email,
                $request,
                ['subaccount_code' => $profile->paystack_subaccount_code]
            );

            return back()->with('status', 'seller-paystack-synced');
        } catch (\Throwable $exception) {
            return back()->withErrors(['seller' => 'Paystack sync failed: '.$exception->getMessage()]);
        }
    }

    public function suspendSeller(Request $request, User $seller): RedirectResponse
    {
        if ($seller->is_admin) {
            return back()->withErrors(['seller' => 'Admin account cannot be suspended here.']);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $seller->suspended_at = now();
        $seller->suspension_note = $validated['note'] ?? null;
        $seller->save();

        app(SellerNotifier::class)->notify(
            $seller,
            \App\Models\SellerNotification::TYPE_ADMIN_MESSAGE,
            'Account suspended',
            'Your account was suspended by admin. '.($seller->suspension_note ? 'Reason: '.$seller->suspension_note : ''),
            ['by_admin' => $request->user()->id],
            sendEmail: false,
            sendWhatsApp: true
        );

        $this->auditGeneric(
            (int) $request->user()->id,
            'seller.suspend',
            User::class,
            (string) $seller->id,
            'Suspend '.$seller->email,
            $request,
            ['note' => $seller->suspension_note]
        );

        return back()->with('status', 'seller-suspended');
    }

    public function unsuspendSeller(Request $request, User $seller): RedirectResponse
    {
        $seller->suspended_at = null;
        $seller->suspension_note = null;
        $seller->save();

        app(SellerNotifier::class)->notify(
            $seller,
            \App\Models\SellerNotification::TYPE_ADMIN_MESSAGE,
            'Account restored',
            'Your account has been re-activated by admin.',
            ['by_admin' => $request->user()->id],
            sendEmail: false,
            sendWhatsApp: true
        );

        $this->auditGeneric(
            (int) $request->user()->id,
            'seller.unsuspend',
            User::class,
            (string) $seller->id,
            'Unsuspend '.$seller->email,
            $request
        );

        return back()->with('status', 'seller-unsuspended');
    }

    public function notifySeller(Request $request, User $seller): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        app(SellerNotifier::class)->notify(
            $seller,
            \App\Models\SellerNotification::TYPE_ADMIN_MESSAGE,
            $validated['title'],
            $validated['body'],
            ['by_admin' => $request->user()->id],
            sendEmail: false,
            sendWhatsApp: true
        );

        $this->auditGeneric(
            (int) $request->user()->id,
            'seller.notify',
            User::class,
            (string) $seller->id,
            'Message to '.$seller->email,
            $request,
            ['title' => $validated['title']]
        );

        return back()->with('status', 'seller-notified');
    }

    private function sumPayments($query): string
    {
        $sum = '0.00';
        $query->get()->each(function (Payment $payment) use (&$sum) {
            $sum = Money::add($sum, (string) $payment->amount);
        });

        return $sum;
    }

    private function sumField($query, string $field): string
    {
        $sum = '0.00';
        $query->get()->each(function (Payment $payment) use (&$sum, $field) {
            $value = (string) ($payment->{$field} ?? '0.00');
            $sum = Money::add($sum, $value);
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

        return [
            'currentRevenue' => $currentRevenue,
            'previousRevenue' => $previousRevenue,
            'currentCount' => $currentCount,
            'previousCount' => $previousCount,
            'revenueChange' => $this->percentChange((float) $previousRevenue, (float) $currentRevenue),
            'countChange' => $this->percentChange($previousCount, $currentCount),
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

    private function audit(int $adminUserId, string $action, Payment $payment, Request $request, array $meta = []): void
    {
        AdminAuditLog::create([
            'admin_user_id' => $adminUserId,
            'action' => $action,
            'target_type' => Payment::class,
            'target_id' => (string) $payment->id,
            'title' => 'Payment '.$payment->reference,
            'meta' => $meta,
            'ip_address' => $request->ip(),
        ]);
    }

    private function auditGeneric(
        int $adminUserId,
        string $action,
        string $targetType,
        ?string $targetId,
        ?string $title,
        Request $request,
        array $meta = []
    ): void {
        AdminAuditLog::create([
            'admin_user_id' => $adminUserId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'title' => $title,
            'meta' => $meta,
            'ip_address' => $request->ip(),
        ]);
    }
}
