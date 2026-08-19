<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_wh_restricted_verifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('scope', 30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'scope'], 'wh_restricted_verifier_user_scope_unique');
            $table->index(['scope', 'is_active'], 'wh_restricted_verifier_scope_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_wh_restricted_verifiers');
    }
};
