<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => '8Kommerce Seller',
            'email' => 'seller@example.com',
        ]);

        $profile = SellerProfile::create([
            'user_id' => $user->id,
            'business_name' => '8Kommerce Studio',
            'phone' => '+2330000000',
            'public_slug' => SellerProfile::generateUniqueSlug('8Kommerce Studio'),
        ]);

        Product::create([
            'user_id' => $user->id,
            'name' => 'Logo Design',
            'description' => 'Custom logo with two revisions.',
            'price' => '120.00',
            'is_active' => true,
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'token' => Str::random(32),
            'title' => 'Website Sprint',
            'description' => 'Two-week build with weekly check-ins.',
            'total_amount' => '800.00',
            'paid_total' => '0.00',
            'payment_mode' => Invoice::MODE_PARTIAL,
            'deposit_amount' => '200.00',
            'status' => Invoice::STATUS_PENDING,
            'customer_name' => 'Nia Boateng',
        ]);
    }
}
