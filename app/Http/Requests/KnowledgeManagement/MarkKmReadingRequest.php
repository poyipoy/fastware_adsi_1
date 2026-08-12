<?php

namespace App\Http\Requests\KnowledgeManagement;

class MarkKmReadingRequest extends KmDocumentInteractionRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
