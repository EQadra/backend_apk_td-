<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 🔥 PRIMERO: Crear roles y permisos
        $this->call(RoleSeeder::class);
        
        // 🔥 SEGUNDO: Crear usuarios
        $this->call(UserSeeder::class);
        
        // 🔥 TERCERO: Datos adicionales
        $this->call([
            DoctorSeeder::class,
            LawyerSeeder::class,
            AssociationSeeder::class,
            ShopSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            FeedbackSeeder::class,
            ProductSeeder::class,
            ServiceSeeder::class,
            NewsSeeder::class,
        ]);
    }
}