<?php

namespace App\Exports\KnowledgeManagement;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KmHrComplianceExport implements FromArray, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly Collection $rows) {}

    public function array(): array
    {
        return [
            ['Employee ID', 'Nama', 'Department Snapshot', 'Dokumen', 'Versi', 'Assignment', 'Due Date', 'Completion', 'Exemption'],
            ...$this->rows->map(static fn (object $row): array => [
                $row->employee_id,
                $row->name,
                $row->department_snapshot,
                $row->document_title,
                $row->version_number,
                $row->assignment_title,
                $row->due_at,
                $row->completed_at,
                $row->exemption,
            ])->all(),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
