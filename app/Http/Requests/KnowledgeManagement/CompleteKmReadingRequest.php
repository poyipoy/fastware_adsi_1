<?php

namespace App\Http\Requests\KnowledgeManagement;

class CompleteKmReadingRequest extends KmDocumentInteractionRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'acknowledged' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'acknowledged.required' => 'Konfirmasi membaca wajib diberikan.',
            'acknowledged.accepted' => 'Anda harus mengonfirmasi bahwa dokumen telah dibaca dan dipahami.',
        ];
    }
}
