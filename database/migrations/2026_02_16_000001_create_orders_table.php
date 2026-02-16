<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 80)->unique();
            $table->string('status', 40)->default(Order::STATUS_PENDING_PAYMENT)->index();
            $table->string('payment_status', 40)->default('PENDING')->index();
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_phone', 30);
            $table->string('customer_location', 180)->nullable();
            $table->boolean('delivery_required')->default(false);
            $table->string('delivery_note', 500)->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('currency', 10)->default('GHS');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

