<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perubahan:
     *  1. Alter kolom status (ENUM) → tambah nilai approved_1 dan approved_2
     *  2. Tambah kolom approved2_by (Approver 2 = Martinus)
     *  3. Migrasi data lama: status 'approved' → 'approved_2'
     */
    public function up(): void
    {
        // Step 1: Perluas ENUM — tambah approved_1 & approved_2,
        // tapi TETAP simpan 'approved' karena data lama masih memakainya.
        DB::statement("
            ALTER TABLE item_codes
            MODIFY COLUMN status ENUM(
                'draft',
                'submitted',
                'approved',
                'approved_1',
                'approved_2',
                'finished'
            ) NOT NULL DEFAULT 'draft'
        ");

        // Step 2: Migrasi data lama 'approved' → 'approved_2'
        // Sekarang aman karena approved_2 sudah ada di ENUM.
        DB::table('item_codes')
            ->where('status', 'approved')
            ->update(['status' => 'approved_2']);

        // Step 3: Baru hapus 'approved' dari ENUM karena sudah tidak ada datanya.
        DB::statement("
            ALTER TABLE item_codes
            MODIFY COLUMN status ENUM(
                'draft',
                'submitted',
                'approved_1',
                'approved_2',
                'finished'
            ) NOT NULL DEFAULT 'draft'
        ");

        // Step 4: Tambah kolom approved2_by (skip jika sudah ada)
        if (!Schema::hasColumn('item_codes', 'approved2_by')) {
            Schema::table('item_codes', function (Blueprint $table) {
                $table->unsignedBigInteger('approved2_by')
                    ->nullable()
                    ->after('approved_by');

                $table->foreign('approved2_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Kembalikan data dulu sebelum menyempitkan ENUM
        DB::table('item_codes')
            ->where('status', 'approved_2')
            ->update(['status' => 'approved']);

        DB::table('item_codes')
            ->where('status', 'approved_1')
            ->update(['status' => 'submitted']);

        // Drop kolom approved2_by
        Schema::table('item_codes', function (Blueprint $table) {
            $table->dropForeign(['approved2_by']);
            $table->dropColumn('approved2_by');
        });

        // Kembalikan ENUM ke semula
        DB::statement("
            ALTER TABLE item_codes
            MODIFY COLUMN status ENUM(
                'draft',
                'submitted',
                'approved',
                'finished'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};