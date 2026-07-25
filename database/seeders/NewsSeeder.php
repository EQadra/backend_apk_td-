<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\News;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;
use App\Models\Association;
use App\Models\User;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ Obtener un usuario para asignar como creador
        $defaultUser = User::first();
        
        if (!$defaultUser) {
            $this->command->error('❌ No hay usuarios en la base de datos. Ejecuta UserSeeder primero.');
            return;
        }

        $models = [
            Doctor::class,
            Lawyer::class,
            Shop::class,
            Association::class,
        ];

        foreach ($models as $model) {
            $items = $model::all();

            foreach ($items as $item) {
                // ✅ Obtener el usuario dueño del perfil
                $user = User::find($item->user_id);
                
                if (!$user) {
                    $this->command->warn("⚠️ No se encontró usuario para {$model} con ID {$item->id}, usando usuario por defecto");
                    $user = $defaultUser;
                }

                News::factory()->count(3)->create([
                    'user_id' => $user->id,           // ✅ Asignar usuario creador
                    'newable_id' => $item->id,
                    'newable_type' => $model,
                    'image' => $this->getRandomImage(),
                ]);
            }
        }

        // ✅ Crear noticias para usuarios sin perfil (usuarios normales)
        $usersWithoutProfile = User::whereDoesntHave('doctor')
            ->whereDoesntHave('lawyer')
            ->whereDoesntHave('shop')
            ->whereDoesntHave('association')
            ->get();

        foreach ($usersWithoutProfile as $user) {
            News::factory()->count(2)->create([
                'user_id' => $user->id,
                'newable_id' => null,
                'newable_type' => null,
                'image' => $this->getRandomImage(),
            ]);
        }

        $this->command->info('✅ Noticias creadas correctamente');
    }

    /**
     * Generar URL de imagen aleatoria para noticias
     */
    private function getRandomImage(): string
    {
        $images = [
            'https://picsum.photos/seed/' . rand(1, 1000) . '/800/400',
            'https://picsum.photos/seed/' . rand(1, 1000) . '/800/400',
            'https://picsum.photos/seed/' . rand(1, 1000) . '/800/400',
        ];
        
        return $images[array_rand($images)];
    }
}