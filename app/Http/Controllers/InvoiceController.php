<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateInvoiceRequest;
use App\Models\Invoice;
use App\Models\SellerNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        return view('dashboard.invoices.show', [
            'invoice' => $invoice,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        abort_unless($invoice->user_id === auth()->id(), 403);
    }
}
