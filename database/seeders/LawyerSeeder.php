<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lawyer;
use App\Models\User;

class LawyerSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::role('lawyer')->take(5)->get();

        foreach ($users as $user) {
            Lawyer::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
