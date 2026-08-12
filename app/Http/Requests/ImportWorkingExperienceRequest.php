<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportWorkingExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'import_file' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls,csv'],
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.required' => 'File Excel wajib dipilih.',
            'import_file.max' => 'Ukuran file maksimal 5 MB.',
            'import_file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
        ];
    }
}
