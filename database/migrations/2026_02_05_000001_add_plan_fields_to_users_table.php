<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plan_type', 32)->nullable()->index();
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable()->index();
            $table->timestamp('plan_started_at')->nullable();
            $table->timestamp('plan_ends_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'plan_type',
                'trial_started_at',
                'trial_ends_at',
                'plan_started_at',
                'plan_ends_at',
            ]);
        });
    }
};

