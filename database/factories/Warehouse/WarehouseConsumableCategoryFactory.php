<?php

namespace Database\Factories\Warehouse;

use App\Models\Warehouse\WarehouseConsumableCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseConsumableCategoryFactory extends Factory
{
    protected $model = WarehouseConsumableCategory::class;

    public function definition(): array
    {
        return [
            'code' => 'CAT-'.$this->faker->unique()->numerify('###'),
            'name' => $this->faker->words(2, true),
            'description' => null,
            'is_active' => true,
        ];
    }
}
