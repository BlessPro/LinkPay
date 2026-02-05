<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PaymentController extends Controller
{
    public function index(Request $request, PaystackService $paystack, PaymentService $paymentsService)
    {
        $user = $request->user();

        // Auto-verify a small batch of recent pending payments so sellers never have to click "Verify".
        $pendingToVerify = $user->payments()
            ->where('status', Payment::STATUS_PENDING)
            ->whereNotNull('reference')
            ->where('created_at', '>=', Carbon::now()->subDays(2))
            ->latest()
            ->limit(5)
            ->get();

        foreach ($pendingToVerify as $pending) {
            try {
                $verification = $paystack->verifyTransaction($pending->reference);
                if (data_get($verification, 'data.status') === 'success') {
                    $paymentsService->markSuccess($pending, data_get($verification, 'data', []));
                }
            } catch (\Throwable $exception) {
                // Ignore verification failures here; webhook/success callback can still resolve later.
            }
        }

        $successQuery = $user->payments()->where('status', Payment::STATUS_SUCCESS);
        $totalReceived = $this->sumPayments(clone $successQuery);
        $successfulCount = (clone $successQuery)->count();
        $pendingCount = $user->payments()->where('status', Payment::STATUS_PENDING)->count();
        $last30DaysReceived = $this->sumPayments(
            $user->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->whereBetween('created_at', [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()])
        );

        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->endOfDay();
        $dailyPayments = $user->payments()
            ->selectRaw('date(created_at) as day, sum(amount) as revenue, count(*) as payments')
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailySeries = [];
        foreach (CarbonPeriod::create($start, '1 day', $end) as $date) {
            $key = $date->format('Y-m-d');
            $entry = $dailyPayments->get($key);
            $revenue = $entry ? (string) $entry->revenue : '0.00';
            $paymentsCount = $entry ? $entry->payments : 0;
            $dailySeries[] = [
                'day' => $key,
                'label' => $date->format('M d'),
                'revenue' => $revenue,
                'payments' => $paymentsCount,
            ];
        }

        $payments = $user->payments()->latest()->paginate(10);

        return view('dashboard.payments.index', [
            'payments' => $payments,
            'currency' => config('services.paystack.currency', 'GHS'),
            'totalReceived' => $totalReceived,
            'successfulCount' => $successfulCount,
            'pendingCount' => $pendingCount,
            'last30DaysReceived' => $last30DaysReceived,
            'dailySeries' => $dailySeries,
        ]);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $format = $request->query('format', 'csv');
        $range = $request->query('range', '30days');
        $customStart = $request->query('start_date');
        $customEnd = $request->query('end_date');

        [$start, $end] = $this->resolveDateRange($range, $customStart, $customEnd);
        $rangeLabel = $this->rangeLabel($start, $end, $range);

        $query = $user->payments()
            ->where('status', Payment::STATUS_SUCCESS);

        if ($start && $end) {
            $query->whereBetween('paid_at', [$start, $end]);
        }

        $payments = $query->latest()->get();
        $dataset = $this->buildExportDataset($payments, $rangeLabel);

        if ($format === 'pdf') {
            return $this->exportPdf($dataset);
        }

        return $this->exportCsv($dataset);
    }

    private function resolveDateRange(string $range, ?string $start, ?string $end): array
    {
        $now = Carbon::now();

        return match ($range) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            '7days' => [
                $now->copy()->subDays(6)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            '30days' => [
                $now->copy()->subDays(29)->startOfDay(),
                $now->copy()->endOfDay(),
            ],
            'all_time' => [null, null],
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
            return $range === 'all_time' ? 'All time' : ucfirst(str_replace('_', ' ', $range));
        }

        return $start->toDateString().' to '.$end->toDateString();
    }

    private function sumPayments($query): string
    {
        $sum = '0.00';
        $query->get()->each(function (Payment $payment) use (&$sum) {
            $sum = Money::add($sum, (string) $payment->amount);
        });

        return $sum;
    }

    private function buildExportDataset(Collection $payments, string $rangeLabel): array
    {
        $rows = $payments->map(function (Payment $payment) {
            $item = $payment->invoice?->title ?? $payment->product?->name ?? 'N/A';
            $type = $payment->invoice_id ? 'Invoice' : ($payment->product_id ? 'Product' : 'Other');
            $customerName = data_get($payment->raw_payload, 'customer.name');
            $customerEmail = data_get($payment->raw_payload, 'customer.email');
            $customerPhone = data_get($payment->raw_payload, 'customer.phone');
            $payerParts = array_filter([$customerName, $customerEmail, $customerPhone]);
            $payer = $payerParts ? implode(' | ', $payerParts) : 'Customer';

            return [
                'date' => $payment->paid_at?->toDateTimeString() ?? $payment->created_at->toDateTimeString(),
                'reference' => $payment->reference,
                'type' => $type,
                'item' => $item,
                'payer' => $payer,
                'amount' => number_format((float) $payment->amount, 2, '.', ''),
                'receiving_account' => $payment->receiving_account
                    ?? data_get($payment->raw_payload, 'metadata.subaccount')
                    ?? 'Platform',
                'transaction_code' => $payment->transaction_code
                    ?? data_get($payment->raw_payload, 'metadata.transaction_code')
                    ?? '',
                'transaction_id' => $payment->transaction_id
                    ?? data_get($payment->raw_payload, 'metadata.transaction_id')
                    ?? '',
                'channel' => $payment->channel ?? 'Paystack',
            ];
        })->toArray();

        return [
            'rows' => $rows,
            'summary' => [
                'revenue' => $this->sumField($payments, 'amount'),
                'count' => $payments->count(),
            ],
            'rangeLabel' => $rangeLabel,
        ];
    }

    private function sumField(Collection $payments, string $field): string
    {
        $sum = '0.00';
        $payments->each(function (Payment $payment) use (&$sum, $field) {
            $value = $payment->{$field} ?? '0.00';
            $sum = Money::add($sum, (string) $value);
        });

        return $sum;
    }

    private function exportCsv(array $dataset)
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($dataset['rows'] as $row) {
            fputcsv($handle, array_values($row));
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $filename = 'payments_export_'.date('Ymd_His').'.csv';

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function exportPdf(array $dataset)
    {
        $pdf = Pdf::loadView('dashboard.payments.export-pdf', [
            'rows' => $dataset['rows'],
            'summary' => $dataset['summary'],
            'rangeLabel' => $dataset['rangeLabel'],
            'currency' => config('services.paystack.currency', 'GHS'),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('payment-report.pdf');
    }
}
