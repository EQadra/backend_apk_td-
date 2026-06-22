<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Shop;
use App\Models\User;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        // Lista de categorías para tiendas
        $categories = [
            'Farmacia', 'Tienda de Ropa', 'Supermercado', 
            'Librería', 'Electrónica', 'Joyería', 
            'Deportes', 'Juguetes', 'Muebles', 'Tecnología'
        ];

        // Horarios de ejemplo
        $schedules = [
            'Lun-Sab 9:00-20:00',
            'Lun-Vie 8:00-18:00',
            'Lun-Dom 10:00-22:00',
            'Lun-Sab 7:00-21:00',
            'Lun-Vie 9:00-19:00, Sab 10:00-15:00'
        ];

        return [
            'name'        => $this->faker->company(),
            'description' => $this->faker->paragraphs(3, true), // Texto más largo pero manejable
            'address'     => $this->faker->streetAddress() . ', ' . $this->faker->city() . ', ' . $this->faker->stateAbbr() . ' ' . $this->faker->postcode(),
            'city'        => $this->faker->city(),
            'phone'       => $this->faker->phoneNumber(),
            'image'       => null, // Se puede asignar después
            'schedule'    => $this->faker->randomElement($schedules),
            'user_id'     => User::role('shop')->inRandomOrder()->first()?->id ?? User::factory()->create(['name' => 'Shop User'])->id,
        ];
    }

    /**
     * Configurar la tienda con un usuario específico
     */
    public function forUser($userId): self
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    /**
     * Crear una tienda con categoría específica
     */
    public function withCategory(string $category): self
    {
        return $this->state(function (array $attributes) use ($category) {
            return [
                'category' => $category,
            ];
        });
    }

    /**
     * Crear una tienda con imagen
     */
    public function withImage(string $imageUrl): self
    {
        return $this->state(function (array $attributes) use ($imageUrl) {
            return [
                'image' => $imageUrl,
            ];
        });
    }
}