<?php

namespace App\Http\Requests\KnowledgeManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class KmInsightListRequest extends FormRequest
{
    private const ALLOWED_QUERY_KEYS = ['page', 'per_page', 'focus_id'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:25'],
            'focus_id' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unknown = array_diff(array_keys($this->query()), self::ALLOWED_QUERY_KEYS);
            if ($unknown !== []) {
                $validator->errors()->add(
                    'query',
                    'Parameter daftar insight tidak didukung: '.implode(', ', $unknown).'.',
                );
            }
        });
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? 10);
    }

    public function focusId(): ?int
    {
        $focusId = $this->validated('focus_id');

        return $focusId === null ? null : (int) $focusId;
    }
}
