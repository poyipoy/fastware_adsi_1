<?php

namespace App\Exports;

use App\Exports\Concerns\StylesTrainingWorkbook;
use App\Services\HR\EmployeeIdentityFormatter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

abstract class AbstractTrainingQueryExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithMapping
{
    use StylesTrainingWorkbook;

    public function __construct(private readonly Builder $builder)
    {
    }

    public function query(): Builder
    {
        return $this->builder;
    }

    public function headings(): array
    {
        return [
            'Tahun', 'Jenis', 'Departemen', 'Section', 'Job Position', 'NPK Participant',
            'Nama Participant', 'Program Usulan', 'Program Aktual', 'Kategori', 'Competency',
            'Due Date', 'Tanggal Aktual', 'Biaya Usulan', 'Biaya Aktual', 'Status Persetujuan',
            'Status Progres', 'Lembaga', 'Objective Learning', 'Objective Aktual',
        ];
    }

    public function map($training): array
    {
        $participants = $this->participants($training);

        return [
            (int) $training->tahun_aktual,
            $training->is_sharing_knowledge ? 'Sharing Knowledge' : 'Training',
            $training->section?->department?->name ?? $training->jobPosition?->department?->name ?? '-',
            $training->section?->name ?? '-',
            $training->jobPosition?->position_name ?? '-',
            $participants->map(fn ($user) => EmployeeIdentityFormatter::npk($user->npk))->implode(', '),
            $participants->pluck('name')->filter()->implode(', ') ?: '-',
            $training->program_training ?? '-',
            $training->program_training_plan ?? '-',
            $training->kategori_competency ?? '-',
            $training->competency ?? '-',
            $this->excelDate($training->due_date),
            $this->excelDate($training->due_date_plan),
            $this->money($training->biaya),
            $this->money($training->biaya_plan),
            (int) $training->status_1,
            $training->status_2 ?? '-',
            $training->lembaga_plan ?? $training->lembaga ?? '-',
            $training->objective_learning ?? '-',
            $training->objective_learning_aktual ?? '-',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'L' => 'dd-mmm-yyyy',
            'M' => 'dd-mmm-yyyy',
            'N' => '[$Rp-421] #,##0',
            'O' => '[$Rp-421] #,##0',
        ];
    }

    protected function participants($training): Collection
    {
        if ($training->is_sharing_knowledge && $training->participants->isNotEmpty()) {
            return $training->participants->unique('id')->values();
        }

        return collect([$training->user])->filter();
    }

    protected function excelDate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return ExcelDate::dateTimeToExcel(Carbon::parse($value));
        } catch (\Throwable) {
            return null;
        }
    }

    protected function money(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $digits = preg_replace('/[^0-9-]/', '', (string) $value);

        return is_numeric($digits) ? (float) $digits : 0.0;
    }
}
