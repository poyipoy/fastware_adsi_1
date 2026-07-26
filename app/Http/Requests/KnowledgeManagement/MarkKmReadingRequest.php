<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmPengajuan;
use Illuminate\Foundation\Http\FormRequest;

class MarkKmReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user() === null) {
            return false;
        }

        $document = KmPengajuan::query()->find($this->integer('id_km_pengajuan'));

        return $document === null || $this->user()->can('view', $document);
    }

    public function rules(): array
    {
        return [
            'id_km_pengajuan' => ['required', 'integer', 'exists:km_pengajuans,id'],
        ];
    }
}
