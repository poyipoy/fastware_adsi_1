<?php

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use Illuminate\Validation\Rule;

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
