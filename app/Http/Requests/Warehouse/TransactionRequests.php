<?php

namespace App\Http\Requests\Warehouse;

use App\Enums\Warehouse\WarehouseMachineCode;
use App\Models\Warehouse\WarehouseStockTransaction;
use App\Services\Warehouse\WarehouseAccessService;
use App\Services\Warehouse\WarehouseVerifierPolicy;
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
        $quantityRules = $type === 'OUT'
            ? ['required', 'integer', 'min:1']
            : ['required', 'numeric', 'gt:0', 'decimal:0,3'];

        return [
            'type' => ['required', Rule::in(['IN', 'OUT'])],
            'item_condition' => ['required', Rule::in(['NEW', 'USED'])],
            'item_barcode' => ['required', 'string', 'max:120'],
            'quantity' => $quantityRules,
            'verified_code' => [Rule::requiredIf($type === 'OUT'), 'nullable', 'string', 'max:150'],
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
            'machine_type_used' => ['sometimes', 'nullable', 'string', 'max:50'],
            'machine_allocations' => [Rule::requiredIf($type === 'OUT'), 'array', 'min:1'],
            'machine_allocations.*.machine_code' => [Rule::requiredIf($type === 'OUT'), Rule::in(WarehouseMachineCode::values())],
            'machine_allocations.*.quantity' => [Rule::requiredIf($type === 'OUT'), 'integer', 'min:1'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('type') === 'OUT') {
                $allocations = (array) $this->input('machine_allocations', []);
                $seen = [];
                $sum = 0;
                foreach ($allocations as $alloc) {
                    $code = $alloc['machine_code'] ?? null;
                    if ($code && isset($seen[$code])) {
                        $validator->errors()->add('machine_allocations', "Mesin '{$code}' tidak boleh dipilih lebih dari sekali.");
                    }
                    if ($code) {
                        $seen[$code] = true;
                    }
                    $sum += (int) ($alloc['quantity'] ?? 0);
                }

                if ($this->filled('quantity') && (int) $this->input('quantity') !== $sum) {
                    $validator->errors()->add('quantity', 'Total quantity Stock Out harus sama dengan jumlah alokasi mesin.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $type = strtoupper(trim((string) $this->input('type')));
        $condition = strtoupper(trim((string) $this->input('item_condition', 'NEW')));
        $location = $this->input('location')
            ?: ($type === 'IN' ? $this->input('storage_location') : $this->input('source_location'));

        $mergeData = [
            'type' => $type,
            'item_condition' => $condition,
            'location' => $location,
        ];

        if ($type === 'OUT') {
            if ($this->has('machine_allocations')) {
                $rawAllocations = $this->input('machine_allocations');
                $normalizedAllocations = [];
                if (is_array($rawAllocations)) {
                    foreach ($rawAllocations as $key => $val) {
                        if (is_array($val)) {
                            $code = trim((string) ($val['machine_code'] ?? ''));
                            $qty = $val['quantity'] ?? null;
                        } else {
                            $code = trim((string) $key);
                            $qty = $val;
                        }

                        if ($qty === null || $qty === '') {
                            continue;
                        }

                        $normalizedAllocations[] = [
                            'machine_code' => $code,
                            'quantity' => (is_int($qty) || (is_string($qty) && ctype_digit($qty))) ? (int) $qty : $qty,
                        ];
                    }
                }
                $mergeData['machine_allocations'] = $normalizedAllocations;
                if (! empty($normalizedAllocations)) {
                    $sum = 0;
                    foreach ($normalizedAllocations as $alloc) {
                        if (is_int($alloc['quantity'])) {
                            $sum += $alloc['quantity'];
                        }
                    }
                    $mergeData['quantity'] = (string) $sum;
                }
            } elseif ($this->filled('quantity')) {
                // Backward compatibility for legacy tests without machine_allocations
                $code = trim((string) $this->input('machine_type_used', 'M1')) ?: 'M1';
                $validCodes = WarehouseMachineCode::values();
                if (in_array($code, $validCodes, true)) {
                    $mergeData['machine_allocations'] = [
                        [
                            'machine_code' => $code,
                            'quantity' => (int) $this->input('quantity'),
                        ],
                    ];
                }
            }
        }

        $this->merge($mergeData);
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
            'item_condition' => ['required', Rule::in(['NEW', 'USED'])],
            'quantity_expected' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
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
        return app(WarehouseVerifierPolicy::class)->canAccessValidationWorkspace($this->user());
    }

    public function rules(): array
    {
        return [
            'quantity_received' => ['required', 'numeric', 'gt:0', 'decimal:0,3'],
            'validation_result' => ['sometimes', 'nullable', Rule::in(['MATCH', 'MANUAL_ADJUSTMENT'])],
            'received_item_barcode' => ['sometimes', 'nullable', 'string', 'max:120'],
            'validation_notes' => ['sometimes', 'nullable', 'string', 'max:65535'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('quantity_received')) {
            $stockIn = $this->route('stockIn');
            if ($stockIn instanceof \App\Models\Warehouse\WarehouseStockIn) {
                $this->merge([
                    'quantity_received' => (string) $stockIn->quantity_expected,
                ]);
            }
        }
    }
}

class CancelWarehouseStockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseVerifierPolicy::class)->canAccessValidationWorkspace($this->user());
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if (! $this->filled('reason') && $this->filled('cancellation_reason')) {
            $merge['reason'] = $this->input('cancellation_reason');
        }
        if (! $this->filled('idempotency_key')) {
            $merge['idempotency_key'] = (string) \Illuminate\Support\Str::uuid();
        }
        if (! empty($merge)) {
            $this->merge($merge);
        }
    }
}
