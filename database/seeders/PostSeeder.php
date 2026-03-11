<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;
use App\Models\Association;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $models = [Doctor::class, Lawyer::class, Shop::class, Association::class];

        foreach ($models as $modelClass) {
            foreach ($modelClass::all() as $item) {
                Post::factory()->count(3)->create([
                    'postable_id' => $item->id,
                    'postable_type' => $modelClass,
                ]);
            }
        }
    }
}
