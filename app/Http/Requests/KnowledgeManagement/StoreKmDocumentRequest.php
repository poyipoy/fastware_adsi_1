<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Http\Requests\KnowledgeManagement\Concerns\NormalizesKmAuthoringMetadata;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmDocumentAuthoringRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreKmDocumentRequest extends FormRequest
{
    use NormalizesKmAuthoringMetadata;

    public function authorize(): bool
    {
        return $this->user()?->can('create', KmPengajuan::class) ?? false;
    }

    public function rules(): array
    {
        return array_merge([
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['required', 'string', 'max:3000'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,ppt,pptx',
                'max:'.(int) config('knowledge_management.upload.maximum_kilobytes', 51_200),
            ],
        ], KmDocumentAuthoringRules::rules((int) $this->user()->getKey()));
    }

    public function messages(): array
    {
        return KmDocumentAuthoringRules::messages();
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAuthoringMetadata(true);
    }
}
