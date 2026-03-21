<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;

class PaymentReconciliationService
{
    public function __construct(private readonly PaystackService $paystack)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReport(int $days = 7, ?int $sellerId = null, ?string $type = null, bool $agedOnly = false): array
    {
        $days = max(1, min(30, $days));
        $from = now()->subDays($days - 1)->startOfDay();
        $to = now()->endOfDay();

        $localQuery = Payment::query()
            ->with('user.sellerProfile')
            ->whereNotNull('reference')
            ->whereBetween('created_at', [$from, $to]);

        if ($sellerId) {
            $localQuery->where('user_id', $sellerId);
        }

        $localPayments = $localQuery->get();

        $localByReference = $localPayments->groupBy('reference');
        $duplicateReferences = $localByReference
            ->filter(fn ($payments) => $payments->count() > 1)
            ->map(fn ($payments, $reference) => [
                'reference' => $reference,
                'count' => $payments->count(),
                'payment_ids' => $payments->pluck('id')->values()->all(),
            ])
            ->values()
            ->all();

        $paystackResponse = $this->paystack->fetchTransactionsByDateRange($from, $to);
        $paystackTransactions = collect($paystackResponse['data'] ?? [])
            ->filter(fn ($tx) => ! empty($tx['reference']))
            ->values();

        $paystackByReference = $paystackTransactions->keyBy('reference');

        $exceptions = [];
        $statusCounts = [
            'matched' => 0,
            'missing_in_db' => 0,
            'missing_in_paystack' => 0,
            'amount_mismatch' => 0,
            'status_mismatch' => 0,
            'duplicate_reference' => count($duplicateReferences),
        ];

        foreach ($localByReference as $reference => $payments) {
            /** @var Payment $payment */
            $payment = $payments->sortByDesc('created_at')->first();
            $paystackTx = $paystackByReference->get($reference);

            if (! $paystackTx) {
                $statusCounts['missing_in_paystack']++;
                $exceptions[] = $this->exceptionItem('missing_in_paystack', $reference, $payment, null, 'Reference not found in Paystack list for period.');
                continue;
            }

            $localAmount = (string) $payment->amount;
            $paystackAmount = Money::fromMinor((int) ($paystackTx['amount'] ?? 0));
            $localIsSuccess = $payment->status === Payment::STATUS_SUCCESS;
            $paystackIsSuccess = strtolower((string) ($paystackTx['status'] ?? '')) === 'success';

            if (Money::compare($localAmount, $paystackAmount) !== 0) {
                $statusCounts['amount_mismatch']++;
                $exceptions[] = $this->exceptionItem('amount_mismatch', $reference, $payment, $paystackTx, 'Local amount does not match Paystack amount.');
                continue;
            }

            if ($localIsSuccess !== $paystackIsSuccess) {
                $statusCounts['status_mismatch']++;
                $exceptions[] = $this->exceptionItem('status_mismatch', $reference, $payment, $paystackTx, 'Local status does not match Paystack transaction status.');
                continue;
            }

            $statusCounts['matched']++;
        }

        foreach ($paystackTransactions as $paystackTx) {
            $reference = (string) ($paystackTx['reference'] ?? '');
            if ($reference === '' || $localByReference->has($reference)) {
                continue;
            }

            $statusCounts['missing_in_db']++;
            $exceptions[] = $this->exceptionItem('missing_in_db', $reference, null, $paystackTx, 'Transaction exists in Paystack but no local payment record found.');
        }

        foreach ($duplicateReferences as $duplicate) {
            $reference = (string) $duplicate['reference'];
            $payment = $localByReference->get($reference)?->sortByDesc('created_at')->first();
            $paystackTx = $paystackByReference->get($reference);
            $exceptions[] = $this->exceptionItem('duplicate_reference', $reference, $payment, $paystackTx, 'Multiple local payments share the same reference.');
        }

        $exceptions = collect($exceptions)
            ->sortBy([
                ['is_aged', 'desc'],
                ['severity_score', 'desc'],
                ['created_at', 'asc'],
            ]);

        if ($type) {
            $exceptions = $exceptions->where('type', $type);
        }

        if ($agedOnly) {
            $exceptions = $exceptions->where('is_aged', true);
        }

        $exceptions = $exceptions
            ->values()
            ->all();

        $exceptionSummary = collect($exceptions);
        $severityBuckets = [
            'critical' => $exceptionSummary->where('severity_score', '>=', 1000)->count(),
            'high' => $exceptionSummary->whereBetween('severity_score', [90, 999])->count(),
            'medium' => $exceptionSummary->whereBetween('severity_score', [70, 89])->count(),
            'low' => $exceptionSummary->where('severity_score', '<', 70)->count(),
        ];

        return [
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'sellerId' => $sellerId,
            'type' => $type,
            'agedOnly' => $agedOnly,
            'statusCounts' => $statusCounts,
            'exceptions' => $exceptions,
            'exceptionTotal' => count($exceptions),
            'agedExceptionTotal' => $exceptionSummary->where('is_aged', true)->count(),
            'severityBuckets' => $severityBuckets,
            'localTotal' => $localPayments->count(),
            'paystackTotal' => $paystackTransactions->count(),
            'duplicateReferences' => $duplicateReferences,
            'meta' => $paystackResponse['meta'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $paystackTx
     * @return array<string, mixed>
     */
    private function exceptionItem(string $type, string $reference, ?Payment $payment, ?array $paystackTx, string $message): array
    {
        $paystackStatus = $paystackTx ? strtoupper((string) ($paystackTx['status'] ?? 'UNKNOWN')) : null;
        $paystackAmount = $paystackTx ? Money::fromMinor((int) ($paystackTx['amount'] ?? 0)) : null;
        $seller = $payment?->user;
        $createdAt = $payment?->created_at ?? Carbon::parse((string) ($paystackTx['created_at'] ?? now()->toIso8601String()));
        $ageHours = max(0, (int) $createdAt->diffInHours(now()));
        $isAged = $ageHours >= 24;
        $severityByType = [
            'missing_in_db' => 100,
            'status_mismatch' => 90,
            'amount_mismatch' => 80,
            'missing_in_paystack' => 70,
            'duplicate_reference' => 60,
        ];
        $severityScore = ($severityByType[$type] ?? 50) + ($isAged ? 1000 : 0);

        return [
            'type' => $type,
            'reference' => $reference,
            'message' => $message,
            'payment_id' => $payment?->id,
            'seller_id' => $payment?->user_id,
            'seller_name' => $seller?->sellerProfile?->business_name ?? $seller?->email,
            'local_status' => $payment?->status,
            'local_amount' => $payment?->amount ? (string) $payment->amount : null,
            'paystack_status' => $paystackStatus,
            'paystack_amount' => $paystackAmount,
            'created_at' => $createdAt,
            'age_hours' => $ageHours,
            'is_aged' => $isAged,
            'severity_score' => $severityScore,
        ];
    }
}
