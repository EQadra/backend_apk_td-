<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Faker\Factory as FakerFactory; // 👈 Importar Faker

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear una instancia de Faker
        $faker = FakerFactory::create('es_ES'); // 'es_ES' para datos en español

        // Verificar que existen usuarios con rol shop
        $shopUsers = User::role('shop')->get();

        if ($shopUsers->isEmpty()) {
            Log::warning('No se encontraron usuarios con rol "shop". Creando usuarios de prueba...');
            
            $shopUsers = collect();
            
            for ($i = 1; $i <= 5; $i++) {
                $user = User::factory()->create([
                    'name' => "Tienda {$i}",
                    'email' => "tienda{$i}@demo.com",
                ]);
                $user->assignRole('shop');
                $shopUsers->push($user);
            }
            
            Log::info('Se crearon ' . $shopUsers->count() . ' usuarios con rol "shop"');
        }

        // Crear una tienda para cada usuario shop
        foreach ($shopUsers as $user) {
            $existingShop = Shop::where('user_id', $user->id)->exists();
            
            if (!$existingShop) {
                Shop::factory()->create([
                    'user_id' => $user->id,
                    'name' => "{$user->name} Store",
                    'description' => $this->getRandomDescription($faker),
                    'city' => $this->getRandomCity($faker),
                    'schedule' => $this->getRandomSchedule($faker),
                ]);
            } else {
                Log::info("El usuario {$user->name} ya tiene una tienda. Saltando...");
            }
        }

        // Crear algunas tiendas adicionales
        $this->createAdditionalShops($faker);
    }

    /**
     * Crear tiendas adicionales con datos variados
     */
    private function createAdditionalShops($faker): void
    {
        $shopUsers = User::role('shop')->get();
        
        if ($shopUsers->isEmpty()) {
            return;
        }

        $specialties = [
            'Farmacia y Salud',
            'Tecnología y Electrónica',
            'Ropa y Moda',
            'Alimentos y Bebidas',
            'Hogar y Decoración'
        ];

        foreach ($specialties as $index => $specialty) {
            $user = $shopUsers->get($index % $shopUsers->count());
            
            if ($user && Shop::where('name', 'LIKE', "%{$specialty}%")->count() < 2) {
                Shop::factory()->create([
                    'user_id' => $user->id,
                    'name' => "Tienda {$specialty}",
                    'description' => "Somos una tienda especializada en {$specialty}. Ofrecemos productos de alta calidad con los mejores precios del mercado.",
                    'city' => $this->getRandomCity($faker),
                    'schedule' => $this->getRandomSchedule($faker),
                ]);
            }
        }
    }

    /**
     * Obtener una descripción aleatoria
     */
    private function getRandomDescription($faker): string
    {
        $descriptions = [
            'Ofrecemos productos de alta calidad con atención personalizada. Nuestro compromiso es brindar el mejor servicio a nuestros clientes.',
            'Especialistas en brindar soluciones a medida. Contamos con más de 10 años de experiencia en el mercado.',
            'La mejor opción para encontrar lo que necesitas. Variedad, calidad y precios competitivos te esperan en nuestra tienda.',
            'Comprometidos con la satisfacción de nuestros clientes. Productos seleccionados y atención de primera.',
            'Innovación y tradición en un mismo lugar. Descubre nuestra amplia gama de productos y servicios.',
            'Donde la calidad y el buen servicio se unen. Tu tienda de confianza en el corazón de la ciudad.'
        ];

        return $faker->randomElement($descriptions);
    }

    /**
     * Obtener una ciudad aleatoria
     */
    private function getRandomCity($faker): string
    {
        $cities = [
            'Lima', 'Arequipa', 'Cusco', 'Trujillo', 'Piura',
            'Chiclayo', 'Huancayo', 'Iquitos', 'Tacna', 'Ayacucho',
            'Puno', 'Cajamarca', 'Ica', 'Moquegua', 'Sullana'
        ];

        return $faker->randomElement($cities);
    }

    /**
     * Obtener un horario aleatorio
     */
    private function getRandomSchedule($faker): string
    {
        $schedules = [
            'Lunes a Sábado: 9:00 AM - 8:00 PM',
            'Lunes a Viernes: 8:00 AM - 6:00 PM',
            'Lunes a Domingo: 10:00 AM - 10:00 PM',
            'Lunes a Sábado: 7:00 AM - 9:00 PM',
            'Martes a Domingo: 9:00 AM - 7:00 PM',
            'Lunes a Viernes: 9:00 AM - 5:00 PM, Sábado: 10:00 AM - 2:00 PM'
        ];

        return $faker->randomElement($schedules);
    }
}