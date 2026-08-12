<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;

class StoreKmMajorRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('kmPengajuan');

        return $document instanceof KmPengajuan
            && ($this->user()?->can('revise', $document) ?? false);
    }

    public function rules(): array
    {
        return [
            'change_note' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
