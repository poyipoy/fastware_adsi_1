<?php

namespace App\Exports;

use App\Exports\Concerns\StylesTrainingWorkbook;
use App\Models\User;
use App\Services\HR\EmployeeIdentityFormatter;
use App\Services\HR\TrainingHistoryQueryService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TrainingHistoryExport implements FromGenerator, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings
{
    use StylesTrainingWorkbook;

    public function __construct(
        private readonly TrainingHistoryQueryService $history,
        private readonly User $actor,
        private readonly array $filters,
    ) {
    }

    public function headings(): array
    {
        return [
            'Tahun', 'Departemen', 'Section', 'Job Position', 'NPK', 'Nama Participant',
            'Jenis', 'Program Usulan', 'Program Aktual', 'Kategori', 'Competency', 'Tanggal Aktual',
            'Biaya Aktual', 'Status', 'Lembaga', 'Objective Learning', 'Objective Aktual',
        ];
    }

    public function generator(): \Generator
    {
        foreach ($this->history->flattened($this->actor, $this->filters) as $row) {
            $training = $row['training'];
            $participant = $row['participant'];

            yield [
                (int) $training->tahun_aktual,
                $training->section?->department?->name ?? $training->jobPosition?->department?->name ?? '-',
                $training->section?->name ?? '-',
                $training->jobPosition?->position_name ?? '-',
                EmployeeIdentityFormatter::npk($participant->npk),
                $participant->name,
                $training->is_sharing_knowledge ? 'Sharing Knowledge' : 'Training',
                $training->program_training ?? '-',
                $training->program_training_plan ?? '-',
                $training->kategori_competency ?? '-',
                $training->competency ?? '-',
                $this->excelDate($training->due_date_plan),
                $this->money($training->biaya_plan),
                $training->status_2 ?? '-',
                $training->lembaga_plan ?? $training->lembaga ?? '-',
                $training->objective_learning ?? '-',
                $training->objective_learning_aktual ?? '-',
            ];
        }
    }

    public function columnFormats(): array
    {
        return ['L' => 'dd-mmm-yyyy', 'M' => '[$Rp-421] #,##0'];
    }

    private function excelDate(mixed $value): ?float
    {
        try {
            return $value ? ExcelDate::dateTimeToExcel(Carbon::parse($value)) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function money(mixed $value): float
    {
        $digits = is_numeric($value) ? $value : preg_replace('/[^0-9-]/', '', (string) $value);

        return is_numeric($digits) ? (float) $digits : 0.0;
    }
}
