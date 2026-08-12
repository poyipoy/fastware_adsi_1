<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('item_codes')) {
            throw new RuntimeException('Tabel item_codes wajib tersedia sebelum migration cancellation dijalankan.');
        }

        if (! Schema::hasColumn('item_codes', 'status')) {
            throw new RuntimeException('Kolom item_codes.status wajib tersedia sebelum migration cancellation dijalankan.');
        }

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

        $addCancelledBy = ! Schema::hasColumn('item_codes', 'cancelled_by');
        $addCancelledAt = ! Schema::hasColumn('item_codes', 'cancelled_at');

        Schema::table('item_codes', function (Blueprint $table) use ($addCancelledBy, $addCancelledAt): void {
            if ($addCancelledBy) {
                $table->unsignedBigInteger('cancelled_by')
                    ->nullable()
                    ->after('finished_by');

                $table->foreign('cancelled_by', 'item_codes_cancelled_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }

            if ($addCancelledAt) {
                $table->timestamp('cancelled_at')
                    ->nullable()
                    ->after('cancelled_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('item_codes')) {
            return;
        }

        if (DB::table('item_codes')->where('status', 'cancelled')->exists()) {
            throw new RuntimeException(
                'Rollback cancellation Item Code dibatalkan karena masih ada record berstatus cancelled.'
            );
        }

        if (Schema::hasColumn('item_codes', 'status')) {
            DB::statement(<<<'SQL'
                ALTER TABLE item_codes
                MODIFY COLUMN status ENUM(
                    'draft',
                    'submitted',
                    'approved_1',
                    'approved_2',
                    'finished'
                ) NOT NULL DEFAULT 'draft'
            SQL);
        }

        $dropCancelledBy = Schema::hasColumn('item_codes', 'cancelled_by');
        $dropCancelledAt = Schema::hasColumn('item_codes', 'cancelled_at');

        Schema::table('item_codes', function (Blueprint $table) use ($dropCancelledBy, $dropCancelledAt): void {
            if ($dropCancelledBy) {
                $table->dropForeign('item_codes_cancelled_by_foreign');
            }

            $columns = [];

            if ($dropCancelledAt) {
                $columns[] = 'cancelled_at';
            }

            if ($dropCancelledBy) {
                $columns[] = 'cancelled_by';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
