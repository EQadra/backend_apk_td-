<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('123456'),
        ])->assignRole('admin');

        // ==========================================
        // USUARIOS ESPECÍFICOS CON NOMBRES CLAROS
        // ==========================================
        
        // Association
        User::create([
            'name' => 'Asociación Salud Para Todos',
            'email' => 'asociacion@demo.com',
            'password' => Hash::make('123456'),
        ])->assignRole('association');

        // Doctor
        User::create([
            'name' => 'Dr. Carlos Pérez',
            'email' => 'doctor@demo.com',
            'password' => Hash::make('123456'),
        ])->assignRole('doctor');

        // Lawyer
        User::create([
            'name' => 'Abg. María González',
            'email' => 'abogado@demo.com',
            'password' => Hash::make('123456'),
        ])->assignRole('lawyer');

        // Shop
        User::create([
            'name' => 'Farmacia Central',
            'email' => 'tienda@demo.com',
            'password' => Hash::make('123456'),
        ])->assignRole('shop');

        // ==========================================
        // USUARIOS ADICIONALES (4 por cada rol)
        // ==========================================

        // Associations (4 adicionales = total 5)
        User::factory()
            ->count(4)
            ->create()
            ->each(fn ($user) => $user->assignRole('association'));

        // Doctors (4 adicionales = total 5)
        User::factory()
            ->count(4)
            ->create()
            ->each(fn ($user) => $user->assignRole('doctor'));

        // Lawyers (4 adicionales = total 5)
        User::factory()
            ->count(4)
            ->create()
            ->each(fn ($user) => $user->assignRole('lawyer'));

        // Shops (4 adicionales = total 5)
        User::factory()
            ->count(4)
            ->create()
            ->each(fn ($user) => $user->assignRole('shop'));
    }
}