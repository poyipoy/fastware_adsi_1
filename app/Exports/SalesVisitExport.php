<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class SalesVisitExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $data;
    protected $startDate;
    protected $endDate;
    protected $salesFilter;
    protected $regionFilter;
    protected $companyFilter;
    protected $userName;

    public function __construct($data, $startDate, $endDate, $salesFilter, $regionFilter, $companyFilter, $userName)
    {
        $this->data = collect($data);
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->salesFilter = $salesFilter;
        $this->regionFilter = $regionFilter;
        $this->customer_nameFilter = $companyFilter; // Gunakan companyFilter sebagai customer_nameFilter karena ini filter nama pelanggan
        $this->userName = $userName;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Salesperson', 'Customer Name', 'New Customer Name', 'PIC', 'Plan Visit', 'Keterangan', 'Visit Date', 'Visit Result', 'Remark', 'Files'
        ];
    }

    public function map($row): array
    {
        $files = '-';
        if (is_array($row['files'])) {
            $files = implode(', ', $row['files']);
        } elseif (is_string($row['files'])) {
            $files = $row['files'];
        }

        return [
            $row['sales_name'],
            $row['customer_name'] ?? ($row['company'] ?? '-'),
            $row['new_customer_name'] ?? '-',
            $row['pic_cust'] ?? '-',
            $row['plan_date'],
            $row['keterangan'],
            $row['visit_date'],
            $row['visit_result'],
            $row['remark'],
            $files,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E0E0E0']]],
        ];
    }

    /**
     * Daftarkan event untuk memformat sheet sebagai tabel yang rapi (border, autofilter, header beku)
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(1, $this->data->count()) + 1; // header + rows (pastikan minimal ada header)
                $range = 'A1:J' . $lastRow;

                // Terapkan border tipis ke semua sel dalam rentang
                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                // Aktifkan autofilter dan bekukan baris header
                $sheet->setAutoFilter('A1:J' . $lastRow);
                $sheet->freezePane('A2');

                // Pastikan perataan header dan pembungkusan teks untuk kolom Files
                $sheet->getStyle('A1:J1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('J2:J' . $lastRow)->getAlignment()->setWrapText(true);
            }
        ];
    }
}
