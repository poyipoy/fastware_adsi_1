<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->canAdjust($this->user());
    }

    public function rules(): array
    {
        return [
            'consumable_id' => ['required', 'integer', 'exists:mst_wh_consumables,id'],
            'direction' => ['required', Rule::in(['IN', 'OUT'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason_category' => ['required', 'string', 'max:80'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'verified_code' => ['required', 'string', 'max:150'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
