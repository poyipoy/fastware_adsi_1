<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.report.view');
    }

    public function rules(): array
    {
        return [
            'year' => ['nullable', 'integer', 'min:2000', 'max:'.(now()->year + 1)],
            'condition' => ['required', Rule::in(['ALL', 'NEW', 'USED'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'condition' => strtoupper(trim((string) $this->input('condition', 'NEW'))),
        ]);
    }
}
