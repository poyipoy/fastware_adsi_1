<?php

namespace App\Http\Requests\KnowledgeManagement;

use App\Models\KmDocumentVersion;
use Illuminate\Foundation\Http\FormRequest;

class RecoverKmDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $version = $this->route('version');

        return $version instanceof KmDocumentVersion
            && ($this->user()?->can('recoverOriginal', $version->document) ?? false);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }
}
