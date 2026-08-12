<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TcpdCompanyExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithTitle, WithEvents
{
    use Exportable;

    /** @var array<int, array<string, mixed>> */
    protected array $rows;

    /** @var array<string, mixed> */
    protected array $meta;

    /** @var string */
    protected string $sheetTitle;

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $meta
     */
    public function __construct(array $rows, array $meta = [], string $sheetTitle = 'Company & Department')
    {
        $this->rows = $rows;
        $this->meta = $meta;
        $this->sheetTitle = $sheetTitle;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8, // Force Column No to stay compact
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $exportRows = [];

        // Title Block (Rows 1-3)
        $exportRows[] = ['LAPORAN PERFORMANCE COMPANY & DEPARTMENT - TCPD'];
        $exportRows[] = ['Periode: ' . ($this->meta['period'] ?? '-') . ' | Departemen: ' . ($this->meta['department'] ?? 'All')];
        $exportRows[] = ['Tanggal Export: ' . ($this->meta['export_date'] ?? date('d/m/Y H:i'))];
        $exportRows[] = ['']; // Blank row 4

        // Table Header (Row 5)
        $exportRows[] = [
            'No',
            'Department',
            'Job Position',
            'Average (%)',
            'Tahun',
            'Persentase (%)',
        ];

        // Data Rows (Rows 6+)
        $counter = 1;
        foreach ($this->rows as $row) {
            $exportRows[] = [
                $counter++,
                $row['department'] ?? '',
                $row['job_position'] ?? '',
                $this->normalizeNumeric($row['average'] ?? null),
                $row['year'] ?? '',
                $this->normalizeNumeric($row['percentage'] ?? null),
            ];
        }

        // Summary Row with Excel Formula
        if (!empty($this->rows)) {
            $startDataRow = 6;
            $lastDataRow = 5 + count($this->rows);
            $exportRows[] = [
                '',
                'Rata-rata Keseluruhan (Average)',
                '',
                "=AVERAGE(D{$startDataRow}:D{$lastDataRow})",
                '',
                "=AVERAGE(F{$startDataRow}:F{$lastDataRow})",
            ];
        }

        return $exportRows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // Prevent Column A from auto-sizing too wide
                $sheet->getColumnDimension('A')->setAutoSize(false);
                $sheet->getColumnDimension('A')->setWidth(8);

                // 1. Merge and Style Title Block (Rows 1-3)
                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');
                $sheet->mergeCells('A3:F3');

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setArgb('FF495057');

                // 2. Table Header Styling (Row 5) - Blue Header
                $sheet->getStyle('A5:F5')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF0D6EFD'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color'       => ['argb' => 'FF999999'],
                        ],
                    ],
                ]);

                // 3. Table Data Styling (Rows 6 to $lastRow)
                if ($lastRow >= 6) {
                    // Grey Table Borders
                    $sheet->getStyle("A6:F{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => 'FFCCCCCC'], // Grey borders
                            ],
                        ],
                    ]);

                    // Alignment
                    $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("E6:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B6:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("D6:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("F6:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Number Formatting (0.00)
                    $sheet->getStyle("D6:D{$lastRow}")->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle("F6:F{$lastRow}")->getNumberFormat()->setFormatCode('0.00');

                    // Highlight Summary / Company Rows
                    for ($r = 6; $r < $lastRow; $r++) {
                        $deptVal = $sheet->getCell("B{$r}")->getValue();
                        if ($deptVal === 'Company' || strpos((string)$deptVal, '---') !== false) {
                            $sheet->getStyle("A{$r}:F{$r}")->applyFromArray([
                                'font' => ['bold' => true],
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFF8F9FA'],
                                ],
                            ]);
                        }
                    }

                    // Style Summary Row at bottom
                    if (!empty($this->rows)) {
                        $sheet->mergeCells("A{$lastRow}:C{$lastRow}");
                        $sheet->getStyle("A{$lastRow}:F{$lastRow}")->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => [
                                'fillType'   => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFE9ECEF'], // Light grey summary
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['argb' => 'FF999999'],
                                ],
                            ],
                        ]);
                        $sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }
                }
            },
        ];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function normalizeNumeric($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : $value;
    }
}
