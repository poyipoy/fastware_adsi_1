<?php

namespace App\Exports;

use App\Models\MstDboCrp;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MstDboCrpActualExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $userId = Auth::id(); // Ambil ID user yang sedang login

        $data = DB::table('mst_dbo_crp')
            ->join('users', 'mst_dbo_crp.partner_user', '=', 'users.id')
            ->whereNull('mst_dbo_crp.deleted_at')
            ->where('mst_dbo_crp.plan_actual', 'Actual')
            ->where('mst_dbo_crp.partner_user', $userId) // Filter hanya untuk user login
            ->select(
                'mst_dbo_crp.nm_category', 'users.name as user_name',
                'mst_dbo_crp.month_1', 'mst_dbo_crp.month_2', 'mst_dbo_crp.month_3',
                'mst_dbo_crp.month_4', 'mst_dbo_crp.month_5', 'mst_dbo_crp.month_6',
                'mst_dbo_crp.month_7', 'mst_dbo_crp.month_8', 'mst_dbo_crp.month_9',
                'mst_dbo_crp.month_10', 'mst_dbo_crp.month_11', 'mst_dbo_crp.month_12',
                'mst_dbo_crp.grand_tot', 'mst_dbo_crp.id'
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
            'No', 'Category', 'User',
            'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
            'Total', 'ID'
        ];
    }
}
