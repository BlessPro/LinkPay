<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $invoices = Invoice::query()
            ->with(['user.sellerProfile'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('token', 'ilike', "%{$search}%")
                        ->orWhere('title', 'ilike', "%{$search}%")
                        ->orWhere('customer_name', 'ilike', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('email', 'ilike', "%{$search}%");
                        })
                        ->orWhereHas('payments', function ($paymentQuery) use ($search) {
                            $paymentQuery->where('reference', 'ilike', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'search' => $search,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['user.sellerProfile', 'payments' => function ($query) {
            $query->latest();
        }]);

        $views = AnalyticsEvent::where('entity_type', 'invoice')
            ->where('entity_id', $invoice->id)
            ->where('event_type', AnalyticsEvent::TYPE_INVOICE_VIEW)
            ->count();

        $uniqueViews = AnalyticsEvent::where('entity_type', 'invoice')
            ->where('entity_id', $invoice->id)
            ->where('event_type', AnalyticsEvent::TYPE_INVOICE_VIEW)
            ->distinct('session_hash')
            ->count('session_hash');

        $clicks = AnalyticsEvent::where('entity_type', 'invoice')
            ->where('entity_id', $invoice->id)
            ->where('event_type', AnalyticsEvent::TYPE_INVOICE_CLICK)
            ->count();

        $successfulPayments = $invoice->payments->where('status', Payment::STATUS_SUCCESS);
        $paymentCount = $successfulPayments->count();
        $paymentTotal = '0.00';
        foreach ($successfulPayments as $payment) {
            $paymentTotal = Money::add($paymentTotal, (string) $payment->amount);
        }

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'views' => $views,
            'uniqueViews' => $uniqueViews,
            'clicks' => $clicks,
            'paymentCount' => $paymentCount,
            'paymentTotal' => $paymentTotal,
            'currency' => config('services.paystack.currency', 'GHS'),
            'publicUrl' => route('public.invoice', $invoice->token),
        ]);
    }
}
