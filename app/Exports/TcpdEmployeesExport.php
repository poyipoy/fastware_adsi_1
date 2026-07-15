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

class TcpdEmployeesExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithTitle, WithEvents
{
    use Exportable;

    /** @var array */
    protected array $topJobs;

    /** @var array */
    protected array $criticalFocus;

    /** @var array<string, mixed> */
    protected array $meta;

    /** @var string */
    protected string $sheetTitle;

    protected int $topJobsHeaderRow = 5;
    protected int $topJobsEndRow = 5;
    protected int $criticalHeaderRow = 0;
    protected int $criticalEndRow = 0;

    /** @var array<int, array<int, int>> */
    protected array $topJobsMergeRanges = [];

    /** @var array<int, array<int, int>> */
    protected array $criticalMergeRanges = [];

    public function __construct(array $topJobs, array $criticalFocus, array $meta = [], string $sheetTitle = 'Top Jobs & Critical Focus')
    {
        $this->topJobs = $topJobs;
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
        $exportRows[] = ['LAPORAN DETAIL KARYAWAN - TOP JOBS & CRITICAL FOCUS AREA'];
        $exportRows[] = ['Periode: ' . ($this->meta['period'] ?? '-') . ' | Departemen: ' . ($this->meta['department'] ?? 'All')];
        $exportRows[] = ['Tanggal Export: ' . ($this->meta['export_date'] ?? date('d/m/Y H:i'))];
        $exportRows[] = ['']; // Blank row 4

        // ── TABLE 1: TOP JOBS (Header at Row 5) ──────────────────────────────────
        $this->topJobsHeaderRow = count($exportRows) + 1;
        $exportRows[] = [
            'No',
            'Job Position',
            'Nama Karyawan',
            'TC (%)',
            'SK (%)',
            'AD (%)',
            'Average Position (%)',
        ];

        $counter1 = 1;
        foreach ($this->topJobs as $job) {
            $jobPosition = $job['job_position'] ?? '';
            $empCount = count($job['employees'] ?? []);
            $startRow = count($exportRows) + 1;
            $endRow   = $startRow + max(0, $empCount - 1);

            // Excel formula to calculate average across TC, SK, and AD for all employees in this position
            $formula = $empCount > 0 ? "=AVERAGE(D{$startRow}:F{$endRow})" : null;

            $first = true;
            foreach ($job['employees'] ?? [] as $emp) {
                $exportRows[] = [
                    $counter1++,
                    $jobPosition,
                    $emp['name'] ?? '',
                    $this->normalizeNumeric($emp['tc'] ?? null),
                    $this->normalizeNumeric($emp['sk'] ?? null),
                    $this->normalizeNumeric($emp['ad'] ?? null),
                    $first ? $formula : '',
                ];
                $first = false;
            }
            if ($endRow >= $startRow) {
                $this->topJobsMergeRanges[] = [$startRow, $endRow];
            }
        }
        $this->topJobsEndRow = count($exportRows);

        // ── SPACER ROWS ──────────────────────────────────────────────────────────
        $exportRows[] = ['', '', '', '', '', '', ''];
        $exportRows[] = ['', '', '', '', '', '', ''];

        // ── TABLE 2: CRITICAL FOCUS AREA ─────────────────────────────────────────
        $this->criticalHeaderRow = count($exportRows) + 1;
        $exportRows[] = [
            'No',
            'Competency',
            'Nama Karyawan',
            'Nilai Aktual',
            'Standar',
            'Jumlah Karyawan < Standar',
            '',
        ];

        $counter2 = 1;
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
                    $counter2++,
                    $compName,
                    $emp['name'] ?? '',
                    $this->normalizeNumeric($emp['actual'] ?? null),
                    $this->normalizeNumeric($emp['standard'] ?? null),
                    $first ? $formula : '',
                    '',
                ];
                $first = false;
            }
            if ($endRow >= $startRow) {
                $this->criticalMergeRanges[] = [$startRow, $endRow];
            }
        }
        $this->criticalEndRow = count($exportRows);

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
                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');
                $sheet->mergeCells('A3:G3');

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setArgb('FF495057');

                // 2. Table 1: Top Jobs Styling
                if ($this->topJobsEndRow >= $this->topJobsHeaderRow) {
                    // Green Header
                    $sheet->getStyle("A{$this->topJobsHeaderRow}:G{$this->topJobsHeaderRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FF198754'],
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

                    if ($this->topJobsEndRow > $this->topJobsHeaderRow) {
                        $start = $this->topJobsHeaderRow + 1;
                        $end   = $this->topJobsEndRow;

                        // Grey Table Borders
                        $sheet->getStyle("A{$start}:G{$end}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['argb' => 'FFCCCCCC'],
                                ],
                            ],
                        ]);

                        // Alignment
                        $sheet->getStyle("A{$start}:G{$end}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getStyle("A{$start}:A{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$start}:C{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle("D{$start}:F{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        // Number Format (0.00)
                        $sheet->getStyle("D{$start}:G{$end}")->getNumberFormat()->setFormatCode('0.00');

                        // Merge vertical cells for Average Position (%)
                        foreach ($this->topJobsMergeRanges as $range) {
                            [$sRow, $eRow] = $range;
                            if ($eRow > $sRow) {
                                $sheet->mergeCells("G{$sRow}:G{$eRow}");
                            }
                            $sheet->getStyle("G{$sRow}:G{$eRow}")->getAlignment()
                                  ->setVertical(Alignment::VERTICAL_CENTER)
                                  ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        }
                    }
                }

                // 3. Table 2: Critical Focus Styling
                if ($this->criticalEndRow >= $this->criticalHeaderRow) {
                    // Red Header
                    $sheet->getStyle("A{$this->criticalHeaderRow}:F{$this->criticalHeaderRow}")->applyFromArray([
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

                    if ($this->criticalEndRow > $this->criticalHeaderRow) {
                        $start = $this->criticalHeaderRow + 1;
                        $end   = $this->criticalEndRow;

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

                        // Number Format (0.00)
                        $sheet->getStyle("D{$start}:E{$end}")->getNumberFormat()->setFormatCode('0.00');

                        // Merge vertical cells for Jumlah Karyawan < Standar
                        foreach ($this->criticalMergeRanges as $range) {
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
