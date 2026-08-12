<?php

namespace Database\Factories\Warehouse;

use App\Enums\Warehouse\WarehouseVerificationStatus;
use App\Models\Warehouse\WarehouseVerificationLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseVerificationLogFactory extends Factory
{
    protected $model = WarehouseVerificationLog::class;

    public function definition(): array
    {
        return [
            'scanned_code_hash' => hash('sha256', $this->faker->uuid()),
            'status' => WarehouseVerificationStatus::FAILED,
            'failure_reason' => 'Unknown code',
            'verified_at' => now(),
        ];
    }
}
