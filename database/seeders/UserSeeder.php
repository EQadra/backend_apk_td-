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
        // ✅ Verificar que el rol user existe antes de usarlo
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // ==========================================
        // ADMIN
        // ==========================================
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=1',
            'phone' => '999888777',
            'dni' => '12345678',
            'address' => 'Av. Principal 123, San Isidro',
            'city' => 'Lima',
        ])->assignRole('admin');

        // ==========================================
        // ASOCIACIÓN
        // ==========================================
        $association = User::create([
            'name' => 'Asociación Salud Para Todos',
            'email' => 'asociacion@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=2',
            'phone' => '988777666',
            'dni' => '23456789',
            'address' => 'Av. Principal 123, Miraflores',
            'city' => 'Lima',
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
            'phone' => '999888777',
            'dni' => '34567890',
            'address' => 'Av. Javier Prado 456, San Isidro',
            'city' => 'Lima',
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
            'phone' => '999888777',
            'emergency_phone' => '999111222',
            'clinic_phone' => '0123456789',
        ]);

        // ==========================================
        // ABOGADO
        // ==========================================
        $lawyer = User::create([
            'name' => 'Abg. María González',
            'email' => 'abogado@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=4',
            'phone' => '988777666',
            'dni' => '45678901',
            'address' => 'Calle Los Álamos 789, Surco',
            'city' => 'Lima',
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
            'phone' => '988777666',
            'office_phone' => '0123456789',
        ]);

        // ==========================================
        // TIENDA
        // ==========================================
        $shop = User::create([
            'name' => 'Farmacia Central',
            'email' => 'tienda@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=5',
            'phone' => '977666555',
            'dni' => '56789012',
            'address' => 'Av. Grau 234, Barranco',
            'city' => 'Lima',
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
        // USUARIO NORMAL
        // ==========================================
        $user = User::create([
            'name' => 'Usuario Normal',
            'email' => 'usuario@demo.com',
            'password' => Hash::make('123456'),
            'avatar' => 'https://i.pravatar.cc/300?img=6',
            'phone' => '966555444',
            'dni' => '67890123',
            'address' => 'Av. Los Pinos 123, San Miguel',
            'city' => 'Lima',
        ])->assignRole($userRole); // ✅ Usar la variable en lugar del string

        // ==========================================
        // GENERAR USUARIOS ADICIONALES
        // ==========================================
        $this->generateAdditionalUsers($userRole);
    }

    /**
     * Generar usuarios adicionales con datos realistas
     */
    private function generateAdditionalUsers($userRole): void
    {
        $avatars = $this->getAvatarList();
        $avatarIndex = 7;

        // ==========================================
        // USUARIOS NORMALES ADICIONALES
        // ==========================================
        $usersData = [
            [
                'name' => 'Juan Pérez',
                'email' => 'juan.perez@email.com',
                'phone' => '955444333',
                'dni' => '78901234',
                'address' => 'Av. Arequipa 456, San Isidro',
                'city' => 'Arequipa',
            ],
            [
                'name' => 'Ana Gómez',
                'email' => 'ana.gomez@email.com',
                'phone' => '944333222',
                'dni' => '89012345',
                'address' => 'Calle Real 789, Cusco',
                'city' => 'Cusco',
            ],
            [
                'name' => 'Luis Torres',
                'email' => 'luis.torres@email.com',
                'phone' => '933222111',
                'dni' => '90123456',
                'address' => 'Av. Los Héroes 234, Trujillo',
                'city' => 'Trujillo',
            ],
            [
                'name' => 'María Fernanda Castro',
                'email' => 'maria.castro@email.com',
                'phone' => '922111000',
                'dni' => '01234567',
                'address' => 'Calle Los Álamos 567, Piura',
                'city' => 'Piura',
            ],
        ];

        foreach ($usersData as $data) {
            User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('123456'),
                'avatar' => $avatars[$avatarIndex++ % count($avatars)],
                'phone' => $data['phone'],
                'dni' => $data['dni'],
                'address' => $data['address'],
                'city' => $data['city'],
            ])->assignRole($userRole);
        }

        // ==========================================
        // ASOCIACIONES ADICIONALES
        // ==========================================
        $associationsData = [
            [
                'name' => 'Asociación Vida y Salud',
                'email' => 'vidaysalud@asociacion.com',
                'description' => 'Asociación dedicada a promover la salud preventiva',
                'city' => 'Arequipa',
                'phone' => '988123456',
            ],
            [
                'name' => 'Asociación Desarrollo Comunitario',
                'email' => 'desarrollo@comunidad.org',
                'description' => 'Trabajando por el desarrollo de comunidades vulnerables',
                'city' => 'Cusco',
                'phone' => '977234567',
            ],
            [
                'name' => 'Asociación Educativa Horizonte',
                'email' => 'horizonte@educacion.org',
                'description' => 'Fomentando la educación en zonas rurales',
                'city' => 'Trujillo',
                'phone' => '966345678',
            ],
        ];

        foreach ($associationsData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('123456'),
                'avatar' => $avatars[$avatarIndex++ % count($avatars)],
                'phone' => $data['phone'],
                'dni' => '1' . rand(10000000, 99999999),
                'address' => $this->generateAddress(),
                'city' => $data['city'],
            ])->assignRole('association');

            Association::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'city' => $data['city'],
                'address' => $this->generateAddress(),
                'phone' => $data['phone'],
                'website' => $this->generateWebsite($data['name']),
            ]);
        }

        // ==========================================
        // DOCTORES ADICIONALES
        // ==========================================
        $doctorsData = [
            [
                'name' => 'Dra. Ana Martínez',
                'email' => 'ana.martinez@doctor.com',
                'first_name' => 'Ana',
                'last_name' => 'Martínez',
                'specialty' => 'Pediatría',
                'university' => 'Universidad Peruana Cayetano Heredia',
                'degree' => 'Médico Cirujano',
                'phone' => '977123456',
                'emergency_phone' => '977654321',
                'clinic_phone' => '014567890',
            ],
            [
                'name' => 'Dr. Luis Fernández',
                'email' => 'luis.fernandez@doctor.com',
                'first_name' => 'Luis',
                'last_name' => 'Fernández',
                'specialty' => 'Neurología',
                'university' => 'Universidad Nacional Mayor de San Marcos',
                'degree' => 'Médico Cirujano',
                'phone' => '966789012',
                'emergency_phone' => '966890123',
                'clinic_phone' => '015678901',
            ],
            [
                'name' => 'Dra. Patricia Soto',
                'email' => 'patricia.soto@doctor.com',
                'first_name' => 'Patricia',
                'last_name' => 'Soto',
                'specialty' => 'Dermatología',
                'university' => 'Universidad Privada San Juan Bautista',
                'degree' => 'Médico Cirujano',
                'phone' => '955567890',
                'emergency_phone' => '955678901',
                'clinic_phone' => '016789012',
            ],
            [
                'name' => 'Dr. Roberto Chávez',
                'email' => 'roberto.chavez@doctor.com',
                'first_name' => 'Roberto',
                'last_name' => 'Chávez',
                'specialty' => 'Medicina General',
                'university' => 'Universidad Nacional de San Agustín',
                'degree' => 'Médico Cirujano',
                'phone' => '944234567',
                'emergency_phone' => '944345678',
                'clinic_phone' => '017890123',
            ],
        ];

        foreach ($doctorsData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('123456'),
                'avatar' => $avatars[$avatarIndex++ % count($avatars)],
                'phone' => $data['phone'],
                'dni' => '2' . rand(10000000, 99999999),
                'address' => $this->generateAddress(),
                'city' => $this->getRandomCity(),
            ])->assignRole('doctor');

            Doctor::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'description' => $this->generateDoctorDescription($data['specialty']),
                'degree' => $data['degree'],
                'specialty' => $data['specialty'],
                'graduation_code' => 'CMP-' . rand(10000, 99999),
                'city' => $this->getRandomCity(),
                'university' => $data['university'],
                'rating' => rand(35, 50) / 10,
                'schedule' => $this->getRandomSchedule(),
                'phone' => $data['phone'],
                'emergency_phone' => $data['emergency_phone'],
                'clinic_phone' => $data['clinic_phone'],
            ]);
        }

        // ==========================================
        // ABOGADOS ADICIONALES
        // ==========================================
        $lawyersData = [
            [
                'name' => 'Dra. Silvia Rojas',
                'email' => 'silvia.rojas@abogado.com',
                'first_name' => 'Silvia',
                'last_name' => 'Rojas',
                'specialty' => 'Derecho Penal',
                'university' => 'Pontificia Universidad Católica del Perú',
                'phone' => '988234567',
                'office_phone' => '012345679',
            ],
            [
                'name' => 'Dr. Miguel Ángel Torres',
                'email' => 'miguel.torres@abogado.com',
                'first_name' => 'Miguel Ángel',
                'last_name' => 'Torres',
                'specialty' => 'Derecho Civil',
                'university' => 'Universidad Nacional Mayor de San Marcos',
                'phone' => '977890123',
                'office_phone' => '013456790',
            ],
            [
                'name' => 'Dra. Rosa María Pérez',
                'email' => 'rosa.perez@abogado.com',
                'first_name' => 'Rosa María',
                'last_name' => 'Pérez',
                'specialty' => 'Derecho de Familia',
                'university' => 'Universidad de Lima',
                'phone' => '966123456',
                'office_phone' => '014567891',
            ],
        ];

        foreach ($lawyersData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('123456'),
                'avatar' => $avatars[$avatarIndex++ % count($avatars)],
                'phone' => $data['phone'],
                'dni' => '3' . rand(10000000, 99999999),
                'address' => $this->generateAddress(),
                'city' => $this->getRandomCity(),
            ])->assignRole('lawyer');

            Lawyer::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'description' => $this->generateLawyerDescription($data['specialty']),
                'specialty' => $data['specialty'],
                'license_code' => 'ABO' . rand(100, 999),
                'city' => $this->getRandomCity(),
                'university' => $data['university'],
                'schedule' => $this->getRandomSchedule(),
                'phone' => $data['phone'],
                'office_phone' => $data['office_phone'],
            ]);
        }

        // ==========================================
        // TIENDAS ADICIONALES
        // ==========================================
        $shopsData = [
            [
                'name' => 'Tienda Tech Solutions',
                'email' => 'tech@tienda.com',
                'description' => 'Venta de productos tecnológicos y accesorios',
                'city' => 'Lima',
                'phone' => '955678901',
            ],
            [
                'name' => 'Moda y Estilo',
                'email' => 'moda@tienda.com',
                'description' => 'Ropa y accesorios de última moda',
                'city' => 'Arequipa',
                'phone' => '944789012',
            ],
            [
                'name' => 'Supermercado El Ahorro',
                'email' => 'ahorro@tienda.com',
                'description' => 'Supermercado con los mejores precios',
                'city' => 'Trujillo',
                'phone' => '933890123',
            ],
        ];

        foreach ($shopsData as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('123456'),
                'avatar' => $avatars[$avatarIndex++ % count($avatars)],
                'phone' => $data['phone'],
                'dni' => '4' . rand(10000000, 99999999),
                'address' => $this->generateAddress(),
                'city' => $data['city'],
            ])->assignRole('shop');

            Shop::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'city' => $data['city'],
                'address' => $this->generateAddress(),
                'phone' => $data['phone'],
                'schedule' => 'L-S 9:00-21:00',
            ]);
        }
    }

    /**
     * Obtener lista de avatares
     */
    private function getAvatarList(): array
    {
        return array_map(
            fn($i) => "https://i.pravatar.cc/300?img={$i}",
            range(6, 50)
        );
    }

    /**
     * Generar dirección aleatoria
     */
    private function generateAddress(): string
    {
        $streets = [
            'Av. Principal', 'Calle Real', 'Jr. San Martín', 'Av. Los Héroes',
            'Calle Los Álamos', 'Av. Grau', 'Jr. Ayacucho', 'Av. Venezuela',
            'Calle Las Flores', 'Av. La Paz', 'Jr. Arequipa', 'Av. Los Olivos'
        ];
        $numbers = rand(100, 999);
        $zone = ['San Isidro', 'Miraflores', 'Surco', 'San Miguel', 'Barranco', 'San Borja', 'La Molina'];
        
        return $streets[array_rand($streets)] . ' ' . $numbers . ', ' . $zone[array_rand($zone)];
    }

    /**
     * Generar sitio web
     */
    private function generateWebsite(string $name): string
    {
        $slug = strtolower(str_replace(' ', '-', $name));
        return "https://{$slug}.org";
    }

    /**
     * Generar descripción para doctor
     */
    private function generateDoctorDescription(string $specialty): string
    {
        $descriptions = [
            'Cardiología' => 'Especialista en cardiología con amplia experiencia en el diagnóstico y tratamiento de enfermedades del corazón.',
            'Pediatría' => 'Médico pediatra dedicado al cuidado integral de la salud infantil, con enfoque en el desarrollo temprano.',
            'Neurología' => 'Neurólogo especializado en el diagnóstico y tratamiento de enfermedades del sistema nervioso.',
            'Dermatología' => 'Dermatólogo experto en el cuidado de la piel, cabello y uñas.',
            'Medicina General' => 'Médico general con amplia experiencia en atención primaria y prevención de enfermedades.',
        ];
        
        return $descriptions[$specialty] ?? 'Médico especialista con amplia experiencia profesional.';
    }

    /**
     * Generar descripción para abogado
     */
    private function generateLawyerDescription(string $specialty): string
    {
        $descriptions = [
            'Derecho Penal' => 'Abogado penalista con experiencia en defensa y litigio en materia penal.',
            'Derecho Civil' => 'Abogado civilista especializado en contratos, propiedad y derecho de familia.',
            'Derecho de Familia' => 'Abogado especialista en derecho de familia, divorcios y custodia.',
            'Derecho Laboral' => 'Abogado laboralista con experiencia en derecho laboral y seguridad social.',
            'Derecho Comercial' => 'Abogado especializado en derecho empresarial y corporativo.',
        ];
        
        return $descriptions[$specialty] ?? 'Abogado especialista con amplia experiencia profesional.';
    }

    /**
     * Obtener ciudad aleatoria del Perú
     */
    private function getRandomCity(): string
    {
        $cities = ['Lima', 'Arequipa', 'Cusco', 'Trujillo', 'Piura', 'Chiclayo', 'Huancayo', 'Iquitos', 'Tacna', 'Moquegua', 'Puno', 'Cajamarca'];
        return $cities[array_rand($cities)];
    }

    /**
     * Generar horario aleatorio
     */
    private function getRandomSchedule(): string
    {
        $schedules = [
            'L-V 9:00-18:00',
            'L-V 8:00-20:00',
            'L-S 9:00-19:00',
            'L-V 9:00-17:00',
            'L-S 8:00-22:00',
            'L-V 8:00-18:00',
            'L-S 9:00-21:00',
        ];
        return $schedules[array_rand($schedules)];
    }
}