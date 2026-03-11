<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Shop;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'name'        => $this->faker->company(),
            'description' => $this->faker->text(100), // evita error "data too long"
            'address'     => $this->faker->address(),
            'city'        => $this->faker->city(),
            'phone'       => $this->faker->phoneNumber(),
            'image'       => null, // o usa un pequeño fake
            'schedule'    => $this->faker->sentence(6),
            'user_id'     => null, // el seeder asigna el user
        ];
    }
}
