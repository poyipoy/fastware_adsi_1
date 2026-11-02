<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add minimal archived_at column to outstanding_material_invoices
        if (Schema::hasTable('outstanding_material_invoices')) {
            if (! Schema::hasColumn('outstanding_material_invoices', 'archived_at')) {
                Schema::table('outstanding_material_invoices', function (Blueprint $table): void {
                    // Minimal invoice-level archive marker (NULL = active, timestamp = archived)
                    $table->timestamp('archived_at')->nullable()->after('document_review_required');
                });
            }

            $hasArchiveIndex = count(DB::select(
                "SHOW INDEX FROM `outstanding_material_invoices` WHERE Key_name = 'om_invoices_archived_at_idx'"
            )) > 0;

            if (! $hasArchiveIndex) {
                Schema::table('outstanding_material_invoices', function (Blueprint $table): void {
                    $table->index('archived_at', 'om_invoices_archived_at_idx');
                });
            }
        }

        // 2. Add index on estimasi_delay_eta_warehouse using portable raw index check
        if (Schema::hasTable('outstanding_materials')) {
            $hasDelayIndex = count(DB::select(
                "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_delay_eta_wh_idx'"
            )) > 0;

            if (! $hasDelayIndex) {
                Schema::table('outstanding_materials', function (Blueprint $table): void {
                    $table->index('estimasi_delay_eta_warehouse', 'om_delay_eta_wh_idx');
                });
            }
        }

        // NOTE: Schema migration is strictly minimal DDL.
        // Legacy identity reconciliation is intentionally moved out of the schema migration.
        // Zero inline data backfill, relationship rewrites, or header creation.
    }

    public function down(): void
    {
        if (Schema::hasTable('outstanding_materials')) {
            $hasDelayIndex = count(DB::select(
                "SHOW INDEX FROM `outstanding_materials` WHERE Key_name = 'om_delay_eta_wh_idx'"
            )) > 0;

            if ($hasDelayIndex) {
                Schema::table('outstanding_materials', function (Blueprint $table): void {
                    $table->dropIndex('om_delay_eta_wh_idx');
                });
            }
        }

        if (Schema::hasTable('outstanding_material_invoices')) {
            $hasArchiveIndex = count(DB::select(
                "SHOW INDEX FROM `outstanding_material_invoices` WHERE Key_name = 'om_invoices_archived_at_idx'"
            )) > 0;

            if ($hasArchiveIndex) {
                Schema::table('outstanding_material_invoices', function (Blueprint $table): void {
                    $table->dropIndex('om_invoices_archived_at_idx');
                });
            }

            if (Schema::hasColumn('outstanding_material_invoices', 'archived_at')) {
                Schema::table('outstanding_material_invoices', function (Blueprint $table): void {
                    $table->dropColumn('archived_at');
                });
            }
        }
    }
};
