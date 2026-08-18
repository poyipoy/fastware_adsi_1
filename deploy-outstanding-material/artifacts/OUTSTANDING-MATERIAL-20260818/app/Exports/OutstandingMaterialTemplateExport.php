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

class OutstandingMaterialTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'NO',
            'Supplier',
            'Number Invoice',
            'TYPE',
            'Thickness',
            'Width',
            'Diameter',
            'Length',
            'QTY (PCS)',
            'Est QTY (KG)',
            'Status',
            'Estimasi ETA Port',
            'Estimasi ETA Warehouse',
            'Estimasi Bulan ETA',
            'Keterangan',
            'Estimasi Delay ETA Port',
            'Estimasi Delay ETA Warehouse',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));

        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '993300'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
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
        return [
            'A' => 8,
            'B' => 24,
            'C' => 22,
            'D' => 18,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 12,
            'I' => 12,
            'J' => 14,
            'K' => 18,
            'L' => 20,
            'M' => 24,
            'N' => 18,
            'O' => 18,
            'P' => 24,
            'Q' => 28,
        ];
    }
}
