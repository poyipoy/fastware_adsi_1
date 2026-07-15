<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// Migration 1: Add deskripsi_level_1-4 to mst_tcs
if (!Schema::hasColumn('mst_tcs', 'deskripsi_level_1')) {
    Schema::table('mst_tcs', function (Blueprint $table) {
        $table->text('deskripsi_level_1')->nullable();
        $table->text('deskripsi_level_2')->nullable();
        $table->text('deskripsi_level_3')->nullable();
        $table->text('deskripsi_level_4')->nullable();
    });
    echo "mst_tcs updated.\n";
} else {
    echo "mst_tcs already updated.\n";
}

// Migration 1: Add deskripsi_level_1-4 to mst_soft_skills
if (!Schema::hasColumn('mst_soft_skills', 'deskripsi_level_1')) {
    Schema::table('mst_soft_skills', function (Blueprint $table) {
        $table->text('deskripsi_level_1')->nullable();
        $table->text('deskripsi_level_2')->nullable();
        $table->text('deskripsi_level_3')->nullable();
        $table->text('deskripsi_level_4')->nullable();
    });
    echo "mst_soft_skills updated.\n";
} else {
    echo "mst_soft_skills already updated.\n";
}

// Migration 1: Add deskripsi_level_1-4 to mst_additionals
if (!Schema::hasColumn('mst_additionals', 'deskripsi_level_1')) {
    Schema::table('mst_additionals', function (Blueprint $table) {
        $table->text('deskripsi_level_1')->nullable();
        $table->text('deskripsi_level_2')->nullable();
        $table->text('deskripsi_level_3')->nullable();
        $table->text('deskripsi_level_4')->nullable();
    });
    echo "mst_additionals updated.\n";
} else {
    echo "mst_additionals already updated.\n";
}

// Migration 2: Rename categories in tc_poin_kategoris
DB::table('tc_poin_kategoris')->where('id', 1)->update(['poin_kategori' => 'Produksi']);
DB::table('tc_poin_kategoris')->where('id', 2)->update(['poin_kategori' => 'Office/Tekofis']);
DB::table('tc_poin_kategoris')->where('id', 3)->update(['poin_kategori' => 'EHS']);
echo "tc_poin_kategoris updated.\n";

echo "Done.";
