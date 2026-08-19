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
            'storage_location' => 'DS8',
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (WarehouseConsumable $item): void {
            $current = (string) $item->current_stock;
            $location = $item->storage_location === 'Deltamas' ? 'stock_deltamas' : 'stock_ds8';
            $other = $location === 'stock_deltamas' ? 'stock_ds8' : 'stock_deltamas';

            if ((float) $item->{$location} + (float) $item->{$other} !== (float) $current) {
                $item->forceFill([$location => $current, $other => '0.000'])->saveQuietly();
            }
        });
    }
}
