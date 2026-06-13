<?php

namespace App\Exports;

use App\Models\ItemCode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemCodeExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    private Collection $items;

    public function __construct(Collection $items)
    {
        $this->items = $items->values();
    }

    public function collection(): Collection
    {
        return $this->items;
    }

    public function headings(): array
    {
        return [
            'Item',
            'Description',
            'Simulated Price',
            'Currency',
            'Unit',
            'Cost Component',
            'Average Price',
            '',
            'Latest Price',
            'No Pengajuan',
            'Tanggal',
            'Nama',
            'Supplier',
            'QTY',
            'Reason',
            'Status',
        ];
    }

    public function map($item): array
    {
        $simulatedPrice = $item->type === 'update_price'
            ? ($item->harga_baru !== null ? (float) $item->harga_baru : (float) $item->price_per_pcs)
            : (float) $item->price_per_pcs;
        $currency = strtoupper((string) ($item->currency ?? 'IDR'));
        $unit = strtolower(trim((string) ($item->unit ?? 'pcs')));

        return [
            $item->product_code,
            (string) ($item->description ?? ''),
            $simulatedPrice,
            $currency,
            $unit === '' ? 'pcs' : $unit,
            'MAT',
            0,
            $currency,
            0,
            (string) ($item->nomor_pengajuan ?? ''),
            optional($item->tanggal)->format('d-m-Y') ?: '',
            (string) (optional($item->creator)->name ?? ''),
            (string) ($item->supplier ?? ''),
            $item->qty !== null ? (float) $item->qty : null,
            (string) ($item->reason_new_price ?? ''),
            $this->statusLabel($item->status ?? null),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            ItemCode::STATUS_APPROVED_1 => 'Approved by Jessica',
            ItemCode::STATUS_APPROVED_2 => 'Approved by Cahyo',
            default => (string) ($status ?? ''),
        };
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('A1')->applyFromArray([
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
        ]);

        $sheet->getStyle('B1:P1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '993300'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($highestRow >= 2) {
            $sheet->getStyle('A2:A' . $highestRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'C0C0C0'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle('B2:P' . $highestRow)->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFFFFF'],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            $sheet->getStyle('C2:C' . $highestRow)
                ->getNumberFormat()
                ->setFormatCode('0.00');

            $sheet->getStyle('G2:G' . $highestRow)
                ->getNumberFormat()
                ->setFormatCode('0.00');

            $sheet->getStyle('I2:I' . $highestRow)
                ->getNumberFormat()
                ->setFormatCode('0.00');

            $sheet->getStyle('N2:N' . $highestRow)
                ->getNumberFormat()
                ->setFormatCode('0.00');

            $sheet->getStyle('C2:C' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G2:G' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I2:I' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('N2:N' . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getDefaultRowDimension()->setRowHeight(22);

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 28,
            'B' => 40,
            'C' => 16,
            'D' => 9,
            'E' => 7,
            'F' => 16,
            'G' => 14,
            'H' => 8,
            'I' => 12,
            'J' => 24,
            'K' => 14,
            'L' => 24,
            'M' => 24,
            'N' => 10,
            'O' => 32,
            'P' => 16,
        ];
    }
}
