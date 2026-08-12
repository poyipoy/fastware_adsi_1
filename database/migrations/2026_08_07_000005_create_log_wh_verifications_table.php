<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_wh_verifications', function (Blueprint $table): void {
            $table->id();
            $table->char('scanned_code_hash', 64);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('trs_wh_stock_transactions')->nullOnDelete();
            $table->string('status', 20);
            $table->string('failure_reason', 120)->nullable();
            $table->timestamp('verified_at')->useCurrent();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->index(['status', 'verified_at'], 'wh_log_status_at_idx');
            $table->index(['user_id', 'verified_at'], 'wh_log_user_at_idx');
            $table->index(['transaction_id'], 'wh_log_transaction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_wh_verifications');
    }
};
