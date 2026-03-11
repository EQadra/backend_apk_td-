<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Association; // 🔥 ESTA LÍNEA ES OBLIGATORIA

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Shops
        Shop::all()->each(function ($shop) {
            Product::factory(rand(3, 6))->create([
                'productable_id' => $shop->id,
                'productable_type' => Shop::class,
            ]);
        });

        // Associations
        Association::all()->each(function ($association) {
            Product::factory(rand(3, 6))->create([
                'productable_id' => $association->id,
                'productable_type' => Association::class,
            ]);
        });
    }
}
