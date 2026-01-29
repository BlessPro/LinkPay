<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use App\Services\SellerNotifier;
use App\Services\TwilioMessagingService;
use App\Support\Money;
use Illuminate\Support\Facades\Log;

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

        $existingPayload = $payment->raw_payload ?? [];
        $payment->raw_payload = array_replace_recursive($existingPayload, $verifiedData);
        $payment->save();

        $user = $payment->user()->with('sellerProfile')->first();
        if ($user) {
            app(SellerNotifier::class)->notify(
                $user,
                \App\Models\SellerNotification::TYPE_PAYMENT_RECEIVED,
                'Payment received',
                'Payment '.$payment->reference.' was completed.',
                ['payment_id' => $payment->id]
            );
        }

        if ($payment->invoice_id) {
            $invoice = Invoice::find($payment->invoice_id);
            if ($invoice) {
                $beforeStatus = $invoice->status;
                $invoice->refreshPaymentStatus();

                if ($invoice->status === Invoice::STATUS_PARTIAL && $beforeStatus !== Invoice::STATUS_PARTIAL) {
                    if ($user) {
                        app(SellerNotifier::class)->notify(
                            $user,
                            \App\Models\SellerNotification::TYPE_INVOICE_PARTIAL,
                            'Invoice partially paid',
                            'Invoice "'.$invoice->title.'" has a new payment.',
                            ['invoice_id' => $invoice->id]
                        );
                    }
                }

                if ($invoice->status === Invoice::STATUS_PAID && $beforeStatus !== Invoice::STATUS_PAID) {
                    if ($user) {
                        app(SellerNotifier::class)->notify(
                            $user,
                            \App\Models\SellerNotification::TYPE_INVOICE_PAID,
                            'Invoice fully paid',
                            'Invoice "'.$invoice->title.'" is fully paid.',
                            ['invoice_id' => $invoice->id]
                        );
                    }
                }
            }
        }

        $customerPhone = data_get($payment->raw_payload, 'customer.phone')
            ?? data_get($verifiedData, 'metadata.customer.phone')
            ?? data_get($verifiedData, 'customer.phone');

        if ($customerPhone) {
            $amount = Money::format((string) $payment->amount, config('services.paystack.currency', 'GHS'));
            $sellerName = $user?->sellerProfile?->business_name ?? $user?->name ?? 'LinkPay seller';
            $message = "Payment successful ✅\nAmount: {$amount}\nSeller: {$sellerName}\nRef: {$payment->reference}";
            try {
                app(TwilioMessagingService::class)->sendWhatsApp($customerPhone, $message);
                Log::info('Customer WhatsApp notify sent', [
                    'payment_id' => $payment->id,
                    'phone' => $customerPhone,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Customer WhatsApp notify failed', [
                    'payment_id' => $payment->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        } else {
            Log::warning('Customer WhatsApp notify skipped (no phone)', [
                'payment_id' => $payment->id,
            ]);
        }
    }
}
