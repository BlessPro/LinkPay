<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_processed_webhook_payload_is_ignored(): void
    {
        config()->set('services.paystack.secret_key', 'test_secret');

        $payloadArray = [
            'event' => 'charge.success',
            'data' => [
                'reference' => 'DUP-REF-001',
            ],
        ];
        $payload = json_encode($payloadArray, JSON_THROW_ON_ERROR);
        $eventHash = hash('sha256', $payload);
        $signature = hash_hmac('sha512', $payload, 'test_secret');

        WebhookEvent::create([
            'provider' => 'paystack',
            'event' => 'charge.success',
            'event_hash' => $eventHash,
            'reference' => 'DUP-REF-001',
            'status' => WebhookEvent::STATUS_PROCESSED,
            'payload' => $payloadArray,
            'received_at' => now()->subMinute(),
        ]);

        $paystackMock = Mockery::mock(PaystackService::class);
        $paystackMock->shouldNotReceive('verifyTransaction');
        $this->app->instance(PaystackService::class, $paystackMock);

        $response = $this->call(
            'POST',
            '/webhooks/paystack',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertOk();
        $response->assertJson(['status' => 'duplicate_ignored']);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_duplicate_failed_webhook_payload_reuses_event_and_processes_successfully(): void
    {
        config()->set('services.paystack.secret_key', 'test_secret');

        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id,
            'reference' => 'DUP-REF-FAILED-001',
            'amount' => '25.00',
            'status' => Payment::STATUS_PENDING,
        ]);

        $payloadArray = [
            'event' => 'charge.success',
            'data' => [
                'reference' => $payment->reference,
            ],
        ];
        $payload = json_encode($payloadArray, JSON_THROW_ON_ERROR);
        $eventHash = hash('sha256', $payload);
        $signature = hash_hmac('sha512', $payload, 'test_secret');

        $existing = WebhookEvent::create([
            'provider' => 'paystack',
            'event' => 'charge.success',
            'event_hash' => $eventHash,
            'reference' => $payment->reference,
            'payment_id' => $payment->id,
            'status' => WebhookEvent::STATUS_FAILED,
            'verification_status' => 'exception',
            'error_message' => 'Old failure',
            'payload' => $payloadArray,
            'received_at' => now()->subMinute(),
        ]);

        $paystackMock = Mockery::mock(PaystackService::class);
        $paystackMock->shouldReceive('verifyTransaction')
            ->once()
            ->with($payment->reference)
            ->andReturn([
                'data' => [
                    'status' => 'success',
                    'reference' => $payment->reference,
                    'channel' => 'card',
                    'paid_at' => now()->toIso8601String(),
                ],
            ]);
        $this->app->instance(PaystackService::class, $paystackMock);

        $response = $this->call(
            'POST',
            '/webhooks/paystack',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_PAYSTACK_SIGNATURE' => $signature,
            ],
            $payload
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        $payment->refresh();
        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);

        $existing->refresh();
        $this->assertSame(WebhookEvent::STATUS_PROCESSED, $existing->status);
        $this->assertSame('success', $existing->verification_status);
        $this->assertNull($existing->error_message);
        $this->assertDatabaseCount('webhook_events', 1);
    }
}
