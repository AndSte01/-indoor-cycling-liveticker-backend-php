<?php

namespace Database\Seeders;

use App\Models\user;
use Illuminate\Database\Seeder;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = new user(['name' => 'test']);
        $user->setPassword('test');
        // $user->save(); not required user is saved inside of 'setPassword'-method
    }
}
