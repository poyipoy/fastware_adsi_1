<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wh_transaction_sequences')) {
            Schema::create('wh_transaction_sequences', function (Blueprint $table): void {
                $table->unsignedSmallInteger('year')->primary();
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();
            });
        }

        // Preserve any already-issued numbers in the new format without
        // rewriting historical ledger rows. Legacy numbers do not match this
        // pattern and therefore do not affect the new sequence.
        if (Schema::hasTable('trs_wh_stock_transactions')) {
            $existing = DB::table('trs_wh_stock_transactions')
                ->selectRaw("CAST(SUBSTRING(transaction_number, 4, 4) AS UNSIGNED) AS year")
                ->selectRaw("MAX(CAST(SUBSTRING(transaction_number, 8, 4) AS UNSIGNED)) AS last_number")
                ->whereRaw("transaction_number REGEXP '^WH-[0-9]{8}$'")
                ->groupByRaw('CAST(SUBSTRING(transaction_number, 4, 4) AS UNSIGNED)')
                ->get();

            foreach ($existing as $row) {
                DB::table('wh_transaction_sequences')->insertOrIgnore([
                    'year' => (int) $row->year,
                    'last_number' => min(9999, (int) $row->last_number),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('wh_transaction_sequences')
                    ->where('year', (int) $row->year)
                    ->where('last_number', '<', min(9999, (int) $row->last_number))
                    ->update([
                        'last_number' => min(9999, (int) $row->last_number),
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_transaction_sequences');
    }
};
