<?php

namespace Database\Factories;

use App\Enums\KnowledgeManagement\KmReadStatus;
use App\Models\KmPengajuan;
use App\Models\KmTransaksi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KmTransaksi>
 */
class KmTransaksiFactory extends Factory
{
    protected $model = KmTransaksi::class;

    public function definition(): array
    {
        return [
            'id_km_pengajuan' => KmPengajuan::factory()->published(),
            'id_user' => User::factory(),
            'poin' => null,
            'level' => 0,
            'status' => KmReadStatus::READING->value,
            'modified_by' => fn (array $attributes) => $attributes['id_user'],
            'completed_at' => null,
            'points_awarded_at' => null,
        ];
    }

    public function reading(): static
    {
        return $this->state(fn (): array => [
            'status' => KmReadStatus::READING->value,
            'completed_at' => null,
            'points_awarded_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (): array {
            $completedAt = now();

            return [
                'status' => KmReadStatus::COMPLETED->value,
                'poin' => fake()->numberBetween(1, 100),
                'completed_at' => $completedAt,
                'points_awarded_at' => $completedAt,
            ];
        });
    }
}
