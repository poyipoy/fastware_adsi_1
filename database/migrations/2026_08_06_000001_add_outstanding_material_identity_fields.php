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
            if (!Schema::hasColumn('outstanding_materials', 'invoice_identity_key')) {
                $table->char('invoice_identity_key', 64)->nullable()->after('number_invoice');
            }

            if (!Schema::hasColumn('outstanding_materials', 'line_ref')) {
                $table->string('line_ref', 100)->nullable()->after('invoice_identity_key');
            }

            if (!Schema::hasColumn('outstanding_materials', 'line_ref_key')) {
                $table->char('line_ref_key', 64)->nullable()->after('line_ref');
            }
        });

        if (
            Schema::hasColumn('outstanding_materials', 'supplier')
            && Schema::hasColumn('outstanding_materials', 'number_invoice')
            && Schema::hasColumn('outstanding_materials', 'invoice_identity_key')
        ) {
            DB::statement(<<<'SQL'
                UPDATE outstanding_materials
                SET invoice_identity_key = SHA2(
                    CONCAT(
                        UPPER(TRIM(REGEXP_REPLACE(supplier, '[[:space:]]+', ' '))),
                        CHAR(31),
                        UPPER(TRIM(REGEXP_REPLACE(number_invoice, '[[:space:]]+', ' ')))
                    ),
                    256
                )
                WHERE invoice_identity_key IS NULL
                  AND NULLIF(TRIM(supplier), '') IS NOT NULL
                  AND NULLIF(TRIM(number_invoice), '') IS NOT NULL
            SQL);
        }

        if (
            Schema::hasColumn('outstanding_materials', 'line_ref')
            && Schema::hasColumn('outstanding_materials', 'line_ref_key')
        ) {
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
        }

        Schema::table('outstanding_materials', function (Blueprint $table): void {
            $table->index('invoice_identity_key', 'outstanding_materials_invoice_identity_idx');
            $table->index('line_ref_key', 'outstanding_materials_line_ref_key_idx');
            $table->unique(
                ['invoice_identity_key', 'line_ref_key'],
                'outstanding_materials_invoice_line_unique',
            );
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('outstanding_materials')) {
            return;
        }

        Schema::table('outstanding_materials', function (Blueprint $table): void {
            $table->dropUnique('outstanding_materials_invoice_line_unique');
            $table->dropIndex('outstanding_materials_invoice_identity_idx');
            $table->dropIndex('outstanding_materials_line_ref_key_idx');
            $table->dropColumn(['invoice_identity_key', 'line_ref', 'line_ref_key']);
        });
    }
};
