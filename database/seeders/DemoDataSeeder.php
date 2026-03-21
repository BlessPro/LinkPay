<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@8kommerce.demo'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
                'plan_type' => User::PLAN_ENTERPRISE,
                'plan_started_at' => now()->subMonth(),
            ]
        );

        $sellers = [
            ['name' => 'Apex Gadgets', 'email' => 'apex@8kommerce.demo', 'phone' => '+233541900221', 'slug' => 'apex-gadgets'],
            ['name' => 'Nubia Beauty', 'email' => 'nubia@8kommerce.demo', 'phone' => '+233541900222', 'slug' => 'nubia-beauty'],
            ['name' => 'Moda Lane', 'email' => 'moda@8kommerce.demo', 'phone' => '+233541900223', 'slug' => 'moda-lane'],
        ];

        foreach ($sellers as $index => $row) {
            $seller = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'phone' => $row['phone'],
                    'email_verified_at' => now(),
                    'plan_type' => User::PLAN_PAYMENTS,
                    'plan_started_at' => now()->subDays(20),
                    'plan_ends_at' => now()->addMonths(11),
                ]
            );

            $profile = SellerProfile::updateOrCreate(
                ['user_id' => $seller->id],
                [
                    'business_name' => $row['name'],
                    'phone' => $row['phone'],
                    'public_slug' => $row['slug'],
                    'paystack_subaccount_code' => 'ACCT_demo_'.($index + 1),
                    'settlement_bank_code' => 'MTN',
                    'account_number' => '054190022'.($index + 1),
                    'account_name' => $row['name'],
                    'payout_method' => 'MOMO',
                ]
            );

            $featured = Product::updateOrCreate(
                ['user_id' => $seller->id, 'slug' => $row['slug'].'-featured'],
                [
                    'name' => $row['name'].' Featured Item',
                    'description' => 'Top performer for demo storefront.',
                    'price' => (string) (120 + ($index * 35)),
                    'is_active' => true,
                    'status' => Product::STATUS_IN_STOCK,
                    'stock_quantity' => 25,
                    'low_stock_threshold' => 5,
                ]
            );

            Product::updateOrCreate(
                ['user_id' => $seller->id, 'slug' => $row['slug'].'-daily'],
                [
                    'name' => $row['name'].' Daily Pick',
                    'description' => 'Secondary demo product.',
                    'price' => (string) (80 + ($index * 20)),
                    'is_active' => true,
                    'status' => Product::STATUS_LOW_STOCK,
                    'stock_quantity' => 4,
                    'low_stock_threshold' => 5,
                ]
            );

            Coupon::updateOrCreate(
                ['user_id' => $seller->id, 'code' => 'DEMO10'],
                [
                    'type' => Coupon::TYPE_PERCENT,
                    'value' => '10.00',
                    'min_order_amount' => '50.00',
                    'is_active' => true,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addDays(90),
                ]
            );

            $invoice = Invoice::updateOrCreate(
                ['user_id' => $seller->id, 'title' => 'Demo invoice '.($index + 1)],
                [
                    'token' => Str::random(32),
                    'description' => 'Invoice generated for launch readiness demo.',
                    'total_amount' => '300.00',
                    'paid_total' => '120.00',
                    'payment_mode' => Invoice::MODE_PARTIAL,
                    'deposit_amount' => '100.00',
                    'status' => Invoice::STATUS_PARTIAL,
                    'customer_name' => 'Demo Customer',
                ]
            );

            $order = Order::updateOrCreate(
                ['reference' => 'DEMO-ORDER-'.($index + 1)],
                [
                    'user_id' => $seller->id,
                    'status' => Order::STATUS_PAID,
                    'payment_status' => Payment::STATUS_SUCCESS,
                    'customer_name' => 'Demo Buyer',
                    'customer_phone' => '+23354190024'.($index + 1),
                    'customer_location' => 'Accra',
                    'delivery_required' => true,
                    'delivery_note' => 'Demo delivery note',
                    'subtotal' => '200.00',
                    'discount_amount' => '20.00',
                    'coupon_code' => 'DEMO10',
                    'total' => '180.00',
                    'currency' => 'GHS',
                    'paid_at' => now()->subDays($index + 1),
                ]
            );

            OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'product_id' => $featured->id],
                [
                    'product_name' => $featured->name,
                    'unit_price' => (string) $featured->price,
                    'quantity' => 1,
                    'line_total' => (string) $featured->price,
                ]
            );

            Payment::updateOrCreate(
                ['reference' => 'DEMO-PAY-'.($index + 1)],
                [
                    'user_id' => $seller->id,
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'amount' => '180.00',
                    'status' => Payment::STATUS_SUCCESS,
                    'channel' => 'card',
                    'paid_at' => now()->subDays($index + 1),
                    'verified_at' => now()->subDays($index + 1),
                    'raw_payload' => [
                        'metadata' => [
                            'purpose' => 'order',
                            'demo' => true,
                        ],
                    ],
                ]
            );
        }

        if ($admin) {
            // explicit noop branch keeps static analyzers happy for seeded admin usage.
        }
    }
}

