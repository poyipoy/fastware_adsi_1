<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScanWarehouseItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(WarehouseAccessService::class);

        return $access->can($this->user(), 'warehouse.stock-out.create')
            || $access->can($this->user(), 'warehouse.stock-in.create')
            || $access->can($this->user(), 'warehouse.stock-in.validate');
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:120']];
    }
}

class ScanWarehouseUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(WarehouseAccessService::class);
        $type = strtoupper(trim((string) $this->input('type')));

        return match ($type) {
            'IN' => $access->can($this->user(), 'warehouse.stock-in.create'),
            'STOCK_IN_VALIDATE' => $access->can($this->user(), 'warehouse.stock-in.validate'),
            'OUT' => $access->can($this->user(), 'warehouse.stock-out.create'),
            'ADJUSTMENT' => $access->canAdjust($this->user()),
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(['IN', 'STOCK_IN_VALIDATE', 'OUT', 'ADJUSTMENT'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' => strtoupper(trim((string) $this->input('type'))),
        ]);
    }
}
