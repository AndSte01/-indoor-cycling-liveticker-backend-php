<?php

namespace Database\Seeders;

use App\Models\competition;
use App\Models\user;
use Illuminate\Database\Seeder;

class CompetitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create a user for the foreign id constraint
        $user = user::factory()->create(); //creates 10 user

        competition::factory()->count(5)->create(['user_id' => $user->id]);
    }
}
