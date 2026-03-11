<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            // 1️⃣ Seguridad / base
            RolePermissionSeeder::class,
            UserSeeder::class,

            // 2️⃣ Entidades principales (dependen de users)
            ShopSeeder::class,
            AssociationSeeder::class,
            DoctorSeeder::class,
            LawyerSeeder::class,

            // 3️⃣ Contenido dependiente
            ProductSeeder::class,
            ServiceSeeder::class,

            // 4️⃣ Contenido polimórfico / social
            PostSeeder::class,
            NewsSeeder::class,
            FeedbackSeeder::class,
            CommentSeeder::class,
        ]);
    }
}
