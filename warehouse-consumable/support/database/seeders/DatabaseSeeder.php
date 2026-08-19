<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $faker = Faker::create();
        foreach (range(1, 10) as $index) {
            DB::table('form_f_p_p_s')->insert([
                'id_fpp' => $faker->id_fpp,
                'mesin' => $faker->mesin,
                'section' => $faker->section,
                'lokasi' => $faker->lokasi,
                'kendala' => $faker->kendala,
                'status' => $faker->status,
            ]);
        }
    }
}
