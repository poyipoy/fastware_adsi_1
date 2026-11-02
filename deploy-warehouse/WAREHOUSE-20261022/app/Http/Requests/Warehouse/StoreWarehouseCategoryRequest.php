<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.master.manage');
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $id = is_object($category) ? $category->getKey() : $category;

        return [
            'code' => ['required', 'string', 'max:30', 'regex:/^[^\x00-\x1F\x7F]*$/u', Rule::unique('mst_wh_consumable_categories', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => is_string($this->code) ? trim($this->code) : $this->code,
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'is_active' => $this->has('is_active') ? $this->boolean('is_active') : true,
        ]);
    }
}
