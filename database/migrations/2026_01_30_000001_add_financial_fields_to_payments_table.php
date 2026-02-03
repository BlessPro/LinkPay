<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('commission_amount', 12, 2)->default(0)->after('amount');
            $table->decimal('transaction_fee', 12, 2)->default(0)->after('commission_amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('transaction_fee');
            $table->string('receiving_account')->nullable()->after('tax_amount');
            $table->string('transaction_code')->nullable()->after('receiving_account');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'commission_amount',
                'transaction_fee',
                'tax_amount',
                'receiving_account',
                'transaction_code',
            ]);
        });
    }
};
