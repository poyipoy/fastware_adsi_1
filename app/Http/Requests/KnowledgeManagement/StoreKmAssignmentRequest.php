<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmDocumentVersion;
use App\Services\KnowledgeManagement\KmAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreKmAssignmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('due_at')) {
            $this->merge(['due_at' => now()->addDays(14)->format('Y-m-d\TH:i')]);
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null
            && app(KmAccessService::class)->canManageAssignments($this->user());
    }

    public function rules(): array
    {
        return [
            'document_version_id' => ['required', 'integer', 'exists:km_document_versions,id'],
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date', 'after:now'],
            'target_user_ids' => ['nullable', 'array', 'max:1000'],
            'target_user_ids.*' => ['integer', 'distinct:strict', Rule::exists('users', 'id')->where('is_active', false)],
            'target_department_ids' => ['nullable', 'array', 'max:50'],
            'target_department_ids.*' => ['integer', 'distinct:strict', Rule::exists('mst_departments', 'id')->where('is_active', true)],
            'target_job_position_ids' => ['nullable', 'array', 'max:100'],
            'target_job_position_ids.*' => ['integer', 'distinct:strict', Rule::exists('mst_job_positions', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (empty($this->input('target_user_ids'))
                && empty($this->input('target_department_ids'))
                && empty($this->input('target_job_position_ids'))) {
                $validator->errors()->add('target_user_ids', 'Pilih minimal satu target pengguna atau organisasi.');
            }
            $version = KmDocumentVersion::query()->find($this->integer('document_version_id'));
            if ($version !== null && ($version->version_status?->value ?? $version->version_status) !== 'published') {
                $validator->errors()->add('document_version_id', 'Assignment hanya dapat dibuat untuk versi published.');
            }
        }];
    }
}
