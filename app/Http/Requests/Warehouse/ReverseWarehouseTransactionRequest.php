<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;

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
