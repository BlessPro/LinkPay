<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentMarkSuccessRaceGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_success_is_idempotent_for_stock_and_coupon_side_effects(): void
    {
        $seller = User::factory()->create([
            'plan_type' => User::PLAN_FREE_TRIAL,
        ]);

        SellerProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Race Guard Seller',
            'public_slug' => 'race-guard-seller',
            'paystack_subaccount_code' => 'ACCT_test_sub',
        ]);

        $product = Product::create([
            'user_id' => $seller->id,
            'name' => 'Race Product',
            'slug' => 'race-product',
            'price' => '100.00',
            'is_active' => true,
            'status' => Product::STATUS_IN_STOCK,
            'stock_quantity' => 5,
            'low_stock_threshold' => 2,
        ]);

        $coupon = Coupon::create([
            'user_id' => $seller->id,
            'code' => 'RACE10',
            'discount_type' => Coupon::TYPE_PERCENT,
            'discount_value' => '10.00',
            'is_active' => true,
            'used_count' => 0,
        ]);

        $order = Order::create([
            'user_id' => $seller->id,
            'reference' => 'RACE-ORDER-001',
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Payment::STATUS_PENDING,
            'customer_name' => 'Buyer',
            'customer_phone' => '+233541900229',
            'customer_location' => 'Accra',
            'delivery_required' => false,
            'subtotal' => '200.00',
            'discount_amount' => '20.00',
            'coupon_code' => $coupon->code,
            'total' => '180.00',
            'currency' => 'GHS',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => '100.00',
            'quantity' => 2,
            'line_total' => '200.00',
        ]);

        $payment = Payment::create([
            'user_id' => $seller->id,
            'order_id' => $order->id,
            'reference' => 'RACE-PAY-001',
            'amount' => '180.00',
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'phone' => '+233541900229',
                    'ip_address' => '127.0.0.1',
                ],
            ],
        ]);

        $service = app(PaymentService::class);
        $verifiedData = [
            'status' => 'success',
            'channel' => 'card',
            'paid_at' => now()->toIso8601String(),
        ];

        $service->markSuccess($payment, $verifiedData);
        $service->markSuccess($payment, $verifiedData);

        $payment->refresh();
        $order->refresh();
        $product->refresh();
        $coupon->refresh();

        $this->assertSame(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertSame(Order::STATUS_PAID, $order->status);
        $this->assertSame('3', (string) $product->stock_quantity);
        $this->assertSame(1, $coupon->used_count);
        $this->assertDatabaseCount('coupon_redemptions', 1);
    }
}

