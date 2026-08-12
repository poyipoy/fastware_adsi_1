<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmDocumentVersion;
use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KmDocumentInteractionRequest extends FormRequest
{
    private ?KmPengajuan $resolvedDocument = null;

    private ?KmDocumentVersion $resolvedVersion = null;

    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $document = $this->resolveDocument();

        if ($document === null) {
            return true;
        }

        $versionId = $this->integer('document_version_id');
        if ($versionId > 0) {
            $version = KmDocumentVersion::query()->find($versionId);
            if ($version === null
                || (int) $version->km_pengajuan_id !== (int) $document->getKey()) {
                return true;
            }

            $this->resolvedVersion = $version;

            return $this->user()->can('viewVersion', [$document, $version]);
        }

        return $this->user()->can('view', $document);
    }

    public function rules(): array
    {
        return [
            'id_km_pengajuan' => ['required', 'integer', 'exists:km_pengajuans,id'],
            'document_version_id' => [
                'nullable',
                'integer',
                Rule::exists('km_document_versions', 'id')->where(
                    fn ($query) => $query->where('km_pengajuan_id', $this->integer('id_km_pengajuan')),
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $document = $this->route('kmPengajuan');
        if ($document instanceof KmPengajuan) {
            $this->merge(['id_km_pengajuan' => $document->getKey()]);
        }
    }

    public function document(): KmPengajuan
    {
        return $this->resolvedDocument ??= KmPengajuan::query()
            ->findOrFail($this->integer('id_km_pengajuan'));
    }

    public function version(): ?KmDocumentVersion
    {
        $versionId = $this->integer('document_version_id');
        if ($versionId <= 0) {
            return null;
        }

        return $this->resolvedVersion ??= KmDocumentVersion::query()
            ->where('km_pengajuan_id', $this->integer('id_km_pengajuan'))
            ->findOrFail($versionId);
    }

    private function resolveDocument(): ?KmPengajuan
    {
        $routeDocument = $this->route('kmPengajuan');
        if ($routeDocument instanceof KmPengajuan) {
            return $this->resolvedDocument ??= $routeDocument;
        }

        $documentId = $this->integer('id_km_pengajuan');
        if ($documentId <= 0) {
            return null;
        }

        return $this->resolvedDocument ??= KmPengajuan::query()->find($documentId);
    }
}
