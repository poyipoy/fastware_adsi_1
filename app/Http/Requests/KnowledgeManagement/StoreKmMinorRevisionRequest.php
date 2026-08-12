<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;

class StoreKmMinorRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('kmPengajuan');

        return $document instanceof KmPengajuan
            && ($this->user()?->can('minorRevision', $document) ?? false);
    }

    public function rules(): array
    {
        return [
            'change_note' => ['required', 'string', 'min:5', 'max:2000'],
            'tag_ids' => ['sometimes', 'array', 'max:10'],
            'tag_ids.*' => ['integer', 'distinct', 'exists:km_tags,id'],
        ];
    }
}
