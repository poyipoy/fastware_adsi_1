<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_claim_submissions', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('submission_date');
            $table->string('nama_produk', 255)->nullable()->after('no_pr');
        });
    }

    public function down(): void
    {
        Schema::table('mst_claim_submissions', function (Blueprint $table) {
            $table->dropColumn(['category', 'nama_produk']);
        });
    }
};
