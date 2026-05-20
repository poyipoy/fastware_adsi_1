<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_inquiry_import', function (Blueprint $table) {
            $table->string('keterangan_order')->nullable()->after('ship');
            $table->string('keterangan_size')->nullable()->after('keterangan_order');
        });
    }

    public function down(): void
    {
        Schema::table('detail_inquiry_import', function (Blueprint $table) {
            $table->dropColumn(['keterangan_order', 'keterangan_size']);
        });
    }
};
