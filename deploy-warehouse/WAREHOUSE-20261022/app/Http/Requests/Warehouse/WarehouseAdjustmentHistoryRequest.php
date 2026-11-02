<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseAdjustmentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->canAdjust($this->user());
    }

    public function rules(): array
    {
        return [
            'adjustment_date_from' => ['nullable', 'date_format:Y-m-d'],
            'adjustment_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:adjustment_date_from'],
            'adjustment_consumable_id' => ['nullable', 'integer', 'exists:mst_wh_consumables,id'],
            'adjustment_condition' => ['nullable', Rule::in(['NEW', 'USED'])],
            'adjustment_direction' => ['nullable', Rule::in(['IN', 'OUT'])],
            'adjustment_verified_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
