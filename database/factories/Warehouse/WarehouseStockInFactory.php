<?php

namespace Database\Factories\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseStockInStatus;
use App\Models\User;
use App\Models\Warehouse\WarehouseConsumable;
use App\Models\Warehouse\WarehouseStockIn;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WarehouseStockInFactory extends Factory
{
    protected $model = WarehouseStockIn::class;

    public function definition(): array
    {
        return [
            'stock_in_number' => 'WH-IN-'.$this->faker->unique()->numerify('########'),
            'creation_idempotency_key' => (string) Str::uuid(),
            'status' => WarehouseStockInStatus::WAITING_VALIDATION,
            'consumable_id' => WarehouseConsumable::factory(),
            'item_condition' => WarehouseItemCondition::NEW,
            'quantity_expected' => '1.000',
            'destination_location' => 'DS8',
            'source_location' => null,
            'created_by' => User::factory(),
        ];
    }
}
