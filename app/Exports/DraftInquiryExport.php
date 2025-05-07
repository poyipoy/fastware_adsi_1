<?php
namespace App\Exports;

use App\Models\InquirySales;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DraftInquiryExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        // Filter hanya data yang memiliki loc_imp = 'import'
        return InquirySales::where('loc_imp', 'import')
            ->select(
                'id',
                'id_customer',
                'kode_inquiry',
                'type_order',
                'jenis_inquiry',
                'loc_imp',
                'est_date',
                'supplier',
                'create_by',
                'progress',
                'refnopo',
                'status',
                'created_at',
                'updated_at',
                'modified_by',
                'region'
            )->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Customer',
            'Kode Inquiry',
            'Tipe Order',
            'Jenis Inquiry',
            'Lokasi Impor',
            'Estimasi Tanggal',
            'Supplier',
            'Dibuat Oleh',
            'Progress',
            'Ref No PO',
            'Status',
            'Dibuat Pada',
            'Diupdate Pada',
            'Diubah Oleh',
            'Region',
        ];
    }
}
