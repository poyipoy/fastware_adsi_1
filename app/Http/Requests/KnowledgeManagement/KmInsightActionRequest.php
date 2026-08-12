<?php

namespace App\Http\Requests\KnowledgeManagement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KmInsightActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'required', 'string', 'max:1200'],
            'mention_ids' => ['sometimes', 'array', 'max:10'],
            'mention_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('is_active', false),
            ],
            'reaction' => [
                'sometimes',
                'required',
                'string',
                Rule::in(config('knowledge_management.insights.reactions', [])),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
