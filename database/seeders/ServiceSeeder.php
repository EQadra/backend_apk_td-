<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([Doctor::class, Lawyer::class, Shop::class] as $model) {
            $model::all()->each(function ($item) use ($model) {
                Service::factory(rand(2, 5))->create([
                    'serviceable_id' => $item->id,
                    'serviceable_type' => $model,
                ]);
            });
        }
    }
}
