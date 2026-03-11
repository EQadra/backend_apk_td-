<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Association;
use App\Models\User;
use Faker\Factory as Faker;

class AssociationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::role('association')->take(5)->get();

        foreach ($users as $user) {
            Association::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
