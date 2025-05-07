<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Customer;

class InquiryImportInventoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $ids; 

    public function __construct(array $ids)
    {
        $this->ids = $ids;
    }

    public function collection()
{
    $result = DB::table('detail_inquiry_import')
        ->whereIn('id_inquiry', $this->ids)
        ->whereNull('detail_inquiry_import.deleted_at')
        ->where('inquiry_sales.status', '3')
        ->join('inquiry_sales', 'detail_inquiry_import.id_inquiry', '=', 'inquiry_sales.id')
        ->join('type_materials', 'detail_inquiry_import.id_type', '=', 'type_materials.id')
        ->join('users', 'detail_inquiry_import.create_by', '=', 'users.id')
        // ->join('customers', 'detail_inquiry_import.customer', '=', 'customers.id')
        ->leftJoin(
            DB::raw('(SELECT inquiry_id, MIN(created_at) AS created_at 
                      FROM trx_dbo_progpurchase 
                      WHERE description = "Approved by Ka. Dept." 
                      GROUP BY inquiry_id) AS trx_approved'),
            function ($join) {
                $join->on('inquiry_sales.id', '=', 'trx_approved.inquiry_id');
            }
        )
        ->select([
            DB::raw('DATE_FORMAT(trx_approved.created_at, "%M %Y") AS bulan'),
            'inquiry_sales.region', 'detail_inquiry_import.id_inquiry AS inquiry_id',
            'detail_inquiry_import.id AS detail_id',
            // 'customers.name_customer',
            'inquiry_sales.kode_inquiry', 'inquiry_sales.type_order',
            'inquiry_sales.jenis_inquiry', 'inquiry_sales.loc_imp', 'detail_inquiry_import.est_date',
            'inquiry_sales.supplier', 'inquiry_sales.create_by AS sales_person', 'detail_inquiry_import.progress',
            'inquiry_sales.refnopo AS ref_po', 'inquiry_sales.attach_file AS files', 'inquiry_sales.status',
            'inquiry_sales.created_at', 'inquiry_sales.updated_at', 'inquiry_sales.modified_by',
            'type_materials.type_name AS raw_material', 'detail_inquiry_import.jenis AS shapes',
            'detail_inquiry_import.thickness', 'detail_inquiry_import.inner_diameter', 'detail_inquiry_import.outer_diameter',
            'detail_inquiry_import.weight', 'detail_inquiry_import.length', 'detail_inquiry_import.qty AS qty_unit',
            'detail_inquiry_import.m1 AS forecast_month_1', 'detail_inquiry_import.m2 AS forecast_month_2',
            'detail_inquiry_import.m3 AS forecast_month_3', 'detail_inquiry_import.so AS ref_so',
            'detail_inquiry_import.ship AS ship_to', 'detail_inquiry_import.note AS remark', 'detail_inquiry_import.file',
            'detail_inquiry_import.created_at AS detail_created_at', 'detail_inquiry_import.updated_at AS detail_updated_at',
            'detail_inquiry_import.customer AS customer',
            'users.name AS partner'
        ])
        ->get();
    return $result;
}


    /**
     * Menentukan header untuk file Excel.
     *
     * @return array
     */
    public function headings(): array
{
    return [
        'No', 'Bulan', 'Region', 'Customer Name', 'Inquiry Code', 'Order Type', 'Inquiry Type', 'Category', 
        'Est. Date', 'Sales Person', 'Status', 'Raw Material', 'Shapes', 'Thickness', 
        'Inner Diameter', 'Outer Diameter', 'Weight', 'Length', 'Qty *Unit', 
        'Forecast Month 1', 'Forecast Month 2', 'Forecast Month 3', 
        'Ref. SO', 'Ship-To', 'Remark', 'Partner', 'ID Inquiry', 'ID Detail', 'Progress'
    ];
}

/**
 * Memetakan data untuk ekspor ke Excel.
 *
 * @param object $inquiry
 * @return array
 */

 private $row = 1; // Inisialisasi di class untuk nomor urut

 public function map($inquiry): array
 {
     $customerNames = [];
 
     $rawCustomer = $inquiry->customer;
 
     // Coba decode jika JSON
     $decoded = json_decode($rawCustomer, true);
 
     if (is_array($decoded)) {
         // Jika bentuk array (hasil decode sukses)
         foreach ($decoded as $custVal) {
             if (is_numeric($custVal)) {
                 $customer = Customer::find($custVal);
                 $customerNames[] = $customer ? $customer->name_customer : "(ID: $custVal)";
             } else {
                 $customerNames[] = $custVal; // Nama langsung
             }
         }
     } else {
         // Jika bukan array (mungkin ID tunggal atau nama langsung)
         if (is_numeric($rawCustomer)) {
             $customer = Customer::find($rawCustomer);
             $customerNames[] = $customer ? $customer->name_customer : "(ID: $rawCustomer)";
         } else {
             $customerNames[] = $rawCustomer;
         }
     }
 
     return [
         $this->row++, // No
         $inquiry->bulan,
         $inquiry->region,
         implode('; ', $customerNames), // <- Pastikan kolom ini sesuai urutan 'Customer Name'
         $inquiry->kode_inquiry,
         $inquiry->type_order,
         $inquiry->jenis_inquiry,
         $inquiry->loc_imp,
         $inquiry->est_date,
         $inquiry->sales_person,
         $inquiry->status,
         $inquiry->raw_material,
         $inquiry->shapes,
         $inquiry->thickness,
         $inquiry->inner_diameter,
         $inquiry->outer_diameter,
         $inquiry->weight,
         $inquiry->length,
         $inquiry->qty_unit,
         $inquiry->forecast_month_1,
         $inquiry->forecast_month_2,
         $inquiry->forecast_month_3,
         $inquiry->ref_so,
         $inquiry->ship_to,
         $inquiry->remark,
         $inquiry->partner,
         $inquiry->inquiry_id,
         $inquiry->detail_id,
         $inquiry->progress,
     ];
 }
 


        /**
 * Mengatur gaya pada Excel.
 *
 * @param Worksheet $sheet
 * @return array
 */

    public function styles(Worksheet $sheet)
{
    $highestColumn = $sheet->getHighestColumn();
    $highestRow = $sheet->getHighestRow();

    // Membuat header tebal
    $headerRange = 'A1:' . $highestColumn . '1';
    $sheet->getStyle($headerRange)->getFont()->setBold(true);

    // Mengaktifkan wrap text untuk semua sel agar isi tidak terpotong
    $contentRange = 'A1:' . $highestColumn . $highestRow;
    $sheet->getStyle($contentRange)->getAlignment()->setWrapText(true);

    // Loop setiap kolom untuk mencari teks terpanjang dan menyesuaikan ukuran kolomnya
    foreach (range('A', $highestColumn) as $column) {
        $maxLength = 0;

        // Loop setiap baris dalam kolom ini (termasuk header)
        for ($row = 1; $row <= $highestRow; $row++) {
            $cellValue = $sheet->getCell($column . $row)->getValue();
            if ($cellValue) {
                $length = mb_strlen($cellValue); // Hitung panjang teks
                if ($length > $maxLength) {
                    $maxLength = $length; // Simpan teks terpanjang
                }
            }
        }

        // Set ukuran kolom mengikuti teks terpanjang dengan padding tambahan
        $columnWidth = min($maxLength + 2, 50); // Batas maksimal lebar kolom = 50
        $sheet->getColumnDimension($column)->setWidth($columnWidth);
    }
}
}