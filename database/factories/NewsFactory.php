<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // ✅ Asignar usuario automáticamente
            'titulo' => $this->faker->sentence(6),
            'descripcion' => $this->faker->paragraph(3),
            'url' => $this->faker->url(),
            'image' => 'https://picsum.photos/seed/' . $this->faker->numberBetween(1, 1000) . '/800/400',
            'fecha_publicacion' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'newable_type' => null,
            'newable_id' => null,
        ];
    }
}