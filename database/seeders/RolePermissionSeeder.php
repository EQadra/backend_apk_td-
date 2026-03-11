<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ⚠️ Limpia primero
        Role::query()->delete();
        Permission::query()->delete();

        $roles = [
            'admin',
            'editor',
            'usuario',
            'doctor',
            'lawyer',
            'shop',
            'association',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'api',
            ]);
        }

        $permissions = [
            'create posts',
            'edit posts',
            'delete posts',
            'view posts',
            'comment posts',
            'manage users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $admin = Role::where('name', 'admin')
            ->where('guard_name', 'api')
            ->firstOrFail();

        $admin->givePermissionTo(
            Permission::where('guard_name', 'api')->get()
        );
    }
}
