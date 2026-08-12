<?php

namespace App\Http\Requests\KnowledgeManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class KmMentionOptionsRequest extends FormRequest
{
    private const ALLOWED_QUERY_KEYS = ['q'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $unknown = array_diff(array_keys($this->query()), self::ALLOWED_QUERY_KEYS);
            if ($unknown !== []) {
                $validator->errors()->add(
                    'query',
                    'Parameter pencarian mention tidak didukung: '.implode(', ', $unknown).'.',
                );
            }
        });
    }

    public function searchQuery(): ?string
    {
        $query = trim((string) ($this->validated('q') ?? ''));

        return $query === '' ? null : $query;
    }
}
