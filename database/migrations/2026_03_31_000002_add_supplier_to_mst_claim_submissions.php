<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_claim_submissions', function (Blueprint $table) {
            $table->string('supplier', 255)->nullable()->after('modified_at');
        });
    }

    public function down(): void
    {
        Schema::table('mst_claim_submissions', function (Blueprint $table) {
            $table->dropColumn('supplier');
        });
    }
};
