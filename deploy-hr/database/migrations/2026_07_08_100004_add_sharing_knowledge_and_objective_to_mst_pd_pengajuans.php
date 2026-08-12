<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->boolean('is_sharing_knowledge')
                  ->default(false)
                  ->after('status_2')
                  ->comment('1 = entry dari tombol Sharing Knowledge di section Additional');
            $table->text('objective_learning')
                  ->nullable()
                  ->after('is_sharing_knowledge')
                  ->comment('Hasil yang diharapkan dari training (field di form /buat-training)');
        });
    }

    public function down(): void
    {
        Schema::table('mst_pd_pengajuans', function (Blueprint $table) {
            $table->dropColumn(['is_sharing_knowledge', 'objective_learning']);
        });
    }
};
