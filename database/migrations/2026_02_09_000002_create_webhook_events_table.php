<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->default('paystack');
            $table->string('event', 120)->nullable();
            $table->string('reference')->nullable()->index();
            $table->uuid('payment_id')->nullable()->index();
            $table->string('status', 40)->default('received')->index();
            $table->string('verification_status', 40)->nullable()->index();
            $table->string('error_message', 1000)->nullable();
            $table->jsonb('payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};

