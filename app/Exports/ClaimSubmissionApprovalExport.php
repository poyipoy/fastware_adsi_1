<?php

namespace App\Exports;

use App\Models\MstClaimSubmission;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClaimSubmissionApprovalExport implements FromCollection, WithHeadings, WithStyles, WithEvents, ShouldAutoSize
{
    private array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection(): Collection
    {
        $claimsQuery = MstClaimSubmission::query();

        if (!empty($this->filters['status'])) {
            $claimsQuery->where('status', $this->filters['status']);
        } else {
            $claimsQuery->whereIn('status', ['open', 'on_progress', 'finished']);
        }

        $pic = trim((string) ($this->filters['pic'] ?? ''));
        if ($pic !== '') {
            $claimsQuery->where('modified_at', 'like', '%' . $pic . '%');
        }

        $category = trim((string) ($this->filters['category'] ?? ''));
        if ($category !== '') {
            $claimsQuery->where('category', $category);
        }

        $supplier = trim((string) ($this->filters['supplier'] ?? ''));
        if ($supplier !== '') {
            $claimsQuery->where('supplier', 'like', '%' . $supplier . '%');
        }

        if (!empty($this->filters['date_from'])) {
            $claimsQuery->whereDate('submission_date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $claimsQuery->whereDate('submission_date', '<=', $this->filters['date_to']);
        }

        $claims = $claimsQuery->orderByDesc('created_at')->get();

        return $claims->map(function ($claim) {
            return [
                'No. PR' => $this->sanitizeForExcel($claim->no_pr),
                'Nama Produk' => $this->sanitizeForExcel($claim->nama_produk),
                'Submission Date' => optional($claim->submission_date)->format('d-m-Y') ?: '-',
                'Category' => $this->sanitizeForExcel($claim->category),
                'Supplier' => $this->sanitizeForExcel($claim->supplier),
                'Description of Issue' => $this->sanitizeForExcel($claim->description_of_issue),
                'Proposed Solution' => $this->sanitizeForExcel($claim->proposed_solution),
                'Catatan Procurement' => $this->sanitizeForExcel($claim->catatan_procurement),
                'Status' => $this->statusLabel((string) $claim->status),
                'PIC' => $this->sanitizeForExcel($claim->modified_at),
                'File Name' => $this->sanitizeForExcel($claim->file_name),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No. PR',
            'Nama Produk',
            'Submission Date',
            'Category',
            'Supplier',
            'Description of Issue',
            'Proposed Solution',
            'Catatan Procurement',
            'Status',
            'PIC',
            'File Name',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(1, $sheet->getHighestRow());
                $range = 'A1:K' . $lastRow;

                $sheet->setAutoFilter($range);
                $sheet->freezePane('A2');

                $sheet->getStyle($range)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                if ($lastRow > 1) {
                    $sheet->getStyle('F2:H' . $lastRow)->getAlignment()->setWrapText(true);
                }
            },
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'Open',
            'on_progress' => 'On Progress',
            'finished' => 'Finished',
            default => 'Unknown',
        };
    }

    private function sanitizeForExcel(mixed $value): string
    {
        $text = trim((string) ($value ?? '-'));

        if ($text === '') {
            return '-';
        }

        if (preg_match('/^[=+\-@]/', $text) === 1) {
            return "'" . $text;
        }

        return $text;
    }
}
