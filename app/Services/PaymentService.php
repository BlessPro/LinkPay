<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SellerNotification;
use Carbon\Carbon;

class PaymentService
{
    public function markSuccess(Payment $payment, array $verifiedData): void
    {
        if ($payment->status === Payment::STATUS_SUCCESS) {
            return;
        }

        $payment->status = Payment::STATUS_SUCCESS;
        $payment->channel = $verifiedData['channel'] ?? null;

        $paidAt = $verifiedData['paid_at'] ?? null;
        if ($paidAt) {
            $payment->paid_at = Carbon::parse($paidAt);
        }

        $payment->raw_payload = $verifiedData;
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
    }
}
