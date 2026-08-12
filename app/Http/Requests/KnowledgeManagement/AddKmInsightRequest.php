<?php

namespace App\Http\Requests\KnowledgeManagement;

use Illuminate\Validation\Rule;

class AddKmInsightRequest extends KmDocumentInteractionRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'content' => ['required', 'string', 'max:1200'],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('km_insights', 'id')->whereNull('deleted_at'),
            ],
            'mention_ids' => ['sometimes', 'array', 'max:10'],
            'mention_ids.*' => ['integer', 'distinct', Rule::exists('users', 'id')->where('is_active', false)],
        ];
    }
}
