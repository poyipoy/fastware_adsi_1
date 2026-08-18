<?php

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.master.manage');
    }

    public function rules(): array
    {
        return [
            'item_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[^\x00-\x1F\x7F]*$/u',
                Rule::unique('mst_wh_consumables', 'item_code'),
                Rule::unique('mst_wh_consumables', 'barcode'),
            ],
            'item_name' => ['required', 'string', 'max:180'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'maximum_stock' => ['nullable', 'integer', 'min:0', 'gte:minimum_stock'],
            'storage_location' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[^\x00-\x1F\x7F]*$/u',
                Rule::in((array) config('warehouse.storage_locations', ['DS8', 'Deltamas'])),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'item_code' => is_string($this->item_code) ? trim($this->item_code) : $this->item_code,
            'item_name' => is_string($this->item_name) ? trim($this->item_name) : $this->item_name,
            'storage_location' => is_string($this->storage_location) ? trim($this->storage_location) : $this->storage_location,
            'maximum_stock' => $this->input('maximum_stock') === '' ? null : $this->input('maximum_stock'),
        ]);
    }
}

class UpdateWarehouseConsumableRequest extends StoreWarehouseConsumableRequest
{
    public function rules(): array
    {
        $consumable = $this->route('consumable');
        $id = $consumable instanceof WarehouseConsumable ? $consumable->getKey() : $consumable;

        return array_replace(parent::rules(), [
            'item_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[^\x00-\x1F\x7F]*$/u',
                Rule::unique('mst_wh_consumables', 'item_code')->ignore($id),
                Rule::unique('mst_wh_consumables', 'barcode')->ignore($id),
            ],
        ]);
    }
}

class StoreWarehouseOpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->canAdjust($this->user());
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'verified_code' => ['required', 'string', 'max:150'],
            'reason' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'uuid'],
        ];
    }
}
