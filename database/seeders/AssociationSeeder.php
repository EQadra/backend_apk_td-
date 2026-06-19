<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Association;
use App\Models\User;

class AssociationSeeder extends Seeder
{
    public function run(): void
    {
        User::role('association')->each(function ($user) {
            Association::factory()->create([
                'user_id' => $user->id,
                'name' => $user->name, // Usar el nombre del usuario
            ]);
        });
    }
}