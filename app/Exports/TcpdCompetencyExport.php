<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TcpdCompetencyExport implements FromArray, WithHeadings, ShouldAutoSize
{
    use Exportable;

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $rows;

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $exportRows = [];
        $counter = 1;

        foreach ($this->rows as $row) {
            $actual = $row['actual'] ?? null;
            $standard = $row['standard'] ?? null;
            $section = $row['section'] ?? '';

            $exportRows[] = [
                $counter++,
                $row['department'] ?? '',
                $section,
                $row['job_position'] ?? '',
                $row['user'] ?? '',
                $row['competency'] ?? '',
                $this->normalizeNumeric($actual),
                $this->normalizeNumeric($standard),
            ];
        }

        return $exportRows;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Department',
            'Section',
            'Job Position',
            'Employee Name',
            'Competency',
            'Actual',
            'Standard',
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
