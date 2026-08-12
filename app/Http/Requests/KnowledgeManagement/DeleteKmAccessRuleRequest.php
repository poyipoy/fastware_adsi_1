<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Services\KnowledgeManagement\KmAccessService;
use Illuminate\Foundation\Http\FormRequest;

class DeleteKmAccessRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && app(KmAccessService::class)->canManageAccess($this->user());
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }
}
