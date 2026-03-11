<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            // Se setean desde el seeder, pero deben existir en el factory
            'productable_id'   => null,
            'productable_type' => null,

            'name'        => $this->faker->word(),
            'price'       => $this->faker->randomFloat(2, 5, 500),
            'description' => $this->faker->sentence(),
            'image'       => null,
            'stock'       => $this->faker->numberBetween(0, 50),
        ];
    }
}
