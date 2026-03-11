<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\News;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $models = [
            Doctor::class,
            Lawyer::class,
            Shop::class,
        ];

        foreach ($models as $model) {
            $items = $model::all();

            foreach ($items as $item) {
                News::factory()->count(3)->create([
                    'newable_id' => $item->id,
                    'newable_type' => $model,
                ]);
            }
        }
    }
}
