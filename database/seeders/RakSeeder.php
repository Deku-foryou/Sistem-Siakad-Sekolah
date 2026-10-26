<?php

namespace Database\Seeders;

use App\Models\Rak;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class RakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 101; $i < 1000; $i++) {

            $data = [

                'idrak' => 'id'.$i,
                'rak' => $faker->name,

            ];

            Rak::create($data);
        }

    }
}
