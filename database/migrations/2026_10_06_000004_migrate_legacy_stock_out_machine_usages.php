<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $transactions = DB::table('trs_wh_stock_transactions')
            ->where('transaction_type', 'OUT')
            ->whereNotNull('machine_type_used')
            ->where('machine_type_used', '<>', '')
            ->get(['id', 'transaction_number', 'machine_type_used', 'quantity']);

        $rowsToInsert = [];
        $ambiguousRows = [];

        foreach ($transactions as $trx) {
            $machine = trim((string) $trx->machine_type_used);

            if (str_contains($machine, ',')) {
                $ambiguousRows[] = [
                    'id' => $trx->id,
                    'transaction_number' => $trx->transaction_number,
                    'machine' => $machine,
                    'quantity' => $trx->quantity,
                ];
                continue;
            }

            if ($machine !== '') {
                $rowsToInsert[] = [
                    'transaction_id' => $trx->id,
                    'machine_code' => $machine,
                    'quantity' => $trx->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (! empty($ambiguousRows)) {
            throw new \RuntimeException(sprintf(
                'Data migration blocker: Ditemukan %d transaksi Stock Out dengan alokasi mesin ambigu: %s. Alokasi mesin tidak boleh ditebak.',
                count($ambiguousRows),
                json_encode($ambiguousRows),
            ));
        }

        if (! empty($rowsToInsert)) {
            foreach (array_chunk($rowsToInsert, 100) as $chunk) {
                DB::table('trs_wh_stock_transaction_machine_usages')->insertOrIgnore($chunk);
            }
        }
    }

    public function down(): void
    {
        DB::table('trs_wh_stock_transaction_machine_usages')->truncate();
    }
};
