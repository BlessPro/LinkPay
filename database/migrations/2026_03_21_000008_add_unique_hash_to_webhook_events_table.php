<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("
                DELETE FROM webhook_events a
                USING webhook_events b
                WHERE a.id < b.id
                  AND a.provider = b.provider
                  AND a.event_hash IS NOT NULL
                  AND a.event_hash = b.event_hash
            ");
        } else {
            DB::statement("
                DELETE FROM webhook_events
                WHERE event_hash IS NOT NULL
                  AND id NOT IN (
                    SELECT MAX(id)
                    FROM webhook_events
                    WHERE event_hash IS NOT NULL
                    GROUP BY provider, event_hash
                  )
            ");
        }

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->unique(['provider', 'event_hash'], 'webhook_events_provider_event_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique('webhook_events_provider_event_hash_unique');
        });
    }
};
