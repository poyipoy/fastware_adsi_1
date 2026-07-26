<?php

namespace Database\Factories;

use App\Models\KmKategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KmKategori>
 */
class KmKategoriFactory extends Factory
{
    protected $model = KmKategori::class;

    public function definition(): array
    {
        return [
            'nama_kategori' => fake()->unique()->words(2, true),
            'poin_kategori' => fake()->numberBetween(1, 100),
        ];
    }
}
