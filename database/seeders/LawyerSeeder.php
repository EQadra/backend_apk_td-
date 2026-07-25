<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lawyer;
use App\Models\User;

class LawyerSeeder extends Seeder
{
    public function run(): void
    {
        // Datos de abogados adicionales con teléfonos
        $lawyersData = [
            [
                'user_id' => 4, // María González (ya existe)
                'first_name' => 'María',
                'last_name' => 'González',
                'description' => 'Especialista en derecho laboral y civil',
                'specialty' => 'Laboral',
                'license_code' => 'ABO959',
                'city' => 'Lima',
                'university' => 'Pontificia Universidad Católica del Perú',
                'phone' => '988777666',
                'office_phone' => '0123456789',
            ],
            [
                'user_id' => 14, // Dr. Chelsey Nitzsche II
                'first_name' => 'Chelsey',
                'last_name' => 'Nitzsche',
                'description' => 'Especialista en derecho comercial y corporativo',
                'specialty' => 'Comercial',
                'license_code' => 'ABO929',
                'city' => 'Lima',
                'university' => 'Universidad de Lima',
                'phone' => '987654321',
                'office_phone' => '014567890',
            ],
            [
                'user_id' => 15, // Mrs. Carley Collins
                'first_name' => 'Carley',
                'last_name' => 'Collins',
                'description' => 'Especialista en derecho civil con más de 10 años de experiencia',
                'specialty' => 'Civil',
                'license_code' => 'ABO494',
                'city' => 'Arequipa',
                'university' => 'Universidad Católica San Pablo',
                'phone' => '976543210',
                'office_phone' => '054678901',
            ],
            [
                'user_id' => 16, // Prof. Domenic Braun
                'first_name' => 'Domenic',
                'last_name' => 'Braun',
                'description' => 'Abogado especialista en derecho familiar',
                'specialty' => 'Familia',
                'license_code' => 'ABO875',
                'city' => 'Cusco',
                'university' => 'Universidad Andina del Cusco',
                'phone' => '965432109',
                'office_phone' => '084567890',
            ],
            [
                'user_id' => 17, // Violette Padberg
                'first_name' => 'Violette',
                'last_name' => 'Padberg',
                'description' => 'Especialista en derecho laboral y seguridad social',
                'specialty' => 'Laboral',
                'license_code' => 'ABO465',
                'city' => 'Trujillo',
                'university' => 'Universidad Nacional de Trujillo',
                'phone' => '954321098',
                'office_phone' => '044567890',
            ],
        ];

        foreach ($lawyersData as $data) {
            $user = User::find($data['user_id']);
            
            if ($user && $user->hasRole('lawyer')) {
                // Verificar si ya existe un lawyer para este usuario
                $existing = Lawyer::where('user_id', $data['user_id'])->first();
                
                if (!$existing) {
                    Lawyer::create($data);
                } else {
                    // Actualizar si ya existe
                    $existing->update($data);
                }
            }
        }
    }
}