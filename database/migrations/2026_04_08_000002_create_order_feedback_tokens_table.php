<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_feedback_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->string('token', 80)->unique();
            $table->string('phone', 30)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_feedback_tokens');
    }
};

