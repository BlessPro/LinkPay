<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->unique()->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'phone_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            });
        }
    }

    public function down(): void
    {
        $columnsToDrop = [];
        if (Schema::hasColumn('users', 'phone')) {
            $columnsToDrop[] = 'phone';
        }
        if (Schema::hasColumn('users', 'phone_verified_at')) {
            $columnsToDrop[] = 'phone_verified_at';
        }

        if (! empty($columnsToDrop)) {
            Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
