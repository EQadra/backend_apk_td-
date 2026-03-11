<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;
use App\Models\Association;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        // Elegir un modelo aleatorio como dueño del post
        $postableModels = [
            Doctor::class,
            Lawyer::class,
            Shop::class,
            Association::class,
        ];

        $postableType = $this->faker->randomElement($postableModels);
        $postable = $postableType::inRandomOrder()->first();

        return [
            'user_id' => User::inRandomOrder()->first()->id ?? User::factory(),
            'postable_id' => $postable->id ?? null,
            'postable_type' => $postableType,
            'title'    => $this->faker->sentence(),
            'content'  => $this->faker->paragraph(),
            'category' => $this->faker->randomElement(['doctor', 'lawyer', 'shop', 'association', 'general']),
            'image'    => null,
        ];
    }
}
