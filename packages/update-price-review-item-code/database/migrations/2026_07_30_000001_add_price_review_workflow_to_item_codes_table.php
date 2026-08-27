<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureRequiredSchema();

        DB::statement(<<<'SQL'
            ALTER TABLE item_codes
            MODIFY COLUMN status ENUM(
                'draft',
                'pending_price_review',
                'submitted',
                'approved_1',
                'approved_2',
                'finished',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        SQL);

        $this->alterPriceColumn(true);

        $addReviewedBy = ! Schema::hasColumn('item_codes', 'price_reviewed_by');
        $addReviewedAt = ! Schema::hasColumn('item_codes', 'price_reviewed_at');

        Schema::table('item_codes', function (Blueprint $table) use ($addReviewedBy, $addReviewedAt): void {
            if ($addReviewedBy) {
                $table->unsignedBigInteger('price_reviewed_by')
                    ->nullable()
                    ->after('created_by');

                $table->foreign('price_reviewed_by', 'item_codes_price_reviewed_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if ($addReviewedAt) {
                $table->timestamp('price_reviewed_at')
                    ->nullable()
                    ->after('price_reviewed_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('item_codes')) {
            return;
        }

        if (DB::table('item_codes')->where('status', 'pending_price_review')->exists()) {
            throw new RuntimeException(
                'Rollback price review Item Code dibatalkan karena masih ada record berstatus pending_price_review.'
            );
        }

        if (DB::table('item_codes')->whereNull('price_per_pcs')->exists()) {
            throw new RuntimeException(
                'Rollback price review Item Code dibatalkan karena masih ada record dengan price_per_pcs NULL.'
            );
        }

        $this->ensureRequiredSchema();
        $this->alterPriceColumn(false);

        DB::statement(<<<'SQL'
            ALTER TABLE item_codes
            MODIFY COLUMN status ENUM(
                'draft',
                'submitted',
                'approved_1',
                'approved_2',
                'finished',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        SQL);

        $dropReviewedBy = Schema::hasColumn('item_codes', 'price_reviewed_by');
        $dropReviewedAt = Schema::hasColumn('item_codes', 'price_reviewed_at');

        Schema::table('item_codes', function (Blueprint $table) use ($dropReviewedBy, $dropReviewedAt): void {
            if ($dropReviewedBy) {
                $table->dropForeign('item_codes_price_reviewed_by_foreign');
            }

            $columns = [];

            if ($dropReviewedAt) {
                $columns[] = 'price_reviewed_at';
            }

            if ($dropReviewedBy) {
                $columns[] = 'price_reviewed_by';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    private function ensureRequiredSchema(): void
    {
        foreach (['status', 'price_per_pcs'] as $column) {
            if ($this->columnType($column) === null) {
                throw new RuntimeException(
                    sprintf('Kolom item_codes.%s wajib tersedia sebelum migration price review dijalankan.', $column)
                );
            }
        }
    }

    private function alterPriceColumn(bool $nullable): void
    {
        $columnType = strtolower(trim((string) $this->columnType('price_per_pcs')));

        if (! preg_match('/^decimal\(\d+,\d+\)(?: unsigned)?$/', $columnType)) {
            throw new RuntimeException(
                'Tipe item_codes.price_per_pcs harus DECIMAL agar nullability dapat diubah dengan aman.'
            );
        }

        DB::statement(sprintf(
            'ALTER TABLE item_codes MODIFY COLUMN price_per_pcs %s %s',
            strtoupper($columnType),
            $nullable ? 'NULL' : 'NOT NULL'
        ));
    }

    private function columnType(string $column): ?string
    {
        $statement = DB::connection()->getPdo()->prepare(
            <<<'SQL'
                SELECT COLUMN_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ?
                  AND TABLE_NAME = 'item_codes'
                  AND COLUMN_NAME = ?
                LIMIT 1
            SQL
        );
        $statement->execute([
            (string) DB::connection()->getDatabaseName(),
            $column,
        ]);

        $columnType = $statement->fetchColumn();

        return $columnType === false ? null : (string) $columnType;
    }
};
