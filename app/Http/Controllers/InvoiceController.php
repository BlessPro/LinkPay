<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInvoiceRequest;
use App\Models\AnalyticsEvent;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SellerNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\Money;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = $request->user()->invoices()->latest()->paginate(10);

        return view('dashboard.invoices.index', [
            'invoices' => $invoices,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function create()
    {
        return view('dashboard.invoices.create');
    }

    public function store(CreateInvoiceRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['token'] = Str::random(32);
        $data['paid_total'] = '0.00';
        $data['status'] = Invoice::STATUS_PENDING;

        if ($data['payment_mode'] === Invoice::MODE_FULL) {
            $data['deposit_amount'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('invoices', 'public');
        }

        $invoice = Invoice::create($data);

        SellerNotification::create([
            'user_id' => $request->user()->id,
            'type' => SellerNotification::TYPE_INVOICE_CREATED,
            'title' => 'Invoice created',
            'body' => 'Invoice "'.$invoice->title.'" is ready to share.',
            'data' => ['invoice_id' => $invoice->id],
        ]);

        return redirect()->route('invoices.show', $invoice)->with('status', 'invoice-created');
    }

    public function show(Invoice $invoice)
    {
        $this->authorizeInvoice($invoice);

        $invoice->load('payments');
        $stats = $this->buildStats($invoice);

        return view('dashboard.invoices.show', [
            'invoice' => $invoice,
            'stats' => $stats,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 403);
    }

    private function buildStats(Invoice $invoice): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->endOfDay();

        $events = AnalyticsEvent::where('user_id', $invoice->user_id)
            ->where('entity_type', 'invoice')
            ->where('entity_id', (string) $invoice->id)
            ->whereBetween('created_at', [$start, $end]);

        $views = (clone $events)->where('event_type', AnalyticsEvent::TYPE_INVOICE_VIEW)->count();
        $viewsUnique = (clone $events)->where('event_type', AnalyticsEvent::TYPE_INVOICE_VIEW)
            ->distinct('session_hash')->count('session_hash');
        $clicks = (clone $events)->where('event_type', AnalyticsEvent::TYPE_INVOICE_CLICK)->count();
        $clicksUnique = (clone $events)->where('event_type', AnalyticsEvent::TYPE_INVOICE_CLICK)
            ->distinct('session_hash')->count('session_hash');

        $payments = Payment::where('invoice_id', $invoice->id)
            ->where('status', Payment::STATUS_SUCCESS)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $paymentTotal = '0.00';
        foreach ($payments as $payment) {
            $paymentTotal = Money::add($paymentTotal, (string) $payment->amount);
        }

        return [
            'views' => $views,
            'viewsUnique' => $viewsUnique,
            'clicks' => $clicks,
            'clicksUnique' => $clicksUnique,
            'payments' => $payments->count(),
            'paymentTotal' => $paymentTotal,
            'conversion' => $views > 0 ? round(($payments->count() / $views) * 100, 1) : 0.0,
        ];
    }
}
