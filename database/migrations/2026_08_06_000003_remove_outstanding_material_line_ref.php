<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outstanding_materials')) {
            return;
        }

        Schema::table('outstanding_materials', function (Blueprint $table): void {
            $table->dropUnique('outstanding_materials_invoice_line_unique');
            $table->dropIndex('outstanding_materials_line_ref_key_idx');
            $table->dropColumn(['line_ref', 'line_ref_key']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('outstanding_materials') || Schema::hasColumn('outstanding_materials', 'line_ref')) {
            return;
        }

        Schema::table('outstanding_materials', function (Blueprint $table): void {
            $table->string('line_ref', 100)->nullable()->after('invoice_identity_key');
            $table->char('line_ref_key', 64)->nullable()->after('line_ref');
        });

        DB::statement(<<<'SQL'
            UPDATE outstanding_materials
            SET line_ref = CONCAT('LEGACY-', id)
            WHERE line_ref IS NULL OR TRIM(line_ref) = ''
        SQL);

        DB::statement(<<<'SQL'
            UPDATE outstanding_materials
            SET line_ref_key = SHA2(UPPER(TRIM(REGEXP_REPLACE(line_ref, '[[:space:]]+', ' '))), 256)
            WHERE line_ref_key IS NULL
              AND NULLIF(TRIM(line_ref), '') IS NOT NULL
        SQL);

        Schema::table('outstanding_materials', function (Blueprint $table): void {
            $table->index('line_ref_key', 'outstanding_materials_line_ref_key_idx');
            $table->unique(
                ['invoice_identity_key', 'line_ref_key'],
                'outstanding_materials_invoice_line_unique',
            );
        });
    }
};
