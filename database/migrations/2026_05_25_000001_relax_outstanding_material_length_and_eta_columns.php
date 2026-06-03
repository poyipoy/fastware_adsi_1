<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('outstanding_materials')) {
            return;
        }

        DB::statement('ALTER TABLE `outstanding_materials`
            MODIFY `length` VARCHAR(255) NULL,
            MODIFY `estimasi_eta_port` VARCHAR(100) NULL,
            MODIFY `estimasi_eta_warehouse` VARCHAR(100) NULL,
            MODIFY `estimasi_delay_eta_port` VARCHAR(100) NULL,
            MODIFY `estimasi_delay_eta_warehouse` VARCHAR(100) NULL');
    }

    public function down(): void
    {
        if (!Schema::hasTable('outstanding_materials')) {
            return;
        }

        DB::statement('UPDATE `outstanding_materials`
            SET `length` = NULL
            WHERE `length` IS NOT NULL AND `length` NOT REGEXP "^-?[0-9]+(\\\\.[0-9]+)?$"');

        foreach ([
            'estimasi_eta_port',
            'estimasi_eta_warehouse',
            'estimasi_delay_eta_port',
            'estimasi_delay_eta_warehouse',
        ] as $column) {
            DB::statement("UPDATE `outstanding_materials`
                SET `{$column}` = NULL
                WHERE `{$column}` IS NOT NULL AND `{$column}` NOT REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'");
        }

        DB::statement('ALTER TABLE `outstanding_materials`
            MODIFY `length` DECIMAL(15, 2) NULL,
            MODIFY `estimasi_eta_port` DATE NULL,
            MODIFY `estimasi_eta_warehouse` DATE NULL,
            MODIFY `estimasi_delay_eta_port` DATE NULL,
            MODIFY `estimasi_delay_eta_warehouse` DATE NULL');
    }
};
