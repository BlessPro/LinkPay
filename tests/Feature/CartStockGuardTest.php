<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartStockGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_to_cart_rejects_quantity_above_available_stock(): void
    {
        $seller = User::factory()->create([
            'plan_type' => User::PLAN_FREE_TRIAL,
        ]);

        $profile = SellerProfile::create([
            'user_id' => $seller->id,
            'business_name' => 'Guard Seller',
            'public_slug' => 'guard-seller',
        ]);

        $product = Product::create([
            'user_id' => $seller->id,
            'name' => 'Limited Item',
            'price' => '10.00',
            'is_active' => true,
            'status' => Product::STATUS_IN_STOCK,
            'stock_quantity' => 1,
            'low_stock_threshold' => 1,
        ]);

        $response = $this->from(route('public.listing', $profile->public_slug))
            ->post(route('public.products.cart.add', [
                'public_slug' => $profile->public_slug,
                'product' => $product->id,
            ]), [
                'quantity' => 2,
            ]);

        $response->assertRedirect(route('public.listing', $profile->public_slug));
        $response->assertSessionHasErrors('paystack');
    }
}
