<?php

namespace Database\Seeders;


use App\Models\Feedback;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;
use App\Models\Association;
use Illuminate\Database\Seeder;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach ([Doctor::class, Lawyer::class, Shop::class, Association::class] as $model) {
            $model::all()->each(function ($item) use ($users, $model) {
                Feedback::factory(rand(2, 4))->create([
                    'user_id' => $users->random()->id,
                    'feedbackable_id' => $item->id,
                    'feedbackable_type' => $model,
                ]);
            });
        }
    }
}
