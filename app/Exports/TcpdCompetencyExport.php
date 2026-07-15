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

class TcpdCompetencyExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithTitle, WithEvents
{
    use Exportable;

    /** @var array<int, array<string, mixed>> */
    protected array $rows;

    /** @var array<string, mixed> */
    protected array $meta;

    /** @var string */
    protected string $sheetTitle;

    /** @var array<int, array<int, int>> */
    protected array $mergeRanges = [];

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $meta
     */
    public function __construct(array $rows, array $meta = [], string $sheetTitle = 'Area Development')
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
        $exportRows[] = ['LAPORAN AREA DEVELOPMENT - JOB POSITION LEVEL'];
        $exportRows[] = ['Job Position: ' . ($this->meta['job_position'] ?? 'All') . ' | Rentang Tanggal: ' . ($this->meta['date_range'] ?? 'Semua Periode')];
        $exportRows[] = ['Tanggal Export: ' . ($this->meta['export_date'] ?? date('d/m/Y H:i'))];
        $exportRows[] = ['']; // Blank row 4

        // Table Header (Row 5)
        $exportRows[] = [
            'No',
            'Department',
            'Section',
            'Job Position',
            'Nama Karyawan',
            'Competency',
            'Nilai Aktual',
            'Standar',
            'Rata-rata Kompetensi',
        ];

        // Data Rows (Rows 6+)
        $counter = 1;
        $currentComp = null;
        $startRow = null;

        foreach ($this->rows as $row) {
            $rowNum = count($exportRows) + 1;
            $compName = $row['competency'] ?? '';

            if ($compName !== $currentComp) {
                if ($startRow !== null && ($rowNum - 1) >= $startRow) {
                    $this->mergeRanges[] = [$startRow, $rowNum - 1];
                }
                $currentComp = $compName;
                $startRow = $rowNum;
            }

            $exportRows[] = [
                $counter++,
                $row['department'] ?? '',
                $row['section'] ?? '',
                $row['job_position'] ?? '',
                $row['user'] ?? '',
                $row['competency'] ?? '',
                $this->normalizeNumeric($row['actual'] ?? null),
                $this->normalizeNumeric($row['standard'] ?? null),
                $this->normalizeNumeric($row['average'] ?? null),
            ];
        }

        if ($startRow !== null && count($exportRows) >= $startRow) {
            $this->mergeRanges[] = [$startRow, count($exportRows)];
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
                $sheet->mergeCells('A1:I1');
                $sheet->mergeCells('A2:I2');
                $sheet->mergeCells('A3:I3');

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setArgb('FF495057');

                // 2. Table Header Styling (Row 5) - Grey Header
                $sheet->getStyle('A5:I5')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF6C757D'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color'       => ['argb' => 'FF495057'],
                        ],
                    ],
                ]);

                // 3. Table Data Styling (Rows 6 to $lastRow)
                if ($lastRow >= 6) {
                    // Grey Table Borders
                    $sheet->getStyle("A6:I{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => 'FFCCCCCC'], // Grey borders
                            ],
                        ],
                    ]);

                    // Alignment
                    $sheet->getStyle("A6:I{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("B6:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("G6:H{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Number Formatting (0.00)
                    $sheet->getStyle("G6:I{$lastRow}")->getNumberFormat()->setFormatCode('0.00');

                    // Merge vertical cells for Rata-rata Kompetensi (Column I)
                    foreach ($this->mergeRanges as $range) {
                        [$startRow, $endRow] = $range;
                        if ($endRow > $startRow) {
                            $sheet->mergeCells("I{$startRow}:I{$endRow}");
                        }
                        $sheet->getStyle("I{$startRow}:I{$endRow}")->getAlignment()
                              ->setVertical(Alignment::VERTICAL_CENTER)
                              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
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
