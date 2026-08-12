<?php

namespace App\Http\Requests\KnowledgeManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KmApprovalQueueRequest extends FormRequest
{
    private const ALLOWED_QUERY_KEYS = [
        'sort',
        'page',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['sometimes', 'string', Rule::in(['oldest', 'newest'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'Urutan antrean persetujuan tidak valid.',
            'page.integer' => 'Nomor halaman harus berupa angka.',
            'page.min' => 'Nomor halaman minimal 1.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unknown = array_diff(array_keys($this->query()), self::ALLOWED_QUERY_KEYS);
            if ($unknown !== []) {
                $validator->errors()->add(
                    'query',
                    'Parameter antrean tidak didukung: '.implode(', ', $unknown).'.',
                );
            }
        });
    }

    public function sortBy(): string
    {
        return (string) ($this->validated('sort') ?? 'oldest');
    }
}
