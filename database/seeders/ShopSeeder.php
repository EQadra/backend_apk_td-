<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\User;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        User::role('shop')->each(function ($user) {
            Shop::factory()->create([
                'user_id' => $user->id,
                'name' => $user->name, // Usar el nombre del usuario
            ]);
        });
    }
}