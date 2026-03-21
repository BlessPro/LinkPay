<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_checkout_applies_coupon_discount_to_order_and_payment(): void
    {
        $seller = User::factory()->create([
            'plan_type' => User::PLAN_FREE_TRIAL,
        ]);

        $profile = SellerProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Coupon Seller',
            'public_slug' => 'coupon-seller',
            'paystack_subaccount_code' => 'ACCT_test_sub',
        ]);

        $product = Product::create([
            'user_id' => $seller->id,
            'name' => 'Coupon Product',
            'price' => '50.00',
            'is_active' => true,
            'status' => Product::STATUS_IN_STOCK,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
        ]);

        Coupon::create([
            'user_id' => $seller->id,
            'code' => 'SAVE10',
            'discount_type' => Coupon::TYPE_PERCENT,
            'discount_value' => '10.00',
            'is_active' => true,
        ]);

        $paystackMock = Mockery::mock(PaystackService::class);
        $paystackMock->shouldReceive('platformChargeFor')
            ->once()
            ->with('90.00')
            ->andReturn('0.00');
        $paystackMock->shouldReceive('initializeTransaction')
            ->once()
            ->with('90.00', Mockery::type('string'), Mockery::type('array'), 'ACCT_test_sub', '0.00')
            ->andReturn([
                'authorization_url' => 'https://paystack.test/checkout',
            ]);
        $this->app->instance(PaystackService::class, $paystackMock);

        Session::put('public_cart:'.$profile->public_slug, [
            $product->id => [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ]);

        $response = $this->post(route('public.cart.checkout', $profile->public_slug), [
            'name' => 'Coupon Buyer',
            'phone_number' => '0541900229',
            'phone_country' => '+233',
            'coupon_code' => 'SAVE10',
        ]);

        $response->assertRedirect('https://paystack.test/checkout');

        $order = Order::query()->first();
        $this->assertNotNull($order);
        $this->assertSame('100.00', (string) $order->subtotal);
        $this->assertSame('10.00', (string) $order->discount_amount);
        $this->assertSame('90.00', (string) $order->total);
        $this->assertSame('SAVE10', $order->coupon_code);

        $payment = Payment::query()->first();
        $this->assertNotNull($payment);
        $this->assertSame('90.00', (string) $payment->amount);
    }
}
