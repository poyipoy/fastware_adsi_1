<?php

namespace App\Http\Requests\KnowledgeManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KmCoAuthorOptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'document_id' => ['nullable', 'integer', Rule::exists('km_pengajuans', 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('q')) {
            $query = trim((string) $this->input('q'));
            $this->merge(['q' => $query === '' ? null : $query]);
        }
    }
}
