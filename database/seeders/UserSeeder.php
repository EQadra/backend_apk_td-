<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Shop;
use App\Models\Association;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // ADMIN
        // ==========================================
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=1',
        ])->assignRole('admin');

        // ==========================================
        // ASOCIACIÓN
        // ==========================================
        $association = User::create([
            'name' => 'Asociación Salud Para Todos',
            'email' => 'asociacion@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=2',
        ])->assignRole('association');

        Association::create([
            'user_id' => $association->id,
            'name' => 'Asociación Salud Para Todos',
            'description' => 'Asociación dedicada a la salud y bienestar comunitario',
            'city' => 'Lima',
            'address' => 'Av. Principal 123',
            'phone' => '987654321',
            'website' => 'https://saludparatodos.org',
        ]);

        // ==========================================
        // DOCTOR
        // ==========================================
        $doctor = User::create([
            'name' => 'Dr. Carlos Pérez',
            'email' => 'doctor@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=3',
        ])->assignRole('doctor');

        Doctor::create([
            'user_id' => $doctor->id,
            'first_name' => 'Carlos',
            'last_name' => 'Pérez',
            'description' => 'Cardiólogo con más de 15 años de experiencia',
            'degree' => 'Médico Cirujano',
            'specialty' => 'Cardiología',
            'graduation_code' => 'CMP-67648',
            'city' => 'Lima',
            'university' => 'Universidad Nacional Mayor de San Marcos',
            'rating' => 4.8,
            'schedule' => 'L-V 9:00-18:00',
        ]);

        // ==========================================
        // ABOGADO
        // ==========================================
        $lawyer = User::create([
            'name' => 'Abg. María González',
            'email' => 'abogado@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=4',
        ])->assignRole('lawyer');

        Lawyer::create([
            'user_id' => $lawyer->id,
            'first_name' => 'María',
            'last_name' => 'González',
            'description' => 'Especialista en derecho laboral y civil',
            'specialty' => 'Laboral',
            'license_code' => 'ABO959',
            'city' => 'Lima',
            'university' => 'Pontificia Universidad Católica del Perú',
        ]);

        // ==========================================
        // TIENDA
        // ==========================================
        $shop = User::create([
            'name' => 'Farmacia Central',
            'email' => 'tienda@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=5',
        ])->assignRole('shop');

        Shop::create([
            'user_id' => $shop->id,
            'name' => 'Farmacia Central',
            'description' => 'Tu farmacia de confianza con los mejores precios',
            'city' => 'Lima',
            'address' => 'Av. Principal 456',
            'phone' => '987654322',
            'schedule' => 'L-S 8:00-22:00',
        ]);

        // ==========================================
        // USUARIOS ADICIONALES CON AVATARES
        // ==========================================
        
        // Lista de avatares para usuarios aleatorios
        $avatars = [
            'https://i.pravatar.cc/300?img=6',
            'https://i.pravatar.cc/300?img=7',
            'https://i.pravatar.cc/300?img=8',
            'https://i.pravatar.cc/300?img=9',
            'https://i.pravatar.cc/300?img=10',
            'https://i.pravatar.cc/300?img=11',
            'https://i.pravatar.cc/300?img=12',
            'https://i.pravatar.cc/300?img=13',
            'https://i.pravatar.cc/300?img=14',
            'https://i.pravatar.cc/300?img=15',
            'https://i.pravatar.cc/300?img=16',
            'https://i.pravatar.cc/300?img=17',
            'https://i.pravatar.cc/300?img=18',
            'https://i.pravatar.cc/300?img=19',
            'https://i.pravatar.cc/300?img=20',
            'https://i.pravatar.cc/300?img=21',
            'https://i.pravatar.cc/300?img=22',
            'https://i.pravatar.cc/300?img=23',
            'https://i.pravatar.cc/300?img=24',
            'https://i.pravatar.cc/300?img=25',
        ];

        // Associations adicionales
        User::factory()
            ->count(4)
            ->create()
            ->each(function ($user) use (&$avatars) {
                $user->assignRole('association');
                $user->avatar = $avatars[array_rand($avatars)];
                $user->save();
                
                Association::create([
                    'user_id' => $user->id,
                    'name' => $user->name . ' Asociación',
                    'description' => 'Asociación dedicada al bienestar social',
                    'city' => fake()->city(),
                    'address' => fake()->address(),
                    'phone' => fake()->phoneNumber(),
                ]);
            });

        // Doctors adicionales
        User::factory()
            ->count(4)
            ->create()
            ->each(function ($user) use (&$avatars) {
                $user->assignRole('doctor');
                $user->avatar = $avatars[array_rand($avatars)];
                $user->save();
                
                Doctor::create([
                    'user_id' => $user->id,
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'description' => fake()->sentence(10),
                    'degree' => 'Médico Cirujano',
                    'specialty' => fake()->randomElement(['Cardiología', 'Pediatría', 'Medicina General', 'Neurología', 'Dermatología']),
                    'graduation_code' => 'CMP-' . fake()->numberBetween(10000, 99999),
                    'city' => fake()->city(),
                    'university' => fake()->randomElement(['UNMSM', 'PUCP', 'UPCH', 'UNI']),
                    'rating' => fake()->randomFloat(1, 3, 5),
                    'schedule' => 'L-V 9:00-18:00',
                ]);
            });

        // Lawyers adicionales
        User::factory()
            ->count(4)
            ->create()
            ->each(function ($user) use (&$avatars) {
                $user->assignRole('lawyer');
                $user->avatar = $avatars[array_rand($avatars)];
                $user->save();
                
                Lawyer::create([
                    'user_id' => $user->id,
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'description' => fake()->sentence(10),
                    'specialty' => fake()->randomElement(['Laboral', 'Civil', 'Penal', 'Familia', 'Comercial']),
                    'license_code' => 'ABO' . fake()->numberBetween(100, 999),
                    'city' => fake()->city(),
                    'university' => fake()->randomElement(['UNMSM', 'PUCP', 'UPCH']),
                ]);
            });

        // Shops adicionales
        User::factory()
            ->count(4)
            ->create()
            ->each(function ($user) use (&$avatars) {
                $user->assignRole('shop');
                $user->avatar = $avatars[array_rand($avatars)];
                $user->save();
                
                Shop::create([
                    'user_id' => $user->id,
                    'name' => $user->name . ' Store',
                    'description' => fake()->sentence(10),
                    'city' => fake()->city(),
                    'address' => fake()->address(),
                    'phone' => fake()->phoneNumber(),
                    'schedule' => 'L-S 8:00-20:00',
                ]);
            });
    }
}