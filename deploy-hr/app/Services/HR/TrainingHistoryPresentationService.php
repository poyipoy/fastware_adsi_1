<?php

namespace App\Services\HR;

use App\Models\TcPeopleDevelopment;
use App\Models\User;
use Illuminate\Support\Collection;

class TrainingHistoryPresentationService
{
    public function __construct(
        private readonly HRRoleAccessService $roleAccess,
    ) {
    }

    /**
     * @param  iterable<array{training: TcPeopleDevelopment, participant: User}>  $rows
     * @return array{data: array<int, array<string, mixed>>, meta: array{total: int}}
     */
    public function payload(User $actor, iterable $rows): array
    {
        $data = Collection::make($rows)
            ->map(fn (array $row): array => $this->row($actor, $row['training'], $row['participant']))
            ->values()
            ->all();

        return [
            'data' => $data,
            'meta' => [
                'total' => count($data),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function row(User $actor, TcPeopleDevelopment $training, User $participant): array
    {
        $department = $training->section?->department
            ?? $training->jobPosition?->department;
        $hasFile = filled($training->file) || filled($training->file_name);
        $canDownload = $hasFile && (
            $this->roleAccess->hasFullAccess($actor)
            || trim((string) $training->modified_at) === trim((string) $actor->name)
        );

        return [
            'id' => (int) $training->id,
            'npk' => EmployeeIdentityFormatter::npk($participant->npk),
            'employee_name' => $this->fallback($participant->name),
            'program' => $this->fallback($training->program_training_plan ?: $training->program_training),
            'category' => $this->fallback($training->kategori_competency),
            'competency' => $this->fallback($training->competency),
            'institution' => $this->fallback($training->lembaga_plan ?: $training->lembaga),
            'period' => $this->fallback($training->due_date_plan ?: $training->due_date),
            'year' => $training->tahun_aktual ? (int) $training->tahun_aktual : null,
            'department_id' => $department?->id ? (int) $department->id : null,
            'department_name' => $this->fallback($department?->name),
            'has_file' => $hasFile,
            'can_download' => $canDownload,
            'download_url' => $canDownload
                ? route('download.pdf', ['id' => $training->id])
                : null,
        ];
    }

    private function fallback(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        return $value !== '' ? $value : '-';
    }
}
