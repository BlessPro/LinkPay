<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_cart_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('public_slug');
            $table->string('session_token', 64);
            $table->json('cart_payload');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['public_slug', 'session_token']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_cart_sessions');
    }
};

