<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseOpeningBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->canAdjust($this->user());
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
            'verified_code' => ['required', 'string', 'max:150'],
            'reason' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'uuid'],
        ];
    }
}
