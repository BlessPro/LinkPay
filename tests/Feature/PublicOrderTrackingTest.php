<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SellerNotification;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicOrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_track_order_with_reference_and_phone(): void
    {
        $seller = User::factory()->create();
        $phone = Phone::normalize('0541900229', '+233');

        $order = Order::create([
            'user_id' => $seller->id,
            'reference' => 'ORDER-TRACK-001',
            'status' => Order::STATUS_ACCEPTED,
            'payment_status' => Payment::STATUS_SUCCESS,
            'customer_name' => 'Buyer',
            'customer_phone' => $phone,
            'customer_location' => 'Accra',
            'delivery_required' => true,
            'delivery_note' => 'Call first',
            'subtotal' => '120.00',
            'discount_amount' => '0.00',
            'total' => '120.00',
            'currency' => 'GHS',
            'paid_at' => now()->subHour(),
        ]);

        Payment::create([
            'user_id' => $seller->id,
            'order_id' => $order->id,
            'reference' => 'PAY-TRACK-001',
            'amount' => '120.00',
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now()->subHour(),
            'raw_payload' => [],
        ]);

        SellerNotification::create([
            'user_id' => $seller->id,
            'type' => SellerNotification::TYPE_ORDER_ACCEPTED,
            'title' => 'Order accepted',
            'body' => 'Accepted for delivery.',
            'data' => ['order_id' => (string) $order->id],
        ]);

        $response = $this->get(route('public.orders.track', [
            'reference' => 'ORDER-TRACK-001',
            'phone_number' => '0541900229',
        ]));

        $response->assertOk();
        $response->assertSeeText('ORDER-TRACK-001');
        $response->assertSeeText('Payment confirmed');
        $response->assertSeeText('Order accepted');
    }

    public function test_tracking_fails_for_mismatched_phone(): void
    {
        $seller = User::factory()->create();

        Order::create([
            'user_id' => $seller->id,
            'reference' => 'ORDER-TRACK-002',
            'status' => Order::STATUS_PAID,
            'payment_status' => Payment::STATUS_SUCCESS,
            'customer_name' => 'Buyer',
            'customer_phone' => Phone::normalize('0541900229', '+233'),
            'customer_location' => 'Accra',
            'delivery_required' => false,
            'delivery_note' => null,
            'subtotal' => '99.00',
            'discount_amount' => '0.00',
            'total' => '99.00',
            'currency' => 'GHS',
            'paid_at' => now()->subHour(),
        ]);

        $response = $this->get(route('public.orders.track', [
            'reference' => 'ORDER-TRACK-002',
            'phone_number' => '0550000000',
        ]));

        $response->assertOk();
        $response->assertSeeText('Order not found');
        $response->assertDontSeeText('Order details');
    }
}
