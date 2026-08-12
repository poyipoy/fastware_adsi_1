<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_wh_user_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('card_code', 150)->collation('utf8mb4_bin')->unique();
            $table->boolean('is_active')->default(true);
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'is_active'], 'wh_card_user_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_wh_user_cards');
    }
};
