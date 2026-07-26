<?php

namespace Database\Factories;

use App\Models\KmTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KmTag>
 */
class KmTagFactory extends Factory
{
    protected $model = KmTag::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
