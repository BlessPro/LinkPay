<?php

namespace App\Services;

use App\Models\TwilioMessageLog;
use App\Support\Phone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class HubtelSmsService
{
    public function send(string $to, string $message, array $context = []): void
    {
        $this->ensureConfigured();

        $normalizedTo = $this->formatForHubtel($to);
        $response = Http::timeout(20)->get((string) config('services.hubtel.base_url'), [
            'clientid' => (string) config('services.hubtel.client_id'),
            'clientsecret' => (string) config('services.hubtel.client_secret'),
            'from' => (string) config('services.hubtel.sender_id'),
            'to' => $normalizedTo,
            'content' => $message,
        ]);

        if (! $response->successful()) {
            $this->log(array_merge($context, [
                'to' => $normalizedTo,
                'status' => 'failed',
                'error_code' => (string) $response->status(),
                'error_message' => $response->body(),
                'payload' => ['body' => $message],
            ]));

            throw new \RuntimeException('Hubtel SMS failed: HTTP '.$response->status());
        }

        $json = $response->json();
        if ($this->isProviderFailure($json)) {
            $this->log(array_merge($context, [
                'to' => $normalizedTo,
                'status' => 'failed',
                'error_code' => (string) ($json['code'] ?? $json['statusCode'] ?? $json['ResponseCode'] ?? 'provider_failure'),
                'error_message' => (string) ($json['message'] ?? $json['Message'] ?? 'Hubtel provider rejected message'),
                'payload' => ['body' => $message, 'response' => $json],
            ]));

            throw new \RuntimeException('Hubtel SMS provider rejected message.');
        }

        $providerId = is_array($json)
            ? ($json['MessageId'] ?? $json['messageId'] ?? $json['id'] ?? null)
            : null;

        $this->log(array_merge($context, [
            'to' => $normalizedTo,
            'status' => 'sent',
            'provider_sid' => is_scalar($providerId) ? (string) $providerId : null,
            'payload' => ['body' => $message, 'response' => $json],
        ]));
    }

    private function log(array $data): void
    {
        TwilioMessageLog::create([
            'user_id' => $data['user_id'] ?? null,
            'payment_id' => $data['payment_id'] ?? null,
            'channel' => 'sms',
            'direction' => 'outgoing',
            'to' => $data['to'] ?? null,
            'from' => (string) config('services.hubtel.sender_id'),
            'status' => $data['status'] ?? null,
            'provider_sid' => $data['provider_sid'] ?? null,
            'error_code' => $data['error_code'] ?? null,
            'error_message' => $data['error_message'] ?? null,
            'context_type' => $data['context_type'] ?? null,
            'context_id' => $data['context_id'] ?? null,
            'payload' => $data['payload'] ?? null,
            'sent_at' => Carbon::now(),
        ]);
    }

    private function ensureConfigured(): void
    {
        if (! config('services.hubtel.client_id') || ! config('services.hubtel.client_secret') || ! config('services.hubtel.sender_id')) {
            throw new \RuntimeException('Hubtel SMS is not configured (HUBTEL_CLIENT_ID/HUBTEL_CLIENT_SECRET/HUBTEL_SENDER_ID).');
        }
    }

    private function formatForHubtel(string $phone): string
    {
        $normalized = Phone::normalize($phone, '+233') ?: $phone;
        return ltrim($normalized, '+');
    }

    private function isProviderFailure($json): bool
    {
        if (! is_array($json)) {
            return false;
        }

        $status = strtolower((string) ($json['status'] ?? $json['Status'] ?? ''));
        if (in_array($status, ['error', 'failed', 'fail', 'rejected'], true)) {
            return true;
        }

        $responseCode = (string) ($json['ResponseCode'] ?? $json['responseCode'] ?? '');
        if ($responseCode !== '' && ! in_array($responseCode, ['0', '00', '0000'], true)) {
            return true;
        }

        $code = (string) ($json['code'] ?? $json['statusCode'] ?? '');
        if ($code !== '' && ! in_array($code, ['0', '200', '202'], true)) {
            return true;
        }

        return false;
    }
}
