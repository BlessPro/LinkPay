<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class PublicCheckoutSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_checkout_initializes_payment_and_clears_cart_session(): void
    {
        $seller = User::factory()->create([
            'plan_type' => User::PLAN_FREE_TRIAL,
        ]);

        $profile = SellerProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Smoke Seller',
            'public_slug' => 'smoke-seller',
            'paystack_subaccount_code' => 'ACCT_test_sub',
        ]);

        $product = Product::create([
            'user_id' => $seller->id,
            'name' => 'Smoke Product',
            'price' => '55.00',
            'is_active' => true,
            'status' => Product::STATUS_IN_STOCK,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
        ]);

        $paystackMock = Mockery::mock(PaystackService::class);
        $paystackMock->shouldReceive('platformChargeFor')
            ->once()
            ->andReturn('0.00');
        $paystackMock->shouldReceive('initializeTransaction')
            ->once()
            ->andReturn([
                'authorization_url' => 'https://paystack.test/checkout',
            ]);
        $this->app->instance(PaystackService::class, $paystackMock);

        $cartKey = 'public_cart:'.$profile->public_slug;
        Session::put($cartKey, [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ]);

        $response = $this->post(route('public.cart.checkout', $profile->public_slug), [
            'phone_number' => '0541900229',
            'phone_country' => '+233',
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $response->assertRedirect('https://paystack.test/checkout');

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame(Order::STATUS_PENDING_PAYMENT, $order->status);
        $this->assertSame(Payment::STATUS_PENDING, $order->payment_status);
        $this->assertSame('110.00', $order->total);

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame((string) $order->id, (string) $payment->order_id);
        $this->assertSame('110.00', (string) $payment->amount);

        $orderItem = OrderItem::query()->first();
        $this->assertNotNull($orderItem);
        $this->assertSame($product->id, $orderItem->product_id);
        $this->assertSame(2, $orderItem->quantity);

        $response->assertSessionMissing($cartKey);
    }
}
