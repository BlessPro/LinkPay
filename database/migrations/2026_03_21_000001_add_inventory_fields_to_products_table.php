<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->default(0)->after('price');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock_quantity');
            $table->string('stock_alert_state', 32)->nullable()->after('status');
            $table->timestamp('stock_alerted_at')->nullable()->after('stock_alert_state');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'stock_quantity',
                'low_stock_threshold',
                'stock_alert_state',
                'stock_alerted_at',
            ]);
        });
    }
};
