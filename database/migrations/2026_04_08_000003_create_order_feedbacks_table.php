<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->uuid('order_feedback_token_id')->nullable()->index();
            $table->string('type', 30)->index(); // RECEIVED | REPORTED
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('note')->nullable();
            $table->text('issue_note')->nullable();
            $table->string('issue_photo_path')->nullable();
            $table->string('admin_status', 30)->default('PENDING')->index(); // PENDING | REFUND_APPROVED | IGNORED
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('order_feedback_token_id')->references('id')->on('order_feedback_tokens')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_feedbacks');
    }
};

