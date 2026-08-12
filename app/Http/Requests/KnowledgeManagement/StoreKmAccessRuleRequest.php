<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\MstJobPosition;
use App\Models\Role;
use App\Models\User;
use App\Services\KnowledgeManagement\KmAccessService;
use App\Services\KnowledgeManagement\KmRbacService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreKmAccessRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && app(KmAccessService::class)->canManageAccess($this->user());
    }

    public function rules(): array
    {
        return [
            'subject_type' => ['required', Rule::in(['user', 'role', 'job_position'])],
            'subject_id' => ['required', 'integer', 'min:1'],
            'ability' => ['required', Rule::in(array_keys(KmRbacService::ABILITIES))],
            'effect' => ['required', Rule::in(['allow', 'deny'])],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $exists = match ($this->input('subject_type')) {
                'user' => User::query()->whereKey($this->integer('subject_id'))->exists(),
                'role' => Role::query()->whereKey($this->integer('subject_id'))->exists(),
                'job_position' => MstJobPosition::query()->whereKey($this->integer('subject_id'))->exists(),
                default => false,
            };
            if (! $exists) {
                $validator->errors()->add('subject_id', 'Subjek akses tidak ditemukan.');
            }
        }];
    }
}
