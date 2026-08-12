<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;

class ScanWarehouseItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(WarehouseAccessService::class);

        return $access->can($this->user(), 'warehouse.stock-out.create') || $access->can($this->user(), 'warehouse.stock-in.create');
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:120']];
    }
}
