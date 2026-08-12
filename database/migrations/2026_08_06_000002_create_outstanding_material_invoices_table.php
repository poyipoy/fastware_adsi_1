<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outstanding_material_invoices')) {
            Schema::create('outstanding_material_invoices', function (Blueprint $table): void {
                $table->id();
                $table->string('supplier');
                $table->string('number_invoice');
                $table->char('invoice_identity_key', 64)->unique();
                $table->string('packing_list_path')->nullable();
                $table->string('mtc_path')->nullable();
                $table->boolean('document_review_required')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['supplier', 'number_invoice']);
            });
        }

        if (Schema::hasTable('outstanding_materials') && !Schema::hasColumn('outstanding_materials', 'invoice_id')) {
            Schema::table('outstanding_materials', function (Blueprint $table): void {
                $table->foreignId('invoice_id')->nullable()->after('id')->constrained('outstanding_material_invoices')->nullOnDelete();
                $table->index('invoice_id');
            });
        }

        if (
            Schema::hasTable('outstanding_materials')
            && Schema::hasTable('outstanding_material_invoices')
            && Schema::hasColumn('outstanding_materials', 'invoice_identity_key')
        ) {
            DB::statement(<<<'SQL'
                INSERT INTO outstanding_material_invoices
                    (supplier, number_invoice, invoice_identity_key, created_by, created_at, updated_at)
                SELECT MIN(supplier), MIN(number_invoice), invoice_identity_key, MIN(created_by), NOW(), NOW()
                FROM outstanding_materials
                WHERE invoice_identity_key IS NOT NULL
                  AND NULLIF(TRIM(number_invoice), '') IS NOT NULL
                GROUP BY invoice_identity_key
                ON DUPLICATE KEY UPDATE
                    supplier = VALUES(supplier),
                    number_invoice = VALUES(number_invoice)
            SQL);

            DB::statement(<<<'SQL'
                UPDATE outstanding_materials m
                INNER JOIN outstanding_material_invoices i
                    ON i.invoice_identity_key = m.invoice_identity_key
                SET m.invoice_id = i.id
                WHERE m.invoice_id IS NULL
            SQL);

            DB::statement(<<<'SQL'
                UPDATE outstanding_material_invoices i
                INNER JOIN (
                    SELECT invoice_identity_key,
                        COUNT(DISTINCT NULLIF(COALESCE(NULLIF(packing_list_path, ''), NULLIF(attachment_path, '')), '')) AS packing_variants,
                        COUNT(DISTINCT NULLIF(mtc_path, '')) AS mtc_variants,
                        MIN(CASE WHEN COALESCE(NULLIF(packing_list_path, ''), NULLIF(attachment_path, '')) IS NOT NULL THEN COALESCE(NULLIF(packing_list_path, ''), NULLIF(attachment_path, '')) END) AS packing_path,
                        MIN(CASE WHEN NULLIF(mtc_path, '') IS NOT NULL THEN mtc_path END) AS mtc_path
                    FROM outstanding_materials
                    WHERE invoice_identity_key IS NOT NULL
                    GROUP BY invoice_identity_key
                ) x ON x.invoice_identity_key = i.invoice_identity_key
                SET i.packing_list_path = CASE WHEN x.packing_variants = 1 THEN x.packing_path ELSE NULL END,
                    i.mtc_path = CASE WHEN x.mtc_variants = 1 THEN x.mtc_path ELSE NULL END,
                    i.document_review_required = CASE WHEN x.packing_variants > 1 OR x.mtc_variants > 1 THEN 1 ELSE 0 END
            SQL);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('outstanding_materials') && Schema::hasColumn('outstanding_materials', 'invoice_id')) {
            Schema::table('outstanding_materials', function (Blueprint $table): void {
                $table->dropForeign(['invoice_id']);
                $table->dropIndex(['invoice_id']);
                $table->dropColumn('invoice_id');
            });
        }

        Schema::dropIfExists('outstanding_material_invoices');
    }
};
