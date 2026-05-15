<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('item_codes', 'amount')) {
            return;
        }

        Schema::table('item_codes', function (Blueprint $table) {
            $table->dropColumn('amount');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('item_codes', 'amount')) {
            return;
        }

        Schema::table('item_codes', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable()->after('qty');
        });
    }
};
