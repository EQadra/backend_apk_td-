<?php

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'titulo' => $this->faker->sentence(),
            'descripcion' => $this->faker->paragraph(4),
            'url' => $this->faker->url(),
            'fecha_publicacion' => $this->faker->dateTimeBetween('-1 year', 'now'),

            // Campos polimórficos se llenan en el seeder
            'newable_id' => null,
            'newable_type' => null,
        ];
    }
}
