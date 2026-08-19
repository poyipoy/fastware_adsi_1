<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateWarehouseLocationShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.location-shipment.validate');
    }

    public function rules(): array
    {
        return [
            'received_quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'received_condition' => ['required', Rule::in(['NEW', 'USED'])],
            'received_item_barcode' => ['nullable', 'string', 'max:120'],
            'validator_code' => ['required', 'string', 'max:150'],
            'validation_notes' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'received_condition' => strtoupper(trim((string) $this->input('received_condition', 'NEW'))),
            'validation_notes' => is_string($this->input('validation_notes'))
                ? trim((string) $this->input('validation_notes'))
                : $this->input('validation_notes'),
        ]);
    }
}
