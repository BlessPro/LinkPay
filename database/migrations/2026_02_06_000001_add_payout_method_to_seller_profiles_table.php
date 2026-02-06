<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->string('payout_method', 16)->default('MOMO')->after('paystack_subaccount_code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn('payout_method');
        });
    }
};

