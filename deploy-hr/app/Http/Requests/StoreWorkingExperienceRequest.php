<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkingExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'year_start' => ['required', 'integer', 'digits:4', 'min:1900', 'max:'.(date('Y') + 5)],
            'year_end' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:'.(date('Y') + 5), 'gte:year_start'],
            'job_position' => ['required', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'departemen' => ['nullable', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
