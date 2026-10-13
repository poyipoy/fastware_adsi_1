<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outstanding_materials', function (Blueprint $table) {
            $table->string('month_order')->nullable()->after('number_po');
        });
    }

    public function down(): void
    {
        Schema::table('outstanding_materials', function (Blueprint $table) {
            $table->dropColumn('month_order');
        });
    }
};
