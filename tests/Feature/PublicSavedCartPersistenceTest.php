<?php

namespace Tests\Feature;

use App\Models\PublicCartSession;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicSavedCartPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_is_restored_for_same_browser_token_after_session_reset(): void
    {
        $seller = User::factory()->create([
            'plan_type' => User::PLAN_FREE_TRIAL,
        ]);

        $profile = SellerProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Persistent Cart Seller',
            'public_slug' => 'persistent-cart-seller',
            'paystack_subaccount_code' => 'ACCT_test_sub',
        ]);

        $product = Product::create([
            'user_id' => $seller->id,
            'name' => 'Persistent Product',
            'slug' => 'persistent-product',
            'price' => '80.00',
            'is_active' => true,
            'status' => Product::STATUS_IN_STOCK,
            'stock_quantity' => 15,
            'low_stock_threshold' => 3,
        ]);

        $token = (string) Str::uuid();

        $this->withCookie('lp_cart_token', $token)
            ->post(route('public.products.cart.add', [$profile->public_slug, $product]), [
                'quantity' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('public_cart_sessions', [
            'public_slug' => $profile->public_slug,
            'session_token' => $token,
        ]);

        $this->flushSession();

        $this->withCookie('lp_cart_token', $token)
            ->get(route('public.listing', $profile->public_slug))
            ->assertOk()
            ->assertSeeText('1 item(s)');
    }

    public function test_checkout_clears_persisted_cart_for_browser_token(): void
    {
        $seller = User::factory()->create([
            'plan_type' => User::PLAN_FREE_TRIAL,
        ]);

        $profile = SellerProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Clear Cart Seller',
            'public_slug' => 'clear-cart-seller',
            'paystack_subaccount_code' => 'ACCT_test_sub',
        ]);

        $product = Product::create([
            'user_id' => $seller->id,
            'name' => 'Checkout Product',
            'slug' => 'checkout-product',
            'price' => '55.00',
            'is_active' => true,
            'status' => Product::STATUS_IN_STOCK,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
        ]);

        $token = (string) Str::uuid();

        PublicCartSession::create([
            'public_slug' => $profile->public_slug,
            'session_token' => $token,
            'cart_payload' => [
                $product->id => [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'expires_at' => now()->addDays(10),
        ]);

        $paystackMock = \Mockery::mock(\App\Services\PaystackService::class);
        $paystackMock->shouldReceive('platformChargeFor')->once()->andReturn('0.00');
        $paystackMock->shouldReceive('initializeTransaction')->once()->andReturn([
            'authorization_url' => 'https://paystack.test/checkout',
        ]);
        $this->app->instance(\App\Services\PaystackService::class, $paystackMock);

        $this->withCookie('lp_cart_token', $token)
            ->post(route('public.cart.checkout', $profile->public_slug), [
                'phone_number' => '0541900229',
                'phone_country' => '+233',
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            ])
            ->assertRedirect('https://paystack.test/checkout');

        $this->assertDatabaseMissing('public_cart_sessions', [
            'public_slug' => $profile->public_slug,
            'session_token' => $token,
        ]);
    }
}
