<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\History;
use App\Models\User;
use App\Models\Product;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\Shop;

class HistorySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::take(5)->get();

        foreach ($users as $user) {

            // Productos
            foreach (Product::inRandomOrder()->take(5)->get() as $product) {

                History::create([
                    'user_id' => $user->id,
                    'historyable_id' => $product->id,
                    'historyable_type' => Product::class,
                    'views' => rand(1, 20),
                    'last_viewed_at' => now()->subDays(rand(0, 30)),
                ]);
            }

            // Servicios
            foreach (Service::inRandomOrder()->take(3)->get() as $service) {

                History::create([
                    'user_id' => $user->id,
                    'historyable_id' => $service->id,
                    'historyable_type' => Service::class,
                    'views' => rand(1, 15),
                    'last_viewed_at' => now()->subDays(rand(0, 30)),
                ]);
            }

            // Doctores
            foreach (Doctor::inRandomOrder()->take(2)->get() as $doctor) {

                History::create([
                    'user_id' => $user->id,
                    'historyable_id' => $doctor->id,
                    'historyable_type' => Doctor::class,
                    'views' => rand(1, 10),
                    'last_viewed_at' => now()->subDays(rand(0, 30)),
                ]);
            }

            // Tiendas
            foreach (Shop::inRandomOrder()->take(2)->get() as $shop) {

                History::create([
                    'user_id' => $user->id,
                    'historyable_id' => $shop->id,
                    'historyable_type' => Shop::class,
                    'views' => rand(1, 10),
                    'last_viewed_at' => now()->subDays(rand(0, 30)),
                ]);
            }
        }
    }
}