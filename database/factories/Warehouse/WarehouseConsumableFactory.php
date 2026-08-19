<?php

namespace Database\Factories\Warehouse;

use App\Models\Warehouse\WarehouseConsumable;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseConsumableFactory extends Factory
{
    protected $model = WarehouseConsumable::class;

    public function definition(): array
    {
        return [
            'item_code' => 'CNS-'.$this->faker->unique()->numerify('#####'),
            'barcode' => $this->faker->unique()->numerify('089############'),
            'item_name' => $this->faker->words(3, true),
            'unit' => 'pcs',
            'allow_fraction' => false,
            'current_stock' => '0.000',
            'stock_deltamas' => '0.000',
            'stock_ds8' => '0.000',
            'stock_used_deltamas' => '0.000',
            'stock_used_ds8' => '0.000',
            'minimum_stock' => '0.000',
            'maximum_stock' => null,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (WarehouseConsumable $item): void {
            $current = \App\Services\Warehouse\WarehouseQuantity::toMilli((string) $item->current_stock);
            $ds8 = \App\Services\Warehouse\WarehouseQuantity::toMilli((string) $item->stock_ds8);
            $deltamas = \App\Services\Warehouse\WarehouseQuantity::toMilli((string) $item->stock_deltamas);

            if ($ds8 + $deltamas !== $current) {
                $item->forceFill(['stock_ds8' => \App\Services\Warehouse\WarehouseQuantity::fromMilli($current), 'stock_deltamas' => '0.000'])->saveQuietly();
            }
        });
    }
}
