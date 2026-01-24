<?php

namespace App\Services;

use App\Models\SellerProfile;
use App\Support\Money;
use Illuminate\Support\Facades\Http;

class PaystackService
{
    public function createOrUpdateSubaccount(SellerProfile $profile): array
    {
        $payload = [
            'business_name' => $profile->business_name,
            'settlement_bank' => $profile->settlement_bank_code,
            'account_number' => $profile->account_number,
            'percentage_charge' => config('services.paystack.subaccount_percent_charge', 0),
            'description' => $profile->business_name.' payouts',
        ];

        if ($profile->paystack_subaccount_code) {
            $response = $this->client()
                ->put('/subaccount/'.$profile->paystack_subaccount_code, $payload)
                ->throw()
                ->json();
        } else {
            $response = $this->client()
                ->post('/subaccount', $payload)
                ->throw()
                ->json();
        }

        return $response['data'] ?? [];
    }

    public function initializeTransaction(
        string $amount,
        string $email,
        array $metadata,
        ?string $subaccountCode,
        ?string $transactionCharge
    ): array {
        $minorAmount = Money::toMinor($amount);
        $payload = [
            'amount' => $minorAmount,
            'email' => $email,
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $metadata['reference'] ?? null,
            'metadata' => $metadata,
        ];

        if (config('services.paystack.callback_url')) {
            $payload['callback_url'] = config('services.paystack.callback_url');
        }

        if ($subaccountCode) {
            $payload['subaccount'] = $subaccountCode;
        }

        if ($transactionCharge !== null) {
            $charge = Money::toMinor($transactionCharge);
            $payload['transaction_charge'] = min($charge, $minorAmount);
        }

        $response = $this->client()
            ->post('/transaction/initialize', $payload)
            ->throw()
            ->json();

        return $response['data'] ?? [];
    }

    public function verifyTransaction(string $reference): array
    {
        return $this->client()
            ->get('/transaction/verify/'.$reference)
            ->throw()
            ->json();
    }

    private function client()
    {
        return Http::withToken(config('services.paystack.secret_key'))
            ->baseUrl('https://api.paystack.co');
    }
}
