<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseLocationShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.location-shipment.create');
    }

    public function rules(): array
    {
        $locations = (array) config('warehouse.storage_locations', ['DS8', 'Deltamas']);

        return [
            'consumable_id' => [
                'required',
                'integer',
                Rule::exists('mst_wh_consumables', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'item_condition' => ['required', Rule::in(['NEW', 'USED'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'from_location' => ['required', Rule::in($locations), 'different:to_location'],
            'to_location' => ['required', Rule::in($locations), 'different:from_location'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'item_condition' => strtoupper(trim((string) $this->input('item_condition', 'NEW'))),
            'from_location' => trim((string) $this->input('from_location')),
            'to_location' => trim((string) $this->input('to_location')),
            'notes' => is_string($this->input('notes')) ? trim((string) $this->input('notes')) : $this->input('notes'),
        ]);
    }
}
