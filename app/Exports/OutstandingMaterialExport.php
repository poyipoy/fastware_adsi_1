<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OutstandingMaterialExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private Collection $materials;
    private int $rowNumber = 0;

    public function __construct(Collection $materials)
    {
        $this->materials = $materials->values();
    }

    public function collection(): Collection
    {
        return $this->materials;
    }

    public function headings(): array
    {
        return [
            'NO',
            'Supplier',
            'TYPE',
            'Thickness',
            'Width',
            'Diameter',
            'Length',
            'QTY (PCS)',
            'Est QTY (KG)',
            'Number Invoice',
            'Status',
            'Estimasi ETA Port',
            'Estimasi ETA Warehouse',
            'Estimasi Bulan ETA',
            'Keterangan',
            'Estimasi Delay ETA Port',
            'Estimasi Delay ETA Warehouse',
            'DOKUMEN PACKING LIST DAN MTC',
        ];
    }

    public function map($material): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $material->supplier,
            $material->type,
            $this->numberValue($material->thickness),
            $this->numberValue($material->width),
            $this->numberValue($material->diameter),
            $material->length,
            $this->numberValue($material->qty_pcs),
            $this->numberValue($material->est_qty_kg),
            $material->number_invoice,
            $material->status,
            $this->dateValue($material->estimasi_eta_port),
            $this->dateValue($material->estimasi_eta_warehouse),
            $material->estimasi_bulan_eta,
            $material->keterangan,
            $this->dateValue($material->estimasi_delay_eta_port),
            $this->dateValue($material->estimasi_delay_eta_warehouse),
            $material->attachment_path,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
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

        if ($highestRow >= 2) {
            $sheet->getStyle('A2:' . $highestColumn . $highestRow)->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ]);
        }

        $sheet->getDefaultRowDimension()->setRowHeight(22);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 24,
            'C' => 18,
            'D' => 12,
            'E' => 12,
            'F' => 12,
            'G' => 12,
            'H' => 12,
            'I' => 14,
            'J' => 22,
            'K' => 18,
            'L' => 20,
            'M' => 24,
            'N' => 18,
            'O' => 18,
            'P' => 24,
            'Q' => 28,
            'R' => 34,
        ];
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d-m-Y');
        }

        return $value ? (string) $value : null;
    }

    private function numberValue(mixed $value): mixed
    {
        return $value === null ? null : (float) $value;
    }
}
