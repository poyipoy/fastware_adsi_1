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
            if (!Schema::hasColumn('outstanding_materials', 'packing_list_path')) {
                $table->string('packing_list_path')->nullable()->after('attachment_path');
            }

            if (!Schema::hasColumn('outstanding_materials', 'mtc_path')) {
                $table->string('mtc_path')->nullable()->after('packing_list_path');
            }
        });

        if (
            Schema::hasColumn('outstanding_materials', 'attachment_path')
            && Schema::hasColumn('outstanding_materials', 'packing_list_path')
        ) {
            DB::table('outstanding_materials')
                ->whereNull('packing_list_path')
                ->whereNotNull('attachment_path')
                ->update(['packing_list_path' => DB::raw('attachment_path')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('outstanding_materials')) {
            return;
        }

        Schema::table('outstanding_materials', function (Blueprint $table): void {
            if (Schema::hasColumn('outstanding_materials', 'mtc_path')) {
                $table->dropColumn('mtc_path');
            }

            if (Schema::hasColumn('outstanding_materials', 'packing_list_path')) {
                $table->dropColumn('packing_list_path');
            }
        });
    }
};
