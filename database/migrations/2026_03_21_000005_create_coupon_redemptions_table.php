<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('order_id')->nullable()->index();
            $table->uuid('payment_id')->nullable()->index();
            $table->string('customer_fingerprint', 64)->index();
            $table->string('ip_address', 64)->nullable()->index();
            $table->timestamp('used_at');
            $table->timestamps();

            $table->unique(['coupon_id', 'customer_fingerprint'], 'coupon_redemptions_unique_customer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
