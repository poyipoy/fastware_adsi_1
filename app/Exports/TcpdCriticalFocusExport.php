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

class TcpdCriticalFocusExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithTitle, WithEvents
{
    use Exportable;

    /** @var array */
    protected array $criticalFocus;

    /** @var array<string, mixed> */
    protected array $meta;

    /** @var string */
    protected string $sheetTitle;

    protected int $headerRow = 5;
    protected int $endRow = 5;

    /** @var array<int, array<int, int>> */
    protected array $mergeRanges = [];

    public function __construct(array $criticalFocus, array $meta = [], string $sheetTitle = 'Critical Focus Area')
    {
        $this->criticalFocus = $criticalFocus;
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
        $exportRows[] = ['LAPORAN PERFORMANCE - CRITICAL FOCUS AREA'];
        $exportRows[] = ['Periode: ' . ($this->meta['period'] ?? '-') . ' | Departemen: ' . ($this->meta['department'] ?? 'All')];
        $exportRows[] = ['Tanggal Export: ' . ($this->meta['export_date'] ?? date('d/m/Y H:i'))];
        $exportRows[] = ['']; // Blank row 4

        // Table Header (Row 5)
        $this->headerRow = count($exportRows) + 1;
        $exportRows[] = [
            'No',
            'Competency',
            'Nama Karyawan',
            'Nilai Aktual',
            'Standar',
            'Jumlah Karyawan < Standar',
        ];

        $counter = 1;
        foreach ($this->criticalFocus as $comp) {
            $compName = $comp['name'] ?? '';
            $empCount = count($comp['employees'] ?? []);
            $startRow = count($exportRows) + 1;
            $endRow   = $startRow + max(0, $empCount - 1);

            // Excel formula to count number of employees below standard in this competency block
            $formula = $empCount > 0 ? "=COUNTA(C{$startRow}:C{$endRow})" : 0;

            $first = true;
            foreach ($comp['employees'] ?? [] as $emp) {
                $exportRows[] = [
                    $counter++,
                    $compName,
                    $emp['name'] ?? '',
                    $this->normalizeNumeric($emp['actual'] ?? null),
                    $this->normalizeNumeric($emp['standard'] ?? null),
                    $first ? $formula : '',
                ];
                $first = false;
            }
            if ($endRow >= $startRow) {
                $this->mergeRanges[] = [$startRow, $endRow];
            }
        }
        $this->endRow = count($exportRows);

        return $exportRows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Prevent Column A from auto-sizing too wide
                $sheet->getColumnDimension('A')->setAutoSize(false);
                $sheet->getColumnDimension('A')->setWidth(8);

                // 1. Merge and Style Title Block (Rows 1-3)
                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');
                $sheet->mergeCells('A3:F3');

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setArgb('FF495057');

                // 2. Table Styling
                if ($this->endRow >= $this->headerRow) {
                    // Red Header
                    $sheet->getStyle("A{$this->headerRow}:F{$this->headerRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFDC3545'],
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

                    if ($this->endRow > $this->headerRow) {
                        $start = $this->headerRow + 1;
                        $end   = $this->endRow;

                        // Grey Table Borders
                        $sheet->getStyle("A{$start}:F{$end}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['argb' => 'FFCCCCCC'],
                                ],
                            ],
                        ]);

                        // Alignment
                        $sheet->getStyle("A{$start}:F{$end}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getStyle("A{$start}:A{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$start}:C{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle("D{$start}:E{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        // Number Format (0.00) for Nilai Aktual & Standar
                        $sheet->getStyle("D{$start}:E{$end}")->getNumberFormat()->setFormatCode('0.00');

                        // Merge vertical cells for Jumlah Karyawan < Standar
                        foreach ($this->mergeRanges as $range) {
                            [$sRow, $eRow] = $range;
                            if ($eRow > $sRow) {
                                $sheet->mergeCells("F{$sRow}:F{$eRow}");
                            }
                            $sheet->getStyle("F{$sRow}:F{$eRow}")->getAlignment()
                                  ->setVertical(Alignment::VERTICAL_CENTER)
                                  ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
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
