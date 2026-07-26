<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Http\Requests\KnowledgeManagement\Concerns\NormalizesKmAuthoringMetadata;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmDocumentAuthoringRules;
use Illuminate\Foundation\Http\FormRequest;

class KmDocumentAutosaveRequest extends FormRequest
{
    use NormalizesKmAuthoringMetadata;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $document = $this->route('kmPengajuan');
        $ownerId = $document instanceof KmPengajuan ? (int) $document->id_user : 0;

        return array_merge([
            'judul' => ['sometimes', 'string', 'min:1', 'max:255'],
            'keterangan' => ['sometimes', 'string', 'max:3000'],
            'revision' => ['required', 'integer', 'min:0'],
        ], KmDocumentAuthoringRules::rules($ownerId, true));
    }

    public function messages(): array
    {
        return array_merge(KmDocumentAuthoringRules::messages(), [
            'revision.required' => 'Revision wajib dikirim untuk autosave.',
        ]);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeAuthoringMetadata(false);
    }
}
