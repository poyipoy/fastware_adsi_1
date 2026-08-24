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
        $quantityRules = $type === 'IN'
            ? ['required', 'integer', 'min:1']
            : ['required', 'numeric', 'gt:0', 'decimal:0,3'];

        return [
            'type' => ['required', Rule::in(['IN', 'OUT'])],
            'item_condition' => ['required', Rule::in(['NEW', 'USED'])],
            'item_barcode' => ['required', 'string', 'max:120'],
            'quantity' => $quantityRules,
            'verified_code' => [Rule::requiredIf($type === 'OUT' || ($type === 'IN' && $condition === 'USED')), 'nullable', 'string', 'max:150'],
            'location' => array_merge(['required'], $locationRules),
            // Temporary aliases keep existing JSON clients compatible while
            // all new forms submit the single user-facing `location` field.
            'storage_location' => array_merge(['sometimes', 'nullable'], $locationRules),
            'source_location' => array_merge(['sometimes', 'nullable'], $locationRules),
            'return_used' => ['sometimes', 'boolean', Rule::prohibitedIf(! $usedReturnAllowed)],
            'used_return_item_barcode' => [Rule::requiredIf($withUsedReturn), Rule::prohibitedIf(! $withUsedReturn), 'string', 'max:120'],
            'used_return_quantity' => [Rule::requiredIf($withUsedReturn), Rule::prohibitedIf(! $withUsedReturn), 'numeric', 'gt:0', 'decimal:0,3'],
            'used_return_location' => array_merge([
                Rule::requiredIf($withUsedReturn),
                Rule::prohibitedIf(! $withUsedReturn),
            ], $locationRules),
            'notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => strtoupper(trim((string) $this->input('type'))),
            'item_condition' => strtoupper(trim((string) $this->input('item_condition', 'NEW'))),
            'location' => $this->input('location')
                ?: ($this->input('type') === 'IN' ? $this->input('storage_location') : $this->input('source_location')),
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

class StoreWarehouseStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.stock-in.create');
    }

    public function rules(): array
    {
        $locations = (array) config('warehouse.storage_locations', ['DS8', 'Deltamas']);
        $location = ['string', 'max:120', 'regex:/^[^\x00-\x1F\x7F]*$/u', Rule::in($locations)];

        return [
            'consumable_id' => [Rule::requiredIf(fn (): bool => ! $this->filled('item_barcode')), 'nullable', 'integer', Rule::exists('mst_wh_consumables', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'item_barcode' => [Rule::requiredIf(fn (): bool => ! $this->filled('consumable_id')), 'nullable', 'string', 'max:120'],
            // Pending Stock In is only the physical receiving workflow for
            // new items. Used-item Stock In continues through the direct
            // ledger path and is never put in the validation queue.
            'item_condition' => ['required', Rule::in(['NEW'])],
            'quantity_expected' => ['required', 'integer', 'min:1'],
            'destination_location' => array_merge(['required'], $location),
            'source_location' => array_merge(['sometimes', 'nullable'], $location),
            'notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'item_condition' => strtoupper(trim((string) $this->input('item_condition', 'NEW'))),
            'quantity_expected' => $this->input('quantity_expected', $this->input('quantity')),
            'destination_location' => $this->input('destination_location', $this->input('location')),
        ]);
    }
}

class ValidateWarehouseStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.stock-in.validate');
    }

    public function rules(): array
    {
        return [
            'quantity_received' => ['required', 'integer', 'min:1'],
            'validation_result' => ['sometimes', 'nullable', Rule::in(['MATCH', 'MANUAL_ADJUSTMENT'])],
            'received_item_barcode' => ['sometimes', 'nullable', 'string', 'max:120'],
            'validation_notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}

class CancelWarehouseStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.stock-in.create');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }
}
