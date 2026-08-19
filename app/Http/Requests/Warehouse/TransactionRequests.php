<?php

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse\WarehouseStockTransaction;
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
        $condition = strtoupper((string) $this->input('item_condition', 'NEW'));
        $withUsedReturn = $this->boolean('return_used');
        $usedReturnAllowed = $type === 'OUT' && $condition === 'NEW';
        $locations = (array) config('warehouse.storage_locations', ['DS8', 'Deltamas']);
        $locationRules = ['string', 'max:120', 'regex:/^[^\x00-\x1F\x7F]*$/u', Rule::in($locations)];

        return [
            'type' => ['required', Rule::in(['IN', 'OUT'])],
            'item_condition' => ['required', Rule::in(['NEW', 'USED'])],
            'item_barcode' => ['required', 'string', 'max:120'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'verified_code' => ['required', 'string', 'max:150'],
            'storage_location' => array_merge([
                Rule::requiredIf($type === 'IN'),
                Rule::prohibitedIf($type !== 'IN'),
            ], $locationRules),
            'source_location' => array_merge([
                Rule::requiredIf($type === 'OUT'),
                Rule::prohibitedIf($type !== 'OUT'),
            ], $locationRules),
            'return_used' => ['sometimes', 'boolean', Rule::prohibitedIf(! $usedReturnAllowed)],
            'used_return_item_barcode' => [Rule::requiredIf($withUsedReturn), Rule::prohibitedIf(! $withUsedReturn), 'string', 'max:120'],
            'used_return_quantity' => [Rule::requiredIf($withUsedReturn), Rule::prohibitedIf(! $withUsedReturn), 'numeric', 'gt:0', 'decimal:0,3'],
            'used_return_location' => array_merge([
                Rule::requiredIf($withUsedReturn),
                Rule::prohibitedIf(! $withUsedReturn),
            ], $locationRules),
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => strtoupper(trim((string) $this->input('type'))),
            'item_condition' => strtoupper(trim((string) $this->input('item_condition', 'NEW'))),
        ]);
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
        $transaction = $this->route('transaction');
        $requiresLegacyLocation = $transaction instanceof WarehouseStockTransaction
            && $transaction->from_location === null
            && $transaction->to_location === null;

        return [
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
            'verified_code' => ['required', 'string', 'max:150'],
            'legacy_location' => [Rule::requiredIf($requiresLegacyLocation), 'nullable', Rule::in((array) config('warehouse.storage_locations', ['DS8', 'Deltamas']))],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
