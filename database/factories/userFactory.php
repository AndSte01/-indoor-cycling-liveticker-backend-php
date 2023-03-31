<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as Faker;

class userFactory extends Factory
{
    //protected $model = user::class;

    public function definition(): array
    {
        $faker = Faker::create();

        return [
            'name' => $faker->word(),
        ];
    }
}
