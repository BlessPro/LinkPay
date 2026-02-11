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
        } elseif (! $payment->paid_at) {
            $payment->paid_at = now();
        }
        $payment->verified_at = now();

        $existingPayload = $payment->raw_payload ?? [];
        $payment->raw_payload = array_replace_recursive($existingPayload, $verifiedData);
        $commissionPercent = (string) config('plans.payments.commission_percent', '0.01');
        $payment->commission_amount = Money::percent((string) $payment->amount, $commissionPercent);
        $payment->transaction_fee = $this->amountFromMinor(data_get($verifiedData, 'transaction_charge'));
        $payment->tax_amount = $this->resolveTaxAmount($verifiedData);
        $payment->receiving_account = $this->resolveReceivingAccount($payment, $verifiedData);
        $payment->transaction_code = $this->resolveTransactionCode($payment, $verifiedData);
        $payment->transaction_id = $this->resolveTransactionId($payment, $verifiedData);
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
            $sellerName = $user?->sellerProfile?->business_name ?? $user?->name ?? '8Kommerce seller';
            $message = "Payment successful\nAmount: {$amount}\nSeller: {$sellerName}\nRef: {$payment->reference}";
            try {
                app(TwilioMessagingService::class)->sendWhatsApp($customerPhone, $message, [
                    'user_id' => $payment->user_id,
                    'payment_id' => $payment->id,
                    'context_type' => 'payment_customer_success',
                    'context_id' => $payment->id,
                ]);
                Log::info('Customer WhatsApp notify sent', [
                    'payment_id' => $payment->id,
                    'phone' => $customerPhone,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Customer WhatsApp notify failed', [
                    'payment_id' => $payment->id,
                    'message' => $exception->getMessage(),
                ]);

                try {
                    // Fall back to SMS when WhatsApp cannot deliver (common in sandbox/outside 24h window).
                    app(TwilioMessagingService::class)->sendSms($customerPhone, $message, [
                        'user_id' => $payment->user_id,
                        'payment_id' => $payment->id,
                        'context_type' => 'payment_customer_success_fallback',
                        'context_id' => $payment->id,
                    ]);
                    Log::info('Customer SMS notify sent (fallback)', [
                        'payment_id' => $payment->id,
                        'phone' => $customerPhone,
                    ]);
                } catch (\Throwable $smsException) {
                    Log::warning('Customer SMS notify failed (fallback)', [
                        'payment_id' => $payment->id,
                        'message' => $smsException->getMessage(),
                    ]);
                }
            }
        } else {
            Log::warning('Customer WhatsApp notify skipped (no phone)', [
                'payment_id' => $payment->id,
            ]);
        }
    }

    private function amountFromMinor($value): string
    {
        if ($value === null) {
            return '0.00';
        }

        return Money::fromMinor($value);
    }

    private function resolveTaxAmount(array $verifiedData): string
    {
        $tax = data_get($verifiedData, 'metadata.tax')
            ?? data_get($verifiedData, 'tax')
            ?? 0;

        if (! is_numeric($tax)) {
            return '0.00';
        }

        return (string) number_format((float) $tax, 2, '.', '');
    }

    private function resolveReceivingAccount(Payment $payment, array $verifiedData): ?string
    {
        return data_get($verifiedData, 'subaccount')
            ?? data_get($verifiedData, 'metadata.subaccount')
            ?? $payment->receiving_account;
    }

    private function resolveTransactionCode(Payment $payment, array $verifiedData): ?string
    {
        return data_get($verifiedData, 'authorization.authorization_code')
            ?? data_get($verifiedData, 'metadata.transaction_code')
            ?? data_get($payment->raw_payload, 'authorization.authorization_code');
    }

    private function resolveTransactionId(Payment $payment, array $verifiedData): ?string
    {
        return data_get($verifiedData, 'id')
            ?? data_get($verifiedData, 'metadata.transaction_id')
            ?? $payment->transaction_id;
    }
}
