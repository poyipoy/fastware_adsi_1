<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWarehouseStockAttentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.stock-attention.update');
    }

    public function rules(): array
    {
        return [
            'stock_attention_note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('stock_attention_note');

        $this->merge([
            'stock_attention_note' => is_string($note) && trim($note) !== '' ? trim($note) : null,
        ]);
    }
}
