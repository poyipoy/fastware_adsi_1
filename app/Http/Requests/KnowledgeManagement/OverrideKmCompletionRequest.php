<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Services\KnowledgeManagement\KmAccessService;
use Illuminate\Foundation\Http\FormRequest;

class OverrideKmCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && app(KmAccessService::class)->canOverrideCompletion($this->user());
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }
}
