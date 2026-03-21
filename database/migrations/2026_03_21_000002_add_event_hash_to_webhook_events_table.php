<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('event_hash', 64)->nullable()->after('event');
            $table->index(['provider', 'event_hash'], 'webhook_events_provider_event_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropIndex('webhook_events_provider_event_hash_idx');
            $table->dropColumn('event_hash');
        });
    }
};
