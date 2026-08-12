<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Http\Requests\KnowledgeManagement\Concerns\NormalizesKmAuthoringMetadata;
use App\Models\KmPengajuan;
use App\Services\KnowledgeManagement\KmDocumentAuthoringRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKmDocumentRequest extends FormRequest
{
    use NormalizesKmAuthoringMetadata;

    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $document = KmPengajuan::query()->find($this->integer('id'));

        return $document === null || $this->user()->can('update', $document);
    }

    public function rules(): array
    {
        $document = KmPengajuan::query()->find($this->integer('id'));
        $filePresenceRule = $document !== null && ! $document->hasCompletePrivateFileMetadata()
            ? 'required'
            : 'nullable';
        $ownerId = $document === null ? 0 : (int) $document->id_user;

        return array_merge([
            'id' => ['required', 'integer', 'exists:km_pengajuans,id'],
            'judul' => ['required', 'string', 'max:255'],
            'keterangan' => ['required', 'string', 'max:3000'],
            'file' => [
                $filePresenceRule,
                'file',
                'mimes:pdf,ppt,pptx',
                'max:'.(int) config('knowledge_management.upload.maximum_kilobytes', 51_200),
            ],
        ], KmDocumentAuthoringRules::rules($ownerId));
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
