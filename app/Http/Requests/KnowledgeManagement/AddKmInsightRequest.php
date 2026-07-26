<?php

namespace App\Http\Requests\KnowledgeManagement;

class AddKmInsightRequest extends KmDocumentInteractionRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'content' => ['required', 'string', 'max:1200'],
        ];
    }
}
