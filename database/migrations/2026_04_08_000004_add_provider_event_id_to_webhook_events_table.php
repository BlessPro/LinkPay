<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->string('provider_event_id', 120)->nullable()->after('provider');
            $table->index(['provider', 'provider_event_id'], 'webhook_events_provider_event_id_idx');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement("
                DELETE FROM webhook_events a
                USING webhook_events b
                WHERE a.id < b.id
                  AND a.provider = b.provider
                  AND a.provider_event_id IS NOT NULL
                  AND a.provider_event_id = b.provider_event_id
            ");
        } else {
            DB::statement("
                DELETE FROM webhook_events
                WHERE provider_event_id IS NOT NULL
                  AND id NOT IN (
                    SELECT MAX(id)
                    FROM webhook_events
                    WHERE provider_event_id IS NOT NULL
                    GROUP BY provider, provider_event_id
                  )
            ");
        }

        Schema::table('webhook_events', function (Blueprint $table) {
            $table->unique(['provider', 'provider_event_id'], 'webhook_events_provider_event_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_events', function (Blueprint $table) {
            $table->dropUnique('webhook_events_provider_event_id_unique');
            $table->dropIndex('webhook_events_provider_event_id_idx');
            $table->dropColumn('provider_event_id');
        });
    }
};

