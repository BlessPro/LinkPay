<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('twilio_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('payment_id')->nullable()->index();
            $table->string('channel', 20)->index();
            $table->string('direction', 20)->default('outgoing');
            $table->string('to', 80)->nullable()->index();
            $table->string('from', 80)->nullable();
            $table->string('status', 60)->nullable()->index();
            $table->string('provider_sid', 80)->nullable()->index();
            $table->string('error_code', 40)->nullable()->index();
            $table->string('error_message', 1000)->nullable();
            $table->string('context_type', 60)->nullable()->index();
            $table->string('context_id', 100)->nullable()->index();
            $table->jsonb('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twilio_message_logs');
    }
};

