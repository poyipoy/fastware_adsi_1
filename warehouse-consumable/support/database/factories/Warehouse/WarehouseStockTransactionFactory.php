<?php

namespace Database\Factories\Warehouse;

use App\Enums\Warehouse\WarehouseItemCondition;
use App\Enums\Warehouse\WarehouseTransactionType;
use App\Models\Warehouse\WarehouseStockTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseStockTransactionFactory extends Factory
{
    protected $model = WarehouseStockTransaction::class;

    public function definition(): array
    {
        return [
            'transaction_number' => 'WH-'.$this->faker->unique()->numerify('########-########'),
            'idempotency_key' => $this->faker->uuid(),
            'transaction_type' => WarehouseTransactionType::IN,
            'item_condition' => WarehouseItemCondition::NEW,
            'to_location' => 'DS8',
            'quantity' => '1.000',
            'stock_before' => '0.000',
            'stock_after' => '1.000',
            'verified_user_name' => 'Warehouse Test User',
            'verified_user_npk' => '1000',
            'verified_user_section' => 'Testing',
            'transaction_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (WarehouseStockTransaction $transaction): void {
            if ($transaction->transaction_type === WarehouseTransactionType::OUT) {
                $transaction->from_location ??= 'DS8';
                $transaction->to_location = null;
            }
        });
    }
}
