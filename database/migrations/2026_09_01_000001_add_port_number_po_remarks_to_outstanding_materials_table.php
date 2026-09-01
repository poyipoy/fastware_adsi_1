<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outstanding_materials', function (Blueprint $table) {
            $table->string('port', 100)->nullable()->after('estimasi_delay_eta_warehouse');
            $table->string('number_po', 255)->nullable()->after('port');
            $table->text('remarks')->nullable()->after('number_po');

            $table->index('port', 'idx_om_port');
            $table->index('number_po', 'idx_om_number_po');
        });
    }

    public function down(): void
    {
        Schema::table('outstanding_materials', function (Blueprint $table) {
            $table->dropIndex('idx_om_port');
            $table->dropIndex('idx_om_number_po');

            $table->dropColumn(['port', 'number_po', 'remarks']);
        });
    }
};
