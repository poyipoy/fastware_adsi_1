<?php

namespace App\Exports;

use App\Models\DetailInquiry;
use App\Models\InquirySales;
use App\Models\Customer;
use App\Models\TypeMaterial;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverviewPurchaseExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    /** @var array<int> */
    protected array $ids;

    public function __construct(array $ids)
    {
        $this->ids = array_map('intval', $ids);
    }

    public function collection(): Collection
    {
        $di = (new DetailInquiry())->getTable();     // detail_inquiry
        $is = (new InquirySales())->getTable();      // inquiry_sales
        $cu = (new Customer())->getTable();          // customers
        $tm = (new TypeMaterial())->getTable();      // type_materials

        return DetailInquiry::query()
            ->from("$di as di")
            ->leftJoin("$is as ins", 'ins.id', '=', 'di.id_inquiry')
            ->leftJoin("$cu as c", 'c.id', '=', 'ins.id_customer')
            ->leftJoin("$tm as tm", 'tm.id', '=', 'di.id_type')
            ->whereIn('di.id_inquiry', $this->ids)
            ->orderBy('di.id_inquiry')
            ->orderBy('di.created_at')
            ->get([
                'di.*',
                'ins.kode_inquiry   as inquiry_kode',
                'ins.create_by      as inquiry_create_by',
                'ins.supplier       as inquiry_supplier',
                'c.name_customer    as customer_name',
                'tm.type_name       as material_type_name',
            ]);
    }

    public function headings(): array
    {
        // Inner & Outer sebelum Panjang
        return [
            'Reference',
            'Requestor',
            'Customer',
            'Material',
            'Tebal',
            'Lebar',
            'Inner',
            'Outer',
            'Panjang',
            'Supplier',
            'PR',
            'SO',
            'Last Update',
            'Notes',
        ];
    }

    /** @param \App\Models\DetailInquiry $detail */
    public function map($detail): array
    {
        $customerName = (string) ($detail->customer_name ?? '');
        $materialName = $detail->material_type_name
            ? (string) $detail->material_type_name
            : ($detail->nama_material ?? '');

        // Selalu ambil apa adanya dari kolom terkait
        $tebal       = $this->stringValue($detail->thickness);
        $lebar       = $this->stringValue($detail->weight);
        $innerValue  = $this->stringValue($detail->inner_diameter);
        $outerValue  = $this->stringValue($detail->outer_diameter);
        $panjang     = $this->stringValue($detail->length);

        $updatedAt = $detail->updated_at
            ? $detail->updated_at->format('Y-m-d H:i:s')
            : '';

        return [
            $this->stringValue($detail->inquiry_kode),     // Reference
            $this->stringValue($detail->inquiry_create_by),// Requestor
            $customerName,                                 // Customer
            $materialName,                                 // Material
            $tebal,                                        // Tebal
            $lebar,                                        // Lebar
            $innerValue,                                   // Inner
            $outerValue,                                   // Outer
            $panjang,                                      // Panjang
            $this->stringValue($detail->inquiry_supplier), // Supplier
            '',                                            // PR (isi sesuai kebutuhan)
            $this->stringValue($detail->so),               // SO
            $updatedAt,                                    // Last Update
            $this->stringValue($detail->note),             // Notes
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow    = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
              ->getAlignment()->setWrapText(true);

        foreach ($sheet->getColumnIterator() as $column) {
            $idx = $column->getColumnIndex();
            $sheet->getColumnDimension($idx)->setAutoSize(true);
        }

        return [];
    }

    protected function stringValue($value): string
    {
        return $value === null ? '' : (string) $value;
    }
}
