<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(WarehouseAccessService::class);
        $type = strtoupper((string) $this->input('type'));

        return $type === 'IN'
            ? $access->can($this->user(), 'warehouse.stock-in.create')
            : ($type === 'OUT' && $access->can($this->user(), 'warehouse.stock-out.create'));
    }

    public function rules(): array
    {
        $type = strtoupper((string) $this->input('type'));
        $rules = [
            'type' => ['required', Rule::in(['IN', 'OUT'])],
            'item_barcode' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'verified_code' => ['required', 'string', 'max:150'],
            'storage_location' => [
                'nullable',
                'string',
                'max:120',
                'regex:/^[^\x00-\x1F\x7F]*$/u',
                Rule::in((array) config('warehouse.storage_locations', ['DS8', 'Deltamas'])),
            ],
            'idempotency_key' => ['required', 'uuid'],
        ];

        if ($type === 'IN' && config('warehouse.transaction.require_storage_location_for_in', true)) {
            $rules['storage_location'][] = 'required';
        }
        if ($type === 'OUT') {
            $rules['storage_location'][] = 'prohibited';
        }

        return $rules;
    }
}

class ReverseWarehouseTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.transaction.reverse');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'verified_code' => ['required', 'string', 'max:150'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
