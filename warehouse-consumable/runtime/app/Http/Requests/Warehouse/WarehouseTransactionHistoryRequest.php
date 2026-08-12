<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseTransactionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.transaction.view');
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'transaction_type' => ['nullable', Rule::in(['IN', 'OUT', 'ADJUSTMENT', 'REVERSAL'])],
            'consumable_id' => ['nullable', 'integer', 'exists:mst_wh_consumables,id'],
            'category_id' => ['nullable', 'integer', 'exists:mst_wh_consumable_categories,id'],
            'verified_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'section' => ['nullable', 'string', 'max:120'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'transaction_number' => ['nullable', 'string', 'max:40'],
        ];
    }
}
