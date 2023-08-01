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

        // timestamp used to make sure the competition ends before it starts
        $date_timestamp = rand();

        return [
            'name' => $faker->text(10),
            'location' => $faker->text(8),
            'date_start' => $faker->date($date_timestamp),
            'date_end' => $faker->date($date_timestamp + rand(0, 1000000)),
            'feature_set' => 0,
            'areas' => rand(1, 3),
            'live' => rand(0, 1)
        ];
    }
}
