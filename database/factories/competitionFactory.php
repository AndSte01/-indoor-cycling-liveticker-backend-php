<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as Faker;

class competitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = Faker::create();

        return [
            'name' => $faker->text(10),
            'location' => $faker->text(8),
            'date' => $faker->date(),
            'feature_set' => 0,
            'areas' => rand(0, 3),
            'live' => rand(0, 1)
        ];
    }
}
