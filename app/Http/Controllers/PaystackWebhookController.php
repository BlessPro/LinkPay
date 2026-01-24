<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SellerNotification;
use App\Services\PaystackService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request, PaystackService $paystack)
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();
        $expected = hash_hmac('sha512', $payload, config('services.paystack.secret_key'));

        if (! $signature || ! hash_equals($expected, $signature)) {
            return response()->json(['status' => 'invalid_signature'], 401);
        }

        if ($request->input('event') !== 'charge.success') {
            return response()->json(['status' => 'ignored']);
        }

        $reference = data_get($request->input('data'), 'reference');
        if (! $reference) {
            return response()->json(['status' => 'missing_reference']);
        }

        $payment = Payment::where('reference', $reference)->first();
        if (! $payment) {
            return response()->json(['status' => 'payment_not_found']);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            return response()->json(['status' => 'already_processed']);
        }

        $verification = $paystack->verifyTransaction($reference);
        $verifiedStatus = data_get($verification, 'data.status');

        if ($verifiedStatus !== 'success') {
            return response()->json(['status' => 'verification_failed']);
        }

        $payment->status = Payment::STATUS_SUCCESS;
        $payment->channel = data_get($verification, 'data.channel');
        $paidAt = data_get($verification, 'data.paid_at');
        if ($paidAt) {
            $payment->paid_at = Carbon::parse($paidAt);
        }
        $payment->raw_payload = data_get($verification, 'data');
        $payment->save();

        SellerNotification::create([
            'user_id' => $payment->user_id,
            'type' => SellerNotification::TYPE_PAYMENT_RECEIVED,
            'title' => 'Payment received',
            'body' => 'Payment '.$payment->reference.' was completed.',
            'data' => ['payment_id' => $payment->id],
        ]);

        if ($payment->invoice_id) {
            $invoice = Invoice::find($payment->invoice_id);
            if ($invoice) {
                $beforeStatus = $invoice->status;
                $invoice->refreshPaymentStatus();

                if ($invoice->status === Invoice::STATUS_PARTIAL && $beforeStatus !== Invoice::STATUS_PARTIAL) {
                    SellerNotification::create([
                        'user_id' => $payment->user_id,
                        'type' => SellerNotification::TYPE_INVOICE_PARTIAL,
                        'title' => 'Invoice partially paid',
                        'body' => 'Invoice "'.$invoice->title.'" has a new payment.',
                        'data' => ['invoice_id' => $invoice->id],
                    ]);
                }

                if ($invoice->status === Invoice::STATUS_PAID && $beforeStatus !== Invoice::STATUS_PAID) {
                    SellerNotification::create([
                        'user_id' => $payment->user_id,
                        'type' => SellerNotification::TYPE_INVOICE_PAID,
                        'title' => 'Invoice fully paid',
                        'body' => 'Invoice "'.$invoice->title.'" is fully paid.',
                        'data' => ['invoice_id' => $invoice->id],
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
