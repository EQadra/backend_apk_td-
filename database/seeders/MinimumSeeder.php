<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class MinimumSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. LIMPIAR CACHÉ DE ROLES Y PERMISOS
        // ==========================================
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ==========================================
        // 2. CREAR ROLES (SOLO LOS QUE USAS)
        // ==========================================
        $roles = [
            'admin',
            'doctor',
            'lawyer',
            'association',
            'shop',
            'user',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'api'
            ]);
        }

        $this->command->info('✅ Roles creados: ' . implode(', ', $roles));

        // ==========================================
        // 3. CREAR PERMISOS BÁSICOS
        // ==========================================
        $permissions = [
            'view_posts',
            'create_posts',
            'edit_posts',
            'delete_posts',
            'view_users',
            'manage_users',
            'manage_roles',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'api'
            ]);
        }

        $this->command->info('✅ Permisos creados: ' . implode(', ', $permissions));

        // ==========================================
        // 4. ASIGNAR TODOS LOS PERMISOS AL ADMIN
        // ==========================================
        $adminRole = Role::where('name', 'admin')
            ->where('guard_name', 'api')
            ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo(
                Permission::where('guard_name', 'api')->get()
            );
            $this->command->info('✅ Permisos asignados al rol admin');
        }

        // ==========================================
        // 5. CREAR UN SOLO USUARIO ADMIN
        // ==========================================
        $adminEmail = 'admin@tudealer.app';

        // Verificar si ya existe
        if (!User::where('email', $adminEmail)->exists()) {
            $admin = User::create([
                'name' => 'Administrador',
                'email' => $adminEmail,
                'password' => Hash::make('Admin123!'),
                'phone' => '999888777',
                'dni' => '12345678',
                'address' => 'Av. Principal 123, San Isidro',
                'city' => 'Lima',
                'avatar' => 'https://i.pravatar.cc/300?img=1',
            ]);

            $admin->assignRole('admin');

            $this->command->info('✅ Usuario admin creado:');
            $this->command->info('   📧 Email: admin@tudealer.app');
            $this->command->info('   🔑 Password: Admin123!');
        } else {
            $this->command->warn('⚠️  El usuario admin ya existe: ' . $adminEmail);
        }

        // ==========================================
        // 6. CREAR UN USUARIO NORMAL DE PRUEBA (OPCIONAL)
        // ==========================================
        // Comenta esto si no quieres usuario de prueba
        $userEmail = 'usuario@tudealer.app';
        if (!User::where('email', $userEmail)->exists()) {
            $user = User::create([
                'name' => 'Usuario Prueba',
                'email' => $userEmail,
                'password' => Hash::make('User123!'),
                'phone' => '966555444',
                'dni' => '67890123',
                'address' => 'Av. Los Pinos 123, San Miguel',
                'city' => 'Lima',
                'avatar' => 'https://i.pravatar.cc/300?img=2',
            ]);

            $user->assignRole('user');

            $this->command->info('✅ Usuario de prueba creado:');
            $this->command->info('   📧 Email: usuario@tudealer.app');
            $this->command->info('   🔑 Password: User123!');
        }

        $this->command->info('🎉 ¡Seeders completados exitosamente!');
    }
}