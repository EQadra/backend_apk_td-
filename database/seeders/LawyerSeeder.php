<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lawyer;
use App\Models\User;

class LawyerSeeder extends Seeder
{
    public function run(): void
    {
        User::role('lawyer')->each(function ($user) {
            $nameParts = explode(' ', $user->name, 2);
            $firstName = $nameParts[0] ?? 'Abogado';
            $lastName = $nameParts[1] ?? 'Default';

            Lawyer::factory()->create([
                'user_id' => $user->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);
        });
    }
}