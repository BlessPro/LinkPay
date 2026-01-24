<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('entity_type')->nullable();
            $table->string('entity_id')->nullable();
            $table->string('session_hash')->nullable();
            $table->string('ip_hash')->nullable();
            $table->string('user_agent_hash')->nullable();
            $table->string('device_type')->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'event_type']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['session_hash']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
    }
};
