<?php

namespace App\Http\Requests\Warehouse;

use App\Services\Warehouse\WarehouseAccessService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseDashboardFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(WarehouseAccessService::class)->can($this->user(), 'warehouse.dashboard.view');
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'trend_date_from' => ['nullable', 'date_format:Y-m-d'],
            'trend_date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:trend_date_from'],
            'movement_machine' => ['nullable', 'string', 'max:120'],
            'movement_consumable_id' => ['nullable', 'integer', 'exists:mst_wh_consumables,id'],
            'stockout_machine' => ['nullable', 'string', 'max:120'],
            'stockout_month' => [
                'nullable',
                'date_format:Y-m',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    try {
                        $tz = config('app.timezone', 'Asia/Jakarta');
                        $targetMonth = CarbonImmutable::createFromFormat('!Y-m', trim($value), $tz)->startOfMonth();
                        $currentMonth = CarbonImmutable::now($tz)->startOfMonth();

                        if ($targetMonth->greaterThan($currentMonth)) {
                            $fail('Bulan Stock Out tidak boleh melebihi bulan berjalan.');
                        }
                    } catch (\Throwable) {
                    }
                },
            ],
            'transaction_type' => ['nullable', Rule::in(['IN', 'OUT', 'ADJUSTMENT', 'REVERSAL', 'TRANSFER'])],
            'category_id' => ['nullable', 'integer', 'exists:mst_wh_consumable_categories,id'],
            'consumable_id' => ['nullable', 'integer', 'exists:mst_wh_consumables,id'],
            'section' => ['nullable', 'string', 'max:120'],
            'verified_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'stock_status' => ['nullable', Rule::in(['HEALTHY', 'LOW', 'OUT'])],
            'condition' => ['nullable', 'string', Rule::in(['NEW', 'USED'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('condition')) {
            $this->merge([
                'condition' => strtoupper(trim((string) $this->input('condition'))),
            ]);
        }
    }
}
