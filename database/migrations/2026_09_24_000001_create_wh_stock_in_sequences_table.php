<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wh_stock_in_sequences')) {
            Schema::create('wh_stock_in_sequences', function (Blueprint $table): void {
                $table->unsignedSmallInteger('year')->primary();
                $table->unsignedInteger('last_number')->default(0);
                $table->timestamps();
            });
        }

        // Renumber existing stock ins to simplified format: WH-IN-YYYYNNNN
        if (Schema::hasTable('trs_wh_stock_ins')) {
            $stockIns = DB::table('trs_wh_stock_ins')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            if ($stockIns->isNotEmpty()) {
                // Step 1: Temporary names to prevent unique collisions
                foreach ($stockIns as $si) {
                    DB::table('trs_wh_stock_ins')
                        ->where('id', $si->id)
                        ->update(['stock_in_number' => 'TEMP-SI-'.$si->id]);
                }

                // Step 2: Sequential renumbering by year
                $yearCounters = [];
                foreach ($stockIns as $si) {
                    $year = $si->created_at ? (int) date('Y', strtotime($si->created_at)) : (int) date('Y');
                    $counter = ($yearCounters[$year] ?? 0) + 1;
                    $yearCounters[$year] = $counter;

                    $newNumber = sprintf('WH-IN-%04d%04d', $year, $counter);
                    DB::table('trs_wh_stock_ins')
                        ->where('id', $si->id)
                        ->update(['stock_in_number' => $newNumber]);
                }

                // Step 3: Record max counter in sequence table
                foreach ($yearCounters as $yr => $maxCount) {
                    DB::table('wh_stock_in_sequences')->insertOrIgnore([
                        'year' => $yr,
                        'last_number' => $maxCount,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('wh_stock_in_sequences')
                        ->where('year', $yr)
                        ->where('last_number', '<', $maxCount)
                        ->update([
                            'last_number' => $maxCount,
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        // Renumber any legacy long transaction numbers in trs_wh_stock_transactions to WH-YYYYNNNN
        if (Schema::hasTable('trs_wh_stock_transactions')) {
            $transactions = DB::table('trs_wh_stock_transactions')
                ->orderBy('transaction_at')
                ->orderBy('id')
                ->get();

            if ($transactions->isNotEmpty()) {
                // Step 1: Temporary names
                foreach ($transactions as $tx) {
                    DB::table('trs_wh_stock_transactions')
                        ->where('id', $tx->id)
                        ->update(['transaction_number' => 'TEMP-TX-'.$tx->id]);
                }

                // Step 2: Sequential numbering by year
                $txYearCounters = [];
                foreach ($transactions as $tx) {
                    $year = $tx->transaction_at ? (int) date('Y', strtotime($tx->transaction_at)) : (int) date('Y');
                    $counter = ($txYearCounters[$year] ?? 0) + 1;
                    $txYearCounters[$year] = $counter;

                    $newNumber = sprintf('WH-%04d%04d', $year, $counter);
                    DB::table('trs_wh_stock_transactions')
                        ->where('id', $tx->id)
                        ->update(['transaction_number' => $newNumber]);
                }

                // Step 3: Sync wh_transaction_sequences
                if (Schema::hasTable('wh_transaction_sequences')) {
                    foreach ($txYearCounters as $yr => $maxCount) {
                        DB::table('wh_transaction_sequences')->insertOrIgnore([
                            'year' => $yr,
                            'last_number' => $maxCount,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('wh_transaction_sequences')
                            ->where('year', $yr)
                            ->where('last_number', '<', $maxCount)
                            ->update([
                                'last_number' => $maxCount,
                                'updated_at' => now(),
                            ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wh_stock_in_sequences');
    }
};
