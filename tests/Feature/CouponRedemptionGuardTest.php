<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Mockery;
use Tests\TestCase;

class CouponRedemptionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_coupon_field_is_ignored_in_phone_only_cart_checkout(): void
    {
        $seller = User::factory()->create(['plan_type' => User::PLAN_FREE_TRIAL]);
        $profile = SellerProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Guard Seller',
            'public_slug' => 'guard-coupon-seller',
            'paystack_subaccount_code' => 'ACCT_guard',
        ]);
        $product = Product::create([
            'user_id' => $seller->id,
            'name' => 'Guard Product',
            'price' => '60.00',
            'is_active' => true,
            'status' => Product::STATUS_IN_STOCK,
            'stock_quantity' => 50,
            'low_stock_threshold' => 5,
        ]);
        $coupon = Coupon::create([
            'user_id' => $seller->id,
            'code' => 'GUARD10',
            'discount_type' => Coupon::TYPE_PERCENT,
            'discount_value' => '10.00',
            'is_active' => true,
        ]);
        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'seller_id' => $seller->id,
            'customer_fingerprint' => Coupon::customerFingerprint('+233541900229'),
            'ip_address' => '127.0.0.1',
            'used_at' => now()->subHour(),
        ]);

        $paystackMock = Mockery::mock(PaystackService::class);
        $paystackMock->shouldReceive('platformChargeFor')
            ->once()
            ->with('60.00')
            ->andReturn('0.00');
        $paystackMock->shouldReceive('initializeTransaction')
            ->once()
            ->with('60.00', Mockery::type('string'), Mockery::type('array'), 'ACCT_guard', '0.00')
            ->andReturn([
                'authorization_url' => 'https://paystack.test/checkout',
            ]);
        $this->app->instance(PaystackService::class, $paystackMock);

        Session::put('public_cart:'.$profile->public_slug, [
            $product->id => ['product_id' => $product->id, 'quantity' => 1],
        ]);

        $response = $this->from(route('public.listing', $profile->public_slug))
            ->post(route('public.cart.checkout', $profile->public_slug), [
                'phone_number' => '0541900229',
                'phone_country' => '+233',
                'coupon_code' => 'GUARD10',
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            ]);

        $response->assertRedirect('https://paystack.test/checkout');
    }

    public function test_payment_success_records_coupon_redemption_with_ip(): void
    {
        $seller = User::factory()->create(['plan_type' => User::PLAN_FREE_TRIAL]);
        $coupon = Coupon::create([
            'user_id' => $seller->id,
            'code' => 'TRACKIP',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => '5.00',
            'is_active' => true,
        ]);

        $order = Order::create([
            'user_id' => $seller->id,
            'reference' => 'ORDER-TRACK-IP',
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Payment::STATUS_PENDING,
            'customer_phone' => '+233541900229',
            'subtotal' => '40.00',
            'coupon_code' => $coupon->code,
            'discount_amount' => '5.00',
            'total' => '35.00',
            'currency' => 'GHS',
        ]);

        $payment = Payment::create([
            'user_id' => $seller->id,
            'order_id' => $order->id,
            'reference' => 'PAY-TRACK-IP',
            'amount' => '35.00',
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'phone' => '+233541900229',
                    'ip_address' => '127.0.0.1',
                ],
            ],
        ]);

        app(PaymentService::class)->markSuccess($payment, [
            'status' => 'success',
            'channel' => 'card',
            'paid_at' => now()->toIso8601String(),
        ]);

        $this->assertDatabaseHas('coupon_redemptions', [
            'coupon_id' => $coupon->id,
            'seller_id' => $seller->id,
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'ip_address' => '127.0.0.1',
        ]);
    }
}
