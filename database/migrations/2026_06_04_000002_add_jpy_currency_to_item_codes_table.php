<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('item_codes') || !Schema::hasColumn('item_codes', 'currency')) {
            return;
        }

        DB::statement("ALTER TABLE item_codes MODIFY currency ENUM('IDR','CNY','USD','JPY') NOT NULL");
    }

    public function down(): void
    {
        if (!Schema::hasTable('item_codes') || !Schema::hasColumn('item_codes', 'currency')) {
            return;
        }

        if (DB::table('item_codes')->where('currency', 'JPY')->exists()) {
            throw new RuntimeException('Cannot remove JPY from item_codes.currency while JPY rows still exist.');
        }

        DB::statement("ALTER TABLE item_codes MODIFY currency ENUM('IDR','CNY','USD') NOT NULL");
    }
};
