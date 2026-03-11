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
            'password' => Hash::make('password'),
        ])->assignRole('admin');

        // Associations
        User::factory()
            ->count(5)
            ->create()
            ->each(fn ($user) => $user->assignRole('association'));

        // Doctors
        User::factory()
            ->count(5)
            ->create()
            ->each(fn ($user) => $user->assignRole('doctor'));

        // Lawyers
        User::factory()
            ->count(5)
            ->create()
            ->each(fn ($user) => $user->assignRole('lawyer'));

        // Shops
        User::factory()
            ->count(5)
            ->create()
            ->each(fn ($user) => $user->assignRole('shop'));
    }
}
