<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'item_codes_nomor_pengajuan_unique';
    private const NORMAL_INDEX = 'item_codes_nomor_pengajuan_index';

    public function up(): void
    {
        $uniqueIndex = self::UNIQUE_INDEX;
        $normalIndex = self::NORMAL_INDEX;

        Schema::table('item_codes', function (Blueprint $table) use ($uniqueIndex, $normalIndex) {
            try {
                $table->dropUnique($uniqueIndex);
            } catch (\Throwable $exception) {
                // Ignore when unique index does not exist.
            }

            try {
                $table->index('nomor_pengajuan', $normalIndex);
            } catch (\Throwable $exception) {
                // Ignore when index already exists.
            }
        });
    }

    public function down(): void
    {
        $uniqueIndex = self::UNIQUE_INDEX;
        $normalIndex = self::NORMAL_INDEX;

        Schema::table('item_codes', function (Blueprint $table) use ($uniqueIndex, $normalIndex) {
            try {
                $table->dropIndex($normalIndex);
            } catch (\Throwable $exception) {
                // Ignore when index does not exist.
            }

            try {
                $table->unique('nomor_pengajuan', $uniqueIndex);
            } catch (\Throwable $exception) {
                // Ignore if rollback cannot recreate unique index due duplicate data.
            }
        });
    }
};
