<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemCodeImportTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    private string $importType;

    public function __construct(string $importType = 'new_product')
    {
        $this->importType = in_array($importType, ['new_product', 'update_price'], true)
            ? $importType
            : 'new_product';
    }

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        if ($this->importType === 'update_price') {
            return [
                'nomor_pengajuan',
                'tanggal',
                'creator',
                'category',
                'supplier',
                'product_code',
                'description',
                'qty',
                'unit',
                'currency',
                'effective_date_current',
                'current_price',
                'effective_date_new',
                'new_price',
                'reason',
                'selisih',
            ];
        }

        return [
            'nomor_pengajuan',
            'tanggal',
            'creator',
            'category',
            'supplier',
            'product_code',
            'description',
            'qty',
            'unit',
            'currency',
            'price',
            'reason',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
        $headerRange = 'A1:' . $lastColumn . '1';

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '993300'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getDefaultRowDimension()->setRowHeight(22);

        return [];
    }

    public function columnWidths(): array
    {
        $widths = [];

        foreach ($this->headings() as $index => $heading) {
            $column = Coordinate::stringFromColumnIndex($index + 1);

            $widths[$column] = match ($heading) {
                'nomor_pengajuan' => 24,
                'creator' => 24,
                'supplier' => 24,
                'product_code' => 24,
                'description' => 36,
                'price', 'current_price', 'new_price', 'selisih' => 16,
                'tanggal', 'effective_date_current', 'effective_date_new' => 20,
                'reason', 'reason_new_price' => 32,
                'category' => 16,
                'currency' => 10,
                'qty', 'unit' => 10,
                default => 14,
            };
        }

        return $widths;
    }
}
