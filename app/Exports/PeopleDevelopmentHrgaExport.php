<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PeopleDevelopmentHrgaExport implements FromArray, WithEvents, ShouldAutoSize
{
    protected $data;
    protected $formatRows = []; // To keep track of special rows for formatting

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $rows = [];
        
        // Baris 1: Super Header
        $rows[] = [
            'DATA TRAINING / PLAN', '', '', '', '', '', '', '', '', '', '', '',
            'DATA AKTUAL / REALISASI', '', '', '', '', '', ''
        ];

        // Baris 2: Kolom Utama
        $rows[] = [
            'NO',
            'Section',
            'Job Position',
            'Nama Karyawan',
            'Program Training',
            'Kategori Competency',
            'Competency',
            'Due Date',
            'Budget',
            'Lembaga',
            'Keterangan Tujuan',
            'Objective Learning',
            'Nama Program',
            'Date Actual',
            'Biaya Actual',
            'Lembaga',
            'Keterangan',
            'Sharing Knowledge',
            'Status'
        ];

        $dataUtama = $this->data->filter(function ($item) {
            return empty($item->tahun_usulan);
        });

        $dataAdditional = $this->data->filter(function ($item) {
            return !empty($item->tahun_usulan);
        });

        $totalBudget1 = 0;
        $totalBudgetActual1 = 0;
        $no = 1;
        $currentRow = 3; // Starting from row 3

        // Data Utama
        foreach ($dataUtama as $item) {
            $budgetNum = (float) str_replace(['Rp', '.', ' '], '', $item->biaya);
            $budgetActualNum = (float) str_replace(['Rp', '.', ' '], '', $item->biaya_plan);
            $totalBudget1 += $budgetNum;
            $totalBudgetActual1 += $budgetActualNum;

            $budget = $item->biaya ? 'Rp ' . number_format($budgetNum, 0, ',', '.') : '-';
            $budgetActual = $item->biaya_plan ? 'Rp ' . number_format($budgetActualNum, 0, ',', '.') : '-';
            
            $rows[] = [
                $no++,
                $item->section->name ?? '-',
                $item->jobPosition->position_name ?? '-',
                $item->user->name ?? '-',
                $item->program_training ?? '-',
                $item->kategori_competency ?? '-',
                $item->competency ?? '-',
                $item->due_date ?? '-',
                $budget,
                $item->lembaga ?? '-',
                $item->keterangan_tujuan ?? '-',
                $item->objective_learning ?? '-',
                $item->program_training_plan ?? '-',
                $item->due_date_plan ?? '-',
                $budgetActual,
                $item->lembaga_plan ?? '-',
                $item->keterangan_plan ?? '-',
                $item->objective_learning_aktual ?? '-',
                $item->status_2 ?? '-'
            ];
            $currentRow++;
        }

        // Sub Total 1
        $rows[] = [
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', '', ''
        ]; // Empty base row to avoid undefined offsets
        
        $subTotal1Row = $rows[count($rows) - 1];
        $subTotal1Row[1] = 'Sub Total 1: Rp ' . number_format($totalBudget1, 0, ',', '.');
        $subTotal1Row[12] = 'Sub Total Actual 1: Rp ' . number_format($totalBudgetActual1, 0, ',', '.');
        $rows[count($rows) - 1] = $subTotal1Row;
        
        $this->formatRows['subTotal1'] = $currentRow;
        $currentRow++;

        // ADDITIONAL Banner
        $rows[] = [
            'ADDITIONAL', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', '', ''
        ];
        $this->formatRows['additional'] = $currentRow;
        $currentRow++;

        $totalBudget2 = 0;
        $totalBudgetActual2 = 0;
        $no2 = 1;

        // Data Additional
        foreach ($dataAdditional as $item) {
            $budgetNum = (float) str_replace(['Rp', '.', ' '], '', $item->biaya);
            $budgetActualNum = (float) str_replace(['Rp', '.', ' '], '', $item->biaya_plan);
            $totalBudget2 += $budgetNum;
            $totalBudgetActual2 += $budgetActualNum;

            $budget = $item->biaya ? 'Rp ' . number_format($budgetNum, 0, ',', '.') : '-';
            $budgetActual = $item->biaya_plan ? 'Rp ' . number_format($budgetActualNum, 0, ',', '.') : '-';
            
            $rows[] = [
                $no2++,
                $item->section->name ?? '-',
                $item->jobPosition->position_name ?? '-',
                $item->user->name ?? '-',
                $item->program_training ?? '-',
                $item->kategori_competency ?? '-',
                $item->competency ?? '-',
                $item->due_date ?? '-',
                $budget,
                $item->lembaga ?? '-',
                $item->keterangan_tujuan ?? '-',
                $item->objective_learning ?? '-',
                $item->program_training_plan ?? '-',
                $item->due_date_plan ?? '-',
                $budgetActual,
                $item->lembaga_plan ?? '-',
                $item->keterangan_plan ?? '-',
                $item->objective_learning_aktual ?? '-',
                $item->status_2 ?? '-'
            ];
            $currentRow++;
        }

        // Sub Total 2
        $rows[] = [
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', '', ''
        ];
        $subTotal2Row = $rows[count($rows) - 1];
        $subTotal2Row[1] = 'Sub Total 2: Rp ' . number_format($totalBudget2, 0, ',', '.');
        $subTotal2Row[12] = 'Sub Total Actual 2: Rp ' . number_format($totalBudgetActual2, 0, ',', '.');
        $rows[count($rows) - 1] = $subTotal2Row;
        
        $this->formatRows['subTotal2'] = $currentRow;
        $currentRow++;

        // Grand Total
        $totalBudgetGrand = $totalBudget1 + $totalBudget2;
        $totalBudgetActualGrand = $totalBudgetActual1 + $totalBudgetActual2;
        
        $rows[] = [
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', '', ''
        ];
        $grandTotalRow = $rows[count($rows) - 1];
        $grandTotalRow[1] = 'Total: Rp ' . number_format($totalBudgetGrand, 0, ',', '.');
        $grandTotalRow[12] = 'Total Actual: Rp ' . number_format($totalBudgetActualGrand, 0, ',', '.');
        $rows[count($rows) - 1] = $grandTotalRow;
        
        $this->formatRows['grandTotal'] = $currentRow;

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = 'S'; // A sampai S = 19 kolom

                // 1. Merge Super Headers
                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('M1:S1');

                // 2. Style Super Headers
                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F83E4'] // Biru
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('M1:S1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0AD4E'] // Orange
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 3. Style Sub Headers (Row 2)
                $sheet->getStyle('A2:L2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F83E4'] // Biru
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                $sheet->getStyle('M2:S2')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F0AD4E'] // Orange
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 4. All Borders untuk seluruh tabel
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '00000000'],
                        ],
                    ],
                ]);
                
                // 5. Wrap Text untuk kolom Objective Learning (L) dan Sharing Knowledge (R)
                $sheet->getStyle("L3:L{$highestRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("R3:R{$highestRow}")->getAlignment()->setWrapText(true);

                $event->sheet->getColumnDimension('L')->setWidth(40);
                $event->sheet->getColumnDimension('R')->setWidth(40);
                
                // Vertical align top untuk isi data
                if ($highestRow >= 3) {
                    $sheet->getStyle("A3:S{$highestRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    
                    // Rata tengah (Center) untuk kolom NO, Due Date, Status
                    $sheet->getStyle("A3:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // NO
                    $sheet->getStyle("H3:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Due Date
                    $sheet->getStyle("N3:N{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Date Actual
                    $sheet->getStyle("S3:S{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Status
                }

                // 6. Style Sub Total 1
                if (isset($this->formatRows['subTotal1'])) {
                    $r = $this->formatRows['subTotal1'];
                    $sheet->mergeCells("B{$r}:I{$r}");
                    $sheet->mergeCells("M{$r}:Q{$r}");
                    $sheet->getStyle("B{$r}:I{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("M{$r}:Q{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$r}:S{$r}")->getFont()->setBold(true);
                }

                // 7. Style ADDITIONAL Banner
                if (isset($this->formatRows['additional'])) {
                    $r = $this->formatRows['additional'];
                    $sheet->mergeCells("A{$r}:S{$r}");
                    $sheet->getStyle("A{$r}:S{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E9ECEF'] // Light gray
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                }

                // 8. Style Sub Total 2
                if (isset($this->formatRows['subTotal2'])) {
                    $r = $this->formatRows['subTotal2'];
                    $sheet->mergeCells("B{$r}:I{$r}");
                    $sheet->mergeCells("M{$r}:Q{$r}");
                    $sheet->getStyle("B{$r}:I{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("M{$r}:Q{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$r}:S{$r}")->getFont()->setBold(true);
                }

                // 9. Style Grand Total
                if (isset($this->formatRows['grandTotal'])) {
                    $r = $this->formatRows['grandTotal'];
                    $sheet->mergeCells("B{$r}:I{$r}");
                    $sheet->mergeCells("M{$r}:Q{$r}");
                    $sheet->getStyle("B{$r}:I{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("M{$r}:Q{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("A{$r}:S{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D1E7DD'] // Light green
                        ]
                    ]);
                }
            },
        ];
    }
}
