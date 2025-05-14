<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CrpDetailExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $userId = Auth::id(); // Ambil ID user yang sedang login

        $data = DB::table('trs_dbo_crp')
            ->join('mst_dbo_crp', 'trs_dbo_crp.mst_id', '=', 'mst_dbo_crp.id')
            ->whereNull('trs_dbo_crp.deleted_at')
            ->whereNull('mst_dbo_crp.deleted_at')
            ->where('mst_dbo_crp.partner_user', $userId)    
            ->select(
                'trs_dbo_crp.nm_category',
                'trs_dbo_crp.detail_activity',
                'trs_dbo_crp.no_po',
                'trs_dbo_crp.date',
                'trs_dbo_crp.qty',
                'trs_dbo_crp.price_before',
                'trs_dbo_crp.price_after',
                'trs_dbo_crp.price_sell',
                'trs_dbo_crp.total_cost_before',
                'trs_dbo_crp.total_cost_after',
                'trs_dbo_crp.total_cost_crp',
                'trs_dbo_crp.id',
                'trs_dbo_crp.mst_id'
            )
            ->get();

        $result = [];
        $no = 1;

        foreach ($data as $row) {
            $result[] = array_merge(
                ['No' => $no++],
                (array) $row
            );
        }

        return collect($result);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No', 'Category', 'Detail Activity',
            'NO PO', 'Date', 'QTY', 'Price Before', 'Price After', 'Selisih',
            'Total Cost Before', 'Total Cost After', 'Total Cost CRP', 'ID', 'MST_ID'
        ];
    }
}
