<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear roles
        $roles = [
            'admin',
            'doctor',
            'lawyer',
            'association',
            'shop',
            'user', // ✅ Rol para usuarios normales
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'api']);
        }

        // Crear permisos básicos
        $permissions = [
            'view posts',
            'create posts',
            'edit posts',
            'delete posts',
            'view users',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        // Asignar permisos al admin
        $adminRole = Role::findByName('admin', 'api');
        $adminRole->givePermissionTo(Permission::all());
    }
}