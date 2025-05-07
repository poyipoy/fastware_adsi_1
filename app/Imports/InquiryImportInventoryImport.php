<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class InquiryImportInventoryImport implements ToCollection
{
     /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
{
    $now = Carbon::now();
    $userId = Auth::id();
    $userName = Auth::user()->name;

    // Ambil semua type_materials sekaligus
    $typeMaterials = DB::table('type_materials')->pluck('id', 'type_name');

    DB::beginTransaction(); // Mulai transaction

    try {
        $inquiryUpdates = [];
        $detailUpdates = [];
        $logs = [];

        foreach ($rows as $row) {
            if (empty($row['id'])) {
                continue;
            }

            // Kumpulkan data untuk update inquiry_sales
            $inquiryUpdates[] = [
                'id' => $row['id'],
                'region' => $row['region'],
                'kode_inquiry' => $row['kode_inquiry'],
                'type_order' => $row['order_type'],
                'jenis_inquiry' => $row['inquiry_type'],
                'loc_imp' => $row['category'],
                'est_date' => $row['est_date'],
                'supplier' => $row['supplier'],
                'create_by' => $row['sales_person'],
                'refnopo' => $row['ref_po'],
                'progress' => $row['progress'],
                'modified_by' => $userName,
                'updated_at' => $now,
            ];

            // Cari ID type_material berdasarkan nama
            $typeId = $typeMaterials[$row['raw_material']] ?? null;

            // Kumpulkan data untuk update detail_inquiry_import
            $detailUpdates[] = [
                'id_inquiry' => $row['id'],
                'id_type' => $typeId,
                'jenis' => $row['shapes'],
                'thickness' => $row['thickness'],
                'inner_diameter' => $row['inner_diameter'],
                'outer_diameter' => $row['outer_diameter'],
                'weight' => $row['weight'],
                'length' => $row['length'],
                'qty' => $row['qty_unit'],
                'm1' => $row['forecast_month_1'],
                'm2' => $row['forecast_month_2'],
                'm3' => $row['forecast_month_3'],
                'so' => $row['ref_so'],
                'ship' => $row['ship_to'],
                'note' => $row['remark'],
                'updated_at' => $now,
            ];

            // Kumpulkan log untuk tracking
            $logs[] = [
                'inquiry_id' => $row['id'],
                'description' => 'Updated via Excel Import',
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now
            ];
        }

        // Batch update inquiry_sales
        DB::table('inquiry_sales')->upsert($inquiryUpdates, ['id'], array_keys($inquiryUpdates[0]));

        // Batch update detail_inquiry_import
        DB::table('detail_inquiry_import')->upsert($detailUpdates, ['id_inquiry'], array_keys($detailUpdates[0]));

        // Insert log activity
        DB::table('trx_dbo_progpurchase')->insert($logs);

        DB::commit(); // Commit transaksi jika sukses

        return response()->json(['success' => true, 'message' => 'Import berhasil']);
    } catch (\Exception $e) {
        DB::rollBack(); // Rollback jika ada error
        return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: '.$e->getMessage()], 500);
    }
}
}
