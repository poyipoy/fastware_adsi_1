<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTrainingFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $decoded = json_decode((string) $this->input('data'), true);
        if (is_array($decoded)) {
            $decoded = array_values(array_filter(
                $decoded,
                fn ($row): bool => ! is_array($row) || ! $this->shouldIgnoreInertRow($row),
            ));
        }

        $this->merge(['rows' => is_array($decoded) ? $decoded : null]);
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'json'],
            'rows' => ['present', 'array'],
            'rows.*.id' => ['required'],
            'rows.*.id_user' => ['nullable', 'integer'],
            'rows.*.is_sharing_knowledge' => ['required', 'boolean'],
            'rows.*.participant_user_ids' => ['present', 'array'],
            'rows.*.participant_user_ids.*' => ['integer'],
            'rows.*.section_id' => ['nullable', 'integer', 'exists:mst_sections,id'],
            'rows.*.id_job_position' => ['nullable', 'integer', 'exists:mst_job_positions,id'],
            'rows.*.due_date' => ['nullable', 'date'],
            'rows.*.due_date_plan' => ['nullable', 'date'],
            'rows.*.program_training' => ['nullable', 'string', 'max:255'],
            'rows.*.program_training_plan' => ['nullable', 'string', 'max:255'],
            'rows.*.kategori_competency' => ['nullable', 'string', 'max:100'],
            'rows.*.competency' => ['nullable', 'string', 'max:255'],
            'rows.*.biaya' => ['nullable'],
            'rows.*.biaya_plan' => ['nullable'],
            'rows.*.lembaga' => ['nullable', 'string', 'max:255'],
            'rows.*.lembaga_plan' => ['nullable', 'string', 'max:255'],
            'rows.*.keterangan_tujuan' => ['nullable', 'string', 'max:2000'],
            'rows.*.keterangan_plan' => ['nullable', 'string', 'max:2000'],
            'rows.*.objective_learning' => ['nullable', 'string', 'max:5000'],
            'rows.*.objective_learning_aktual' => ['nullable', 'string', 'max:5000'],
            'rows.*.status_2' => ['nullable', Rule::in([
                'Mencari Vendor',
                'Proses Pendaftaran',
                'On Progress',
                'Done',
                'Pending',
                'Ditolak',
            ])],
            'tahun_aktual' => ['nullable', 'integer', 'digits:4'],
            'action' => ['nullable', 'in:save,approve'],
            'file.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,xlsx,xls,csv,doc,docx'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ((array) $this->input('rows', []) as $index => $row) {
                if (! is_array($row)) {
                    continue;
                }

                $sharing = filter_var($row['is_sharing_knowledge'] ?? false, FILTER_VALIDATE_BOOLEAN);
                if ($sharing && empty($row['participant_user_ids'])) {
                    $validator->errors()->add("rows.{$index}.participant_user_ids", 'Sharing Knowledge wajib memiliki participant.');
                }
                if ($sharing) {
                    $participantIds = collect($row['participant_user_ids'] ?? [])
                        ->filter(fn ($value): bool => trim((string) ($value ?? '')) !== '')
                        ->map(fn ($value): int => (int) $value);

                    if ($participantIds->count() !== $participantIds->unique()->count()) {
                        $validator->errors()->add(
                            "rows.{$index}.participant_user_ids",
                            'Baris '.($index + 1).': karyawan yang sama tidak boleh dipilih lebih dari satu kali dalam satu kegiatan.',
                        );
                    }
                }
                if (! $sharing && empty($row['id_user'])) {
                    $validator->errors()->add("rows.{$index}.id_user", 'Training biasa wajib memiliki satu karyawan.');
                }
            }
        }];
    }

    private function shouldIgnoreInertRow(array $row): bool
    {
        $rowId = (string) ($row['id'] ?? '');
        $hasMeaningfulInput = $this->hasMeaningfulInput($row)
            || ($rowId !== '' && $this->hasFile('file.'.$rowId));

        if (str_starts_with($rowId, 'new_')) {
            return ! $hasMeaningfulInput;
        }

        $isSharing = filter_var(
            $row['is_sharing_knowledge'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        return $isSharing && ! $hasMeaningfulInput;
    }

    private function hasMeaningfulInput(array $row): bool
    {
        $participantIds = collect($row['participant_user_ids'] ?? [])
            ->contains(fn ($value): bool => trim((string) ($value ?? '')) !== '');

        if ($participantIds || trim((string) ($row['id_user'] ?? '')) !== '') {
            return true;
        }

        $hasContent = collect([
            $row['program_training'] ?? null,
            $row['program_training_plan'] ?? null,
            $row['competency'] ?? null,
            $row['due_date'] ?? null,
            $row['due_date_plan'] ?? null,
            $row['lembaga'] ?? null,
            $row['lembaga_plan'] ?? null,
            $row['keterangan_tujuan'] ?? null,
            $row['keterangan_plan'] ?? null,
            $row['objective_learning'] ?? null,
            $row['objective_learning_aktual'] ?? null,
            $row['status_2'] ?? null,
        ])->contains(fn ($value): bool => trim((string) ($value ?? '')) !== '');

        if ($hasContent) {
            return true;
        }

        return collect([
            $row['biaya'] ?? null,
            $row['biaya_plan'] ?? null,
        ])->contains(function ($value): bool {
            $normalized = preg_replace('/[^0-9.-]/', '', (string) ($value ?? ''));

            return is_numeric($normalized) && (float) $normalized !== 0.0;
        });
    }
}
