<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outstanding_materials')) {
            if (! Schema::hasColumn('outstanding_materials', 'archived_at')) {
                Schema::table('outstanding_materials', function (Blueprint $table): void {
                    $table->timestamp('archived_at')->nullable()->after('keterangan');
                });
            }

            $hasArchiveIndex = count(DB::select(
                "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_materials_archived_at_idx'"
            )) > 0;

            if (! $hasArchiveIndex) {
                Schema::table('outstanding_materials', function (Blueprint $table): void {
                    $table->index('archived_at', 'om_materials_archived_at_idx');
                });
            }

            // Synchronize materials whose invoice header is already archived
            if (Schema::hasTable('outstanding_material_invoices') && Schema::hasColumn('outstanding_material_invoices', 'archived_at')) {
                DB::table('outstanding_materials')
                    ->join('outstanding_material_invoices', 'outstanding_material_invoices.id', '=', 'outstanding_materials.invoice_id')
                    ->whereNotNull('outstanding_material_invoices.archived_at')
                    ->whereNull('outstanding_materials.archived_at')
                    ->update([
                        'outstanding_materials.archived_at' => DB::raw('outstanding_material_invoices.archived_at'),
                    ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('outstanding_materials')) {
            $hasArchiveIndex = count(DB::select(
                "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_materials_archived_at_idx'"
            )) > 0;

            if ($hasArchiveIndex) {
                Schema::table('outstanding_materials', function (Blueprint $table): void {
                    $table->dropIndex('om_materials_archived_at_idx');
                });
            }

            if (Schema::hasColumn('outstanding_materials', 'archived_at')) {
                Schema::table('outstanding_materials', function (Blueprint $table): void {
                    $table->dropColumn('archived_at');
                });
            }
        }
    }
};
