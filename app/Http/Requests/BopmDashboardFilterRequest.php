<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BopmDashboardFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'end_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'material_id' => ['nullable', 'integer', 'exists:mst_material,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'start_year.integer' => 'Tahun mulai harus berupa angka.',
            'start_year.min' => 'Tahun mulai minimal 2000.',
            'start_year.max' => 'Tahun mulai maksimal 2100.',
            'end_year.integer' => 'Tahun akhir harus berupa angka.',
            'end_year.min' => 'Tahun akhir minimal 2000.',
            'end_year.max' => 'Tahun akhir maksimal 2100.',
            'material_id.integer' => 'Material ID harus berupa angka.',
            'material_id.exists' => 'Material yang dipilih tidak ditemukan.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null
        $this->merge([
            'start_year' => $this->start_year === '' ? null : (int) $this->start_year,
            'end_year' => $this->end_year === '' ? null : (int) $this->end_year,
            'material_id' => $this->material_id === '' || $this->material_id === 'all' ? null : (int) $this->material_id,
        ]);
    }
}

