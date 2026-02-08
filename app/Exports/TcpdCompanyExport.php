<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TcpdCompanyExport implements FromArray, WithHeadings, ShouldAutoSize
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
            $exportRows[] = [
                $counter++,
                $row['department'] ?? '',
                $row['job_position'] ?? '',
                $this->normalizeNumeric($row['average'] ?? null),
                $row['year'] ?? '',
                $this->normalizeNumeric($row['percentage'] ?? null),
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
            'Job Position',
            'Average',
            'Year',
            'Percentage',
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
