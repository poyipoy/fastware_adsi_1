<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;

class KmDocumentInteractionRequest extends FormRequest
{
    private ?KmPengajuan $resolvedDocument = null;

    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $document = $this->resolveDocument();

        return $document === null || $this->user()->can('view', $document);
    }

    public function rules(): array
    {
        return [
            'id_km_pengajuan' => ['required', 'integer', 'exists:km_pengajuans,id'],
        ];
    }

    public function document(): KmPengajuan
    {
        return $this->resolvedDocument ??= KmPengajuan::query()
            ->findOrFail($this->integer('id_km_pengajuan'));
    }

    private function resolveDocument(): ?KmPengajuan
    {
        $documentId = $this->integer('id_km_pengajuan');
        if ($documentId <= 0) {
            return null;
        }

        return $this->resolvedDocument ??= KmPengajuan::query()->find($documentId);
    }
}
