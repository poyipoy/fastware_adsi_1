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

class TcpdTopJobsExport implements FromArray, ShouldAutoSize, WithColumnWidths, WithTitle, WithEvents
{
    use Exportable;

    /** @var array */
    protected array $topJobs;

    /** @var array<string, mixed> */
    protected array $meta;

    /** @var string */
    protected string $sheetTitle;

    protected int $headerRow = 5;
    protected int $endRow = 5;

    /** @var array<int, array<int, int>> */
    protected array $mergeRanges = [];

    public function __construct(array $topJobs, array $meta = [], string $sheetTitle = 'Top Jobs')
    {
        $this->topJobs = $topJobs;
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
        $exportRows[] = ['LAPORAN PERFORMANCE - TOP 5 JOB POSITIONS'];
        $exportRows[] = ['Periode: ' . ($this->meta['period'] ?? '-') . ' | Departemen: ' . ($this->meta['department'] ?? 'All')];
        $exportRows[] = ['Tanggal Export: ' . ($this->meta['export_date'] ?? date('d/m/Y H:i'))];
        $exportRows[] = ['']; // Blank row 4

        // Table Header (Row 5)
        $this->headerRow = count($exportRows) + 1;
        $exportRows[] = [
            'No',
            'Job Position',
            'NPK',
            'Nama Karyawan',
            'TC (%)',
            'SK (%)',
            'AD (%)',
            'Average Position (%)',
        ];

        $counter = 1;
        foreach ($this->topJobs as $job) {
            $jobPosition = $job['job_position'] ?? '';
            $empCount = count($job['employees'] ?? []);
            $startRow = count($exportRows) + 1;
            $endRow   = $startRow + max(0, $empCount - 1);

            // Excel formula to calculate average across TC, SK, and AD for all employees in this position
            $formula = $empCount > 0 ? "=AVERAGE(E{$startRow}:G{$endRow})" : null;

            $first = true;
            foreach ($job['employees'] ?? [] as $emp) {
                $exportRows[] = [
                    $counter++,
                    $jobPosition,
                    $emp['npk'] ?? '-',
                    $emp['name'] ?? '',
                    $this->normalizeNumeric($emp['tc'] ?? null),
                    $this->normalizeNumeric($emp['sk'] ?? null),
                    $this->normalizeNumeric($emp['ad'] ?? null),
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
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2:A3')->getFont()->setItalic(true)->setSize(10)->getColor()->setArgb('FF495057');

                // 2. Table Styling
                if ($this->endRow >= $this->headerRow) {
                    // Green Header
                    $sheet->getStyle("A{$this->headerRow}:H{$this->headerRow}")->applyFromArray([
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

                    if ($this->endRow > $this->headerRow) {
                        $start = $this->headerRow + 1;
                        $end   = $this->endRow;

                        // Grey Table Borders
                        $sheet->getStyle("A{$start}:H{$end}")->applyFromArray([
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color'       => ['argb' => 'FFCCCCCC'],
                                ],
                            ],
                        ]);

                        // Alignment
                        $sheet->getStyle("A{$start}:H{$end}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                        $sheet->getStyle("A{$start}:A{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("B{$start}:D{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                        $sheet->getStyle("E{$start}:G{$end}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                        // Number Format (0.00)
                        $sheet->getStyle("E{$start}:H{$end}")->getNumberFormat()->setFormatCode('0.00');

                        // Merge vertical cells for Average Position (%)
                        foreach ($this->mergeRanges as $range) {
                            [$sRow, $eRow] = $range;
                            if ($eRow > $sRow) {
                                $sheet->mergeCells("H{$sRow}:H{$eRow}");
                            }
                            $sheet->getStyle("H{$sRow}:H{$eRow}")->getAlignment()
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
