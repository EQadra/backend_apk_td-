<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\User;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::role('shop')->take(5)->get();

        foreach ($users as $user) {
            Shop::factory()->create([
                'user_id' => $user->id,
            ]);
        }
    }
}