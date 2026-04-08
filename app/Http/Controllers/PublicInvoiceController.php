<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AnalyticsService;
use App\Services\OgImageService;
use App\Services\PaymentService;
use App\Services\PaystackService;
use App\Support\Email;
use App\Support\Money;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\RequestException;

class PublicInvoiceController extends Controller
{
    public function show(string $token, Request $request, AnalyticsService $analytics)
    {
        $invoice = Invoice::where('token', $token)
            ->with(['user.sellerProfile', 'payments'])
            ->firstOrFail();

        $analytics->trackEvent(
            $request,
            $invoice->user_id,
            \App\Models\AnalyticsEvent::TYPE_INVOICE_VIEW,
            'invoice',
            (string) $invoice->id
        );

        $sellerName = $invoice->user->sellerProfile?->business_name ?? 'Seller';
        $ogService = app(OgImageService::class);
        $ogPath = $ogService->publicInvoiceOgPath((string) $invoice->id);
        if (! Storage::disk('public')->exists($ogPath)) {
            try {
                $invoice->loadMissing('user.sellerProfile');
                $ogService->generateInvoice($invoice);
            } catch (\Throwable $e) {
                // ignore
            }
        }
        $ogImage = Storage::disk('public')->exists($ogPath)
            ? url(Storage::url($ogPath))
            : url('/images/og-default.jpg');

        $currency = config('services.paystack.currency', 'GHS');

        return view('public.invoice', [
            'invoice' => $invoice,
            'seller' => $invoice->user->sellerProfile,
            'amountDue' => $invoice->amountDue(),
            'balance' => $invoice->balanceRemaining(),
            'currency' => $currency,
            'paymentsEnabled' => $invoice->user?->canUsePaymentsFeature() ?? false,
            'ogTitle' => $sellerName.' - '.$invoice->title,
            'ogDescription' => 'Amount due: '.Money::format($invoice->amountDue(), $currency).'. Tap to view and pay securely.',
            'ogImage' => $ogImage,
            'ogUrl' => route('public.invoice', $invoice->token),
            'ogType' => 'website',
        ]);
    }

    public function pay(Request $request, string $token, PaystackService $paystack, AnalyticsService $analytics)
    {
        $validated = $request->validate([
            'phone_number' => [
                'required',
                'string',
                'max:25',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $parts = array_filter(array_map('trim', explode(',', (string) $value)));
                    $primaryPhone = $parts[0] ?? $value;
                    if (! Phone::isValidGh((string) $primaryPhone)) {
                        $fail('Please enter a valid Ghana phone number (example: 0541900229).');
                    }
                },
            ],
            'phone_country' => ['nullable', 'string', 'max:8'],
            'location' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:120'],
        ], [
            'phone_number.required' => 'A phone number is required so the seller can reach you.',
        ]);

        $invoice = Invoice::where('token', $token)
            ->with('user.sellerProfile')
            ->firstOrFail();

        $sellerUser = $invoice->user;
        if ($sellerUser) {
            if (! $sellerUser->canUsePaymentsFeature()) {
                return back()->withErrors([
                    'paystack' => 'This seller is not on the Payments plan. Please use Chat on WhatsApp.',
                ])->withInput();
            }
        }

        if ($invoice->status === Invoice::STATUS_PAID) {
            return back()->withErrors(['invoice' => 'This invoice is already fully paid.']);
        }

        $seller = $invoice->user->sellerProfile;
        if (! $seller || ! $seller->paystack_subaccount_code) {
            return back()->withErrors(['paystack' => 'Seller is not connected to Paystack yet.']);
        }

        $amountDue = $invoice->amountDue();
        if (Money::compare($amountDue, '0.00') !== 1) {
            return back()->withErrors(['invoice' => 'No balance due on this invoice.']);
        }

        $reference = (string) Str::uuid();
        $phoneInput = $validated['phone_number'];
        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phoneInput)));
        $primaryPhone = $phoneParts[0] ?? $phoneInput;
        $phone = Phone::normalize($primaryPhone, $validated['phone_country'] ?? '+233');
        $location = trim((string) ($validated['location'] ?? ''));
        $note = trim((string) ($validated['note'] ?? ''));
        if (! $phone) {
            return back()->withErrors(['phone_number' => 'Please enter a valid phone number.'])->withInput();
        }
        $idempotencyKey = (string) $validated['idempotency_key'];
        $idempotencyScope = 'public_invoice_pay:'.$invoice->id;
        if (! $this->acquireIdempotencyKey($request, $idempotencyScope, $idempotencyKey)) {
            return back()->withErrors([
                'paystack' => 'This payment request is already being processed. Please wait a moment.',
            ])->withInput();
        }

        $email = Email::placeholder($reference);
        $analytics->trackEvent(
            $request,
            $invoice->user_id,
            \App\Models\AnalyticsEvent::TYPE_INVOICE_CLICK,
            'invoice',
            (string) $invoice->id
        );

        $payment = Payment::create([
            'user_id' => $invoice->user_id,
            'invoice_id' => $invoice->id,
            'reference' => $reference,
            'amount' => $amountDue,
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location !== '' ? $location : null,
                    'note' => $note !== '' ? $note : null,
                ],
            ],
        ]);

        $platformFee = $paystack->platformChargeFor((string) $amountDue);

        try {
            $data = $paystack->initializeTransaction(
                $amountDue,
                $email,
                [
                    'reference' => $reference,
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'purpose' => 'invoice',
                    'platform_fee' => $platformFee,
                    'customer' => [
                        'email' => $email,
                        'phone' => $phone,
                        'location' => $location !== '' ? $location : null,
                        'note' => $note !== '' ? $note : null,
                    ],
                ],
                $seller->paystack_subaccount_code,
                $platformFee
            );
        } catch (RequestException $exception) {
            $this->releaseIdempotencyKey($request, $idempotencyScope, $idempotencyKey);
            $payment->status = Payment::STATUS_FAILED;
            $payment->raw_payload = array_merge($payment->raw_payload ?? [], [
                'initialize_error' => $exception->getMessage(),
            ]);
            $payment->save();

            return back()->withErrors([
                'paystack' => 'Could not initialize payment. Please confirm seller Paystack connection and try again.',
            ])->withInput();
        }

        return redirect()->away($data['authorization_url'] ?? route('public.invoice', $token));
    }

    public function success(Request $request, PaystackService $paystack, PaymentService $payments)
    {
        $reference = $request->query('reference') ?: $request->query('trxref');
        $payment = null;

        if ($reference) {
            $payment = Payment::where('reference', $reference)
                ->with(['invoice.user.sellerProfile', 'product', 'order.user.sellerProfile'])
                ->first();

            if (! $payment) {
                try {
                    $verification = $paystack->verifyTransaction($reference);
                    $paymentId = data_get($verification, 'data.metadata.payment_id');
                    if ($paymentId) {
                        $payment = Payment::where('id', $paymentId)
                            ->with(['invoice.user.sellerProfile', 'product', 'order.user.sellerProfile'])
                            ->first();
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Paystack verification failed on success page (payment lookup)', [
                        'reference' => $reference,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }

            if ($payment && $payment->status !== Payment::STATUS_SUCCESS) {
                try {
                    $verification = $paystack->verifyTransaction($reference);
                    if (data_get($verification, 'data.status') === 'success') {
                        $payments->markSuccess($payment, data_get($verification, 'data', []));
                        $payment->refresh();
                    }
                } catch (\Throwable $exception) {
                    Log::warning('Paystack verification failed on success page', [
                        'reference' => $reference,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $listingSlug = null;
        if ($payment) {
            $listingSlug = $payment->invoice?->user?->sellerProfile?->public_slug
                ?? $payment->product?->user?->sellerProfile?->public_slug
                ?? $payment->order?->user?->sellerProfile?->public_slug;
        }
        $listingUrl = $listingSlug ? route('public.listing', $listingSlug) : null;

        return view('public.success', [
            'payment' => $payment,
            'reference' => $reference,
            'currency' => config('services.paystack.currency', 'GHS'),
            'listingUrl' => $listingUrl,
        ]);
    }

    private function acquireIdempotencyKey(Request $request, string $scope, string $key): bool
    {
        return Cache::add($this->idempotencyCacheKey($request, $scope, $key), now()->toIso8601String(), now()->addMinutes(15));
    }

    private function releaseIdempotencyKey(Request $request, string $scope, string $key): void
    {
        Cache::forget($this->idempotencyCacheKey($request, $scope, $key));
    }

    private function idempotencyCacheKey(Request $request, string $scope, string $key): string
    {
        return 'idem:'.sha1($scope.'|'.$request->ip().'|'.$key);
    }
}
