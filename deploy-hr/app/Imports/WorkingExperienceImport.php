<?php

namespace App\Imports;

use App\Models\WorkingExperience;
use App\Services\HR\WorkingExperienceValidationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

class WorkingExperienceImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $successCount = 0;

    public int $skippedCount = 0;

    public array $failures = [];

    private array $preparedRows = [];

    public function __construct(private readonly WorkingExperienceValidationService $validator)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $source = array_change_key_case($row->toArray(), CASE_LOWER);
            $name = trim((string) ($source['nama_karyawan'] ?? ''));

            if ($name === '' && collect($source)->filter(fn ($value) => trim((string) $value) !== '')->isEmpty()) {
                continue;
            }

            if (str_contains(mb_strtolower($name), 'instruksi')
                || str_contains(mb_strtolower((string) ($source['npk'] ?? '')), 'instruksi')) {
                continue;
            }

            $payload = [
                'npk' => trim((string) ($source['npk'] ?? '')),
                'nama_karyawan' => $name,
                'year_start' => $source['tahun_mulai'] ?? null,
                'year_end' => $source['tahun_selesai'] ?? null,
                'job_position' => $source['jabatan'] ?? null,
                'section' => $source['section'] ?? null,
                'departemen' => $source['departemen'] ?? null,
                'keterangan' => $source['keterangan'] ?? null,
            ];

            try {
                $this->preparedRows[] = $this->validator->prepare($payload);
            } catch (ValidationException $exception) {
                $errors = $exception->errors();
                $this->failures[] = [
                    'row' => $rowNumber,
                    'npk' => $payload['npk'] ?: '-',
                    'name' => $name ?: '(kosong)',
                    'reason' => collect($errors)->flatten()->implode('; '),
                    'errors' => $errors,
                ];
            } catch (Throwable $exception) {
                $this->failures[] = [
                    'row' => $rowNumber,
                    'npk' => $payload['npk'] ?: '-',
                    'name' => $name ?: '(kosong)',
                    'reason' => $exception->getMessage(),
                    'errors' => ['row' => [$exception->getMessage()]],
                ];
            }
        }
    }

    public function persist(): void
    {
        DB::transaction(function (): void {
            foreach ($this->preparedRows as $payload) {
                $workingExperience = WorkingExperience::firstOrCreate($payload);

                if ($workingExperience->wasRecentlyCreated) {
                    $this->successCount++;
                } else {
                    $this->skippedCount++;
                }
            }
        });
    }
}
