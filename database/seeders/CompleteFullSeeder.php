<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Lawyer;
use App\Models\Association;
use App\Models\Shop;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use App\Models\News;
use App\Models\Comment;
use App\Models\Feedback;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class CompleteFullSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. LIMPIAR CACHÉ DE ROLES Y PERMISOS
        // ==========================================
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ==========================================
        // 2. CREAR ROLES
        // ==========================================
        $roles = ['admin', 'doctor', 'lawyer', 'association', 'shop', 'user'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }
        $this->command->info('✅ Roles creados: ' . implode(', ', $roles));

        // ==========================================
        // 3. CREAR PERMISOS
        // ==========================================
        $permissions = [
            'view_posts', 'create_posts', 'edit_posts', 'delete_posts',
            'view_users', 'manage_users', 'manage_roles',
            'view_products', 'create_products', 'edit_products', 'delete_products',
            'view_services', 'create_services', 'edit_services', 'delete_services',
            'view_news', 'create_news', 'edit_news', 'delete_news',
            'view_comments', 'create_comments', 'edit_comments', 'delete_comments',
            'view_feedback', 'create_feedback', 'edit_feedback', 'delete_feedback',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'api']);
        }
        $this->command->info('✅ Permisos creados: ' . implode(', ', $permissions));

        // ==========================================
        // 4. ASIGNAR PERMISOS AL ADMIN
        // ==========================================
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(Permission::where('guard_name', 'api')->get());
            $this->command->info('✅ Permisos asignados al rol admin');
        }

        // ==========================================
        // 5. CREAR USUARIOS CON SEXO
        // ==========================================
        
        $usuariosData = [
            // ==========================================
            // ADMIN
            // ==========================================
            [
                'role' => 'admin',
                'name' => 'Admin',
                'email' => 'admin@test.com',
                'password' => '12345678',
                'phone' => '999888777',
                'dni' => '12345678',
                'address' => 'Av. Principal 123',
                'city' => 'Lima',
                'sexo' => 'masculino',
                'avatar' => 'https://tudealer.app/avatar_admin.jpg',
                'profile_type' => null,
                'profile_data' => null
            ],
            
            // ==========================================
            // DOCTOR (masculino)
            // ==========================================
            [
                'role' => 'doctor',
                'name' => 'Dr. Juan Pérez',
                'email' => 'doctor@test.com',
                'password' => '12345678',
                'phone' => '988111222',
                'dni' => '23456789',
                'address' => 'Calle Las Lomas 456',
                'city' => 'Miraflores',
                'sexo' => 'masculino',
                'avatar' => 'https://tudealer.app/avatar_doctor.jpg',
                'profile_type' => 'doctor',
                'profile_data' => [
                    'first_name' => 'Juan',
                    'last_name' => 'Pérez',
                    'description' => 'Médico cardiólogo con más de 15 años de experiencia.',
                    'degree' => 'Doctor en Medicina',
                    'specialty' => 'Cardiología',
                    'graduation_code' => 'MED-2020-001',
                    'university' => 'Universidad Nacional Mayor de San Marcos',
                    'services' => json_encode(['Consulta General', 'Ecocardiograma']),
                    'rating' => 4.8,
                    'image' => 'https://picsum.photos/seed/doctor_male/400/400',
                    'schedule' => 'Lun-Vie 8:00-18:00',
                    'phone' => '988111222',
                    'emergency_phone' => '999000111',
                    'clinic_phone' => '4567890',
                    'sexo' => 'masculino',
                ]
            ],
            
            // ==========================================
            // DOCTORA (femenino)
            // ==========================================
            [
                'role' => 'doctor',
                'name' => 'Dra. Ana Martínez',
                'email' => 'doctora@test.com',
                'password' => '12345678',
                'phone' => '988333444',
                'dni' => '34567891',
                'address' => 'Calle Los Rosales 789',
                'city' => 'San Isidro',
                'sexo' => 'femenino',
                'avatar' => 'https://tudealer.app/avatar_doctora.jpg',
                'profile_type' => 'doctor',
                'profile_data' => [
                    'first_name' => 'Ana',
                    'last_name' => 'Martínez',
                    'description' => 'Médica pediatra con especialización en neonatología.',
                    'degree' => 'Doctora en Medicina',
                    'specialty' => 'Pediatría y Neonatología',
                    'graduation_code' => 'MED-2018-005',
                    'university' => 'Universidad Peruana Cayetano Heredia',
                    'services' => json_encode(['Consulta Pediátrica', 'Neonatología']),
                    'rating' => 4.9,
                    'image' => 'https://picsum.photos/seed/doctor_female/400/400',
                    'schedule' => 'Lun-Vie 9:00-19:00',
                    'phone' => '988333444',
                    'emergency_phone' => '999111222',
                    'clinic_phone' => '4567892',
                    'sexo' => 'femenino',
                ]
            ],
            
            // ==========================================
            // DOCTOR LGBT+
            // ==========================================
            [
                'role' => 'doctor',
                'name' => 'Dr. Alex Rivera',
                'email' => 'doctorlgbt@test.com',
                'password' => '12345678',
                'phone' => '988555666',
                'dni' => '45678901',
                'address' => 'Av. Los Alamos 456',
                'city' => 'Surco',
                'sexo' => 'lgbt',
                'avatar' => 'https://tudealer.app/avatar_doctor.jpg',
                'profile_type' => 'doctor',
                'profile_data' => [
                    'first_name' => 'Alex',
                    'last_name' => 'Rivera',
                    'description' => 'Médico internista con enfoque en salud inclusiva y diversidad.',
                    'degree' => 'Doctor en Medicina',
                    'specialty' => 'Medicina Interna',
                    'graduation_code' => 'MED-2019-003',
                    'university' => 'Universidad de Lima',
                    'services' => json_encode(['Consulta General', 'Atención Inclusiva']),
                    'rating' => 4.7,
                    'image' => 'https://picsum.photos/seed/doctor_lgbt/400/400',
                    'schedule' => 'Lun-Vie 10:00-20:00',
                    'phone' => '988555666',
                    'emergency_phone' => '999333444',
                    'clinic_phone' => '4567895',
                    'sexo' => 'lgbt',
                ]
            ],
            
            // ==========================================
            // LAWYER (masculino)
            // ==========================================
            [
                'role' => 'lawyer',
                'name' => 'Dr. Carlos Rodríguez',
                'email' => 'lawyer@test.com',
                'password' => '12345678',
                'phone' => '977333444',
                'dni' => '56789012',
                'address' => 'Av. Canaval y Moreyra 789',
                'city' => 'San Isidro',
                'sexo' => 'masculino',
                'avatar' => 'https://tudealer.app/avatar_lawyer.jpg',
                'profile_type' => 'lawyer',
                'profile_data' => [
                    'first_name' => 'Carlos',
                    'last_name' => 'Rodríguez',
                    'description' => 'Abogado especializado en derecho corporativo y comercial.',
                    'schedule' => 'Lun-Vie 9:00-19:00',
                    'phone' => '977333444',
                    'office_phone' => '4567891',
                    'specialty' => 'Derecho Corporativo',
                    'license_code' => 'ABG-2015-003',
                    'university' => 'Pontificia Universidad Católica del Perú',
                    'image' => 'https://picsum.photos/seed/lawyer_male/400/400',
                    'sexo' => 'masculino',
                ]
            ],
            
            // ==========================================
            // ABOGADA (femenino)
            // ==========================================
            [
                'role' => 'lawyer',
                'name' => 'Dra. Laura Fernández',
                'email' => 'abogada@test.com',
                'password' => '12345678',
                'phone' => '977555666',
                'dni' => '67890123',
                'address' => 'Calle Los Olivos 456',
                'city' => 'Miraflores',
                'sexo' => 'femenino',
                'avatar' => 'https://tudealer.app/avatar_abogada.jpg',
                'profile_type' => 'lawyer',
                'profile_data' => [
                    'first_name' => 'Laura',
                    'last_name' => 'Fernández',
                    'description' => 'Abogada especializada en derecho familiar y laboral.',
                    'schedule' => 'Lun-Vie 8:30-18:30',
                    'phone' => '977555666',
                    'office_phone' => '4567893',
                    'specialty' => 'Derecho Familiar y Laboral',
                    'license_code' => 'ABG-2017-008',
                    'university' => 'Universidad de Lima',
                    'image' => 'https://picsum.photos/seed/lawyer_female/400/400',
                    'sexo' => 'femenino',
                ]
            ],
            
            // ==========================================
            // ABOGADO LGBT+
            // ==========================================
            [
                'role' => 'lawyer',
                'name' => 'Dr. Miguel Torres',
                'email' => 'abogadolgbt@test.com',
                'password' => '12345678',
                'phone' => '977888999',
                'dni' => '78901234',
                'address' => 'Calle Los Pinos 123',
                'city' => 'San Borja',
                'sexo' => 'lgbt',
                'avatar' => 'https://tudealer.app/avatar_lawyer.jpg',
                'profile_type' => 'lawyer',
                'profile_data' => [
                    'first_name' => 'Miguel',
                    'last_name' => 'Torres',
                    'description' => 'Abogado especializado en derechos humanos y diversidad.',
                    'schedule' => 'Lun-Vie 9:00-18:00',
                    'phone' => '977888999',
                    'office_phone' => '4567896',
                    'specialty' => 'Derechos Humanos',
                    'license_code' => 'ABG-2016-007',
                    'university' => 'Universidad Nacional Mayor de San Marcos',
                    'image' => 'https://picsum.photos/seed/lawyer_lgbt/400/400',
                    'sexo' => 'lgbt',
                ]
            ],
            
            // ==========================================
            // ASSOCIATION
            // ==========================================
            [
                'role' => 'association',
                'name' => 'Asociación Vida Sana',
                'email' => 'association@test.com',
                'password' => '12345678',
                'phone' => '966555666',
                'dni' => '89012345',
                'address' => 'Jr. Los Cipreses 321',
                'city' => 'Surco',
                'sexo' => 'no_especificado',
                'avatar' => 'https://tudealer.app/avatar_asociacion.jpg',
                'profile_type' => 'association',
                'profile_data' => [
                    'name' => 'Asociación Vida Sana',
                    'description' => 'Organización sin fines de lucro dedicada a promover hábitos de vida saludable.',
                    'city' => 'Surco',
                    'address' => 'Jr. Los Cipreses 321',
                    'phone' => '966555666',
                    'image' => 'https://picsum.photos/seed/association/400/400',
                    'website' => 'https://vidasana.org',
                    'sexo' => 'no_especificado',
                ]
            ],
            
            // ==========================================
            // SHOP
            // ==========================================
            [
                'role' => 'shop',
                'name' => 'Tienda Central',
                'email' => 'shop@test.com',
                'password' => '12345678',
                'phone' => '955777888',
                'dni' => '90123456',
                'address' => 'Av. Javier Prado 456',
                'city' => 'San Borja',
                'sexo' => 'no_especificado',
                'avatar' => 'https://tudealer.app/avatar_store.jpg',
                'profile_type' => 'shop',
                'profile_data' => [
                    'name' => 'Tienda Central',
                    'category' => 'Tecnología y Electrónica',
                    'description' => 'Tienda especializada en productos tecnológicos.',
                    'address' => 'Av. Javier Prado 456',
                    'city' => 'San Borja',
                    'phone' => '955777888',
                    'image' => 'https://picsum.photos/seed/shop/400/400',
                    'schedule' => 'Lun-Sab 10:00-20:00',
                    'sexo' => 'no_especificado',
                ]
            ],
            
            // ==========================================
            // USUARIO NORMAL (masculino)
            // ==========================================
            [
                'role' => 'user',
                'name' => 'Carlos Sánchez',
                'email' => 'usuario@test.com',
                'password' => '12345678',
                'phone' => '944999000',
                'dni' => '01234567',
                'address' => 'Av. La Marina 789',
                'city' => 'San Miguel',
                'sexo' => 'masculino',
                'avatar' => 'https://tudealer.app/avatar_user.jpg',
                'profile_type' => null,
                'profile_data' => null
            ],
            
            // ==========================================
            // USUARIA NORMAL (femenino)
            // ==========================================
            [
                'role' => 'user',
                'name' => 'María Torres',
                'email' => 'usuaria@test.com',
                'password' => '12345678',
                'phone' => '944888000',
                'dni' => '12345678',
                'address' => 'Calle Los Jazmines 123',
                'city' => 'Pueblo Libre',
                'sexo' => 'femenino',
                'avatar' => 'https://tudealer.app/avatar_usuaria.jpg',
                'profile_type' => null,
                'profile_data' => null
            ],
            
            // ==========================================
            // USUARIO LGBT+
            // ==========================================
            [
                'role' => 'user',
                'name' => 'Alex Soto',
                'email' => 'usuariolgbt@test.com',
                'password' => '12345678',
                'phone' => '944777000',
                'dni' => '23456789',
                'address' => 'Calle Los Girasoles 456',
                'city' => 'Lima',
                'sexo' => 'lgbt',
                'avatar' => 'https://tudealer.app/avatar_user.jpg',
                'profile_type' => null,
                'profile_data' => null
            ],
        ];

        $users = [];
        foreach ($usuariosData as $data) {
            if (!User::where('email', $data['email'])->exists()) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'phone' => $data['phone'],
                    'dni' => $data['dni'],
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'sexo' => $data['sexo'],
                    'avatar' => $data['avatar'],
                ]);
                
                $user->assignRole($data['role']);
                $users[$data['email']] = $user;
                
                if ($data['profile_type'] && $data['profile_data']) {
                    $this->createProfile($data['profile_type'], $user, $data['profile_data']);
                }
                
                $this->command->info("✅ Usuario {$data['role']} creado: {$data['email']} (sexo: {$data['sexo']})");
            } else {
                $this->command->warn("⚠️ Usuario ya existe: {$data['email']}");
                $users[$data['email']] = User::where('email', $data['email'])->first();
            }
        }

        // ==========================================
        // 6. OBTENER PERFILES PARA SERVICIOS
        // ==========================================
        $doctors = Doctor::all();
        $lawyers = Lawyer::all();
        $associations = Association::all();
        $shops = Shop::all();

        $this->command->info('👨‍⚕️ Doctores encontrados: ' . $doctors->count());
        $this->command->info('⚖️ Abogados encontrados: ' . $lawyers->count());
        $this->command->info('🤝 Asociaciones encontradas: ' . $associations->count());
        $this->command->info('🛍️ Tiendas encontradas: ' . $shops->count());

        // ==========================================
        // 7. CREAR POSTS
        // ==========================================
        $this->command->info('📝 Creando posts...');
        
        $doctor1 = Doctor::where('user_id', $users['doctor@test.com']->id ?? 0)->first();
        $doctor2 = Doctor::where('user_id', $users['doctora@test.com']->id ?? 0)->first();
        $doctor3 = Doctor::where('user_id', $users['doctorlgbt@test.com']->id ?? 0)->first();
        $lawyer1 = Lawyer::where('user_id', $users['lawyer@test.com']->id ?? 0)->first();
        $lawyer2 = Lawyer::where('user_id', $users['abogada@test.com']->id ?? 0)->first();
        $lawyer3 = Lawyer::where('user_id', $users['abogadolgbt@test.com']->id ?? 0)->first();
        $association = Association::where('user_id', $users['association@test.com']->id ?? 0)->first();
        $shop = Shop::where('user_id', $users['shop@test.com']->id ?? 0)->first();
        
        $posts = [];
        
        // Posts del Doctor 1
        if ($doctor1) {
            $posts[] = Post::create([
                'user_id' => $users['doctor@test.com']->id,
                'title' => '5 Consejos para mantener un corazón saludable',
                'content' => 'La salud cardiovascular es fundamental. Aquí te comparto 5 consejos clave: 1. Mantén una dieta balanceada. 2. Realiza actividad física. 3. Controla tu presión arterial. 4. Evita el alcohol y tabaco. 5. Realiza chequeos médicos anuales.',
                'image' => 'https://picsum.photos/seed/heart_health/800/400',
                'category' => 'Salud Cardiovascular',
                'postable_type' => Doctor::class,
                'postable_id' => $doctor1->id,
            ]);
        }
        
        // Posts del Doctor 2
        if ($doctor2) {
            $posts[] = Post::create([
                'user_id' => $users['doctora@test.com']->id,
                'title' => 'Importancia de la vacunación en niños',
                'content' => 'La vacunación es fundamental para proteger a los niños de enfermedades graves. Conoce el calendario de vacunación recomendado.',
                'image' => 'https://picsum.photos/seed/vaccine/800/400',
                'category' => 'Pediatría',
                'postable_type' => Doctor::class,
                'postable_id' => $doctor2->id,
            ]);
        }
        
        // Posts del Doctor LGBT+
        if ($doctor3) {
            $posts[] = Post::create([
                'user_id' => $users['doctorlgbt@test.com']->id,
                'title' => 'Salud inclusiva: Atención para la comunidad LGBT+',
                'content' => 'La atención médica debe ser inclusiva y respetuosa con la diversidad. Comparto recomendaciones para una atención de calidad.',
                'image' => 'https://picsum.photos/seed/lgbt_health/800/400',
                'category' => 'Salud Inclusiva',
                'postable_type' => Doctor::class,
                'postable_id' => $doctor3->id,
            ]);
        }
        
        // Posts del Lawyer 1
        if ($lawyer1) {
            $posts[] = Post::create([
                'user_id' => $users['lawyer@test.com']->id,
                'title' => 'Cambios en la legislación corporativa 2026',
                'content' => 'Este año se han implementado importantes cambios en la legislación corporativa. Te explicamos las modificaciones principales.',
                'image' => 'https://picsum.photos/seed/corporate_law/800/400',
                'category' => 'Derecho Corporativo',
                'postable_type' => Lawyer::class,
                'postable_id' => $lawyer1->id,
            ]);
        }
        
        // Posts de la Abogada 2
        if ($lawyer2) {
            $posts[] = Post::create([
                'user_id' => $users['abogada@test.com']->id,
                'title' => 'Derechos laborales que todo trabajador debe conocer',
                'content' => 'Conoce tus derechos laborales: horas extras, vacaciones, gratificaciones, y más. Información clave para proteger tus derechos.',
                'image' => 'https://picsum.photos/seed/labor_rights/800/400',
                'category' => 'Derecho Laboral',
                'postable_type' => Lawyer::class,
                'postable_id' => $lawyer2->id,
            ]);
        }
        
        // Posts del Abogado LGBT+
        if ($lawyer3) {
            $posts[] = Post::create([
                'user_id' => $users['abogadolgbt@test.com']->id,
                'title' => 'Derechos de la comunidad LGBT+ en el Perú',
                'content' => 'Conoce los derechos legales de la comunidad LGBT+ en Perú: matrimonio igualitario, identidad de género y más.',
                'image' => 'https://picsum.photos/seed/lgbt_rights/800/400',
                'category' => 'Derechos Humanos',
                'postable_type' => Lawyer::class,
                'postable_id' => $lawyer3->id,
            ]);
        }
        
        // Posts de la Association
        if ($association) {
            $posts[] = Post::create([
                'user_id' => $users['association@test.com']->id,
                'title' => 'Campaña de alimentación saludable 2026',
                'content' => 'Iniciamos nuestra campaña anual de alimentación saludable. Únete a nosotros para aprender sobre nutrición balanceada.',
                'image' => 'https://picsum.photos/seed/healthy_food/800/400',
                'category' => 'Salud y Bienestar',
                'postable_type' => Association::class,
                'postable_id' => $association->id,
            ]);
        }
        
        // Posts de la Shop
        if ($shop) {
            $posts[] = Post::create([
                'user_id' => $users['shop@test.com']->id,
                'title' => 'Nuevos productos tecnológicos disponibles',
                'content' => 'Hemos ampliado nuestro catálogo con los últimos productos tecnológicos. Encuentra lo último en smartphones y tablets.',
                'image' => 'https://picsum.photos/seed/technology/800/400',
                'category' => 'Tecnología',
                'postable_type' => Shop::class,
                'postable_id' => $shop->id,
            ]);
        }
        
        $this->command->info("✅ " . count($posts) . " posts creados");

        // ==========================================
        // 8. CREAR NOTICIAS
        // ==========================================
        $this->command->info('📰 Creando noticias...');
        
        $admin = $users['admin@test.com'] ?? User::where('email', 'admin@test.com')->first();
        
        $news = [
            [
                'titulo' => 'Lanzamiento de la nueva plataforma TuDealer',
                'descripcion' => 'Presentamos la nueva versión de TuDealer con características mejoradas.',
                'url' => 'https://tudealer.app/news/1',
                'image' => 'https://picsum.photos/seed/launch/800/400',
            ],
            [
                'titulo' => 'Alianza estratégica con organizaciones de salud',
                'descripcion' => 'TuDealer se alía con las principales organizaciones de salud.',
                'url' => 'https://tudealer.app/news/2',
                'image' => 'https://picsum.photos/seed/alliance/800/400',
            ],
            [
                'titulo' => 'Nuevas funcionalidades para profesionales',
                'descripcion' => 'Hemos añadido nuevas funcionalidades para médicos, abogados y otros profesionales.',
                'url' => 'https://tudealer.app/news/3',
                'image' => 'https://picsum.photos/seed/features/800/400',
            ],
        ];

        foreach ($news as $newData) {
            News::create([
                'user_id' => $admin->id,
                'newable_type' => null,
                'newable_id' => null,
                'titulo' => $newData['titulo'],
                'descripcion' => $newData['descripcion'],
                'url' => $newData['url'],
                'image' => $newData['image'],
                'fecha_publicacion' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("✅ Noticia creada: {$newData['titulo']}");
        }

        // ==========================================
        // 9. CREAR PRODUCTOS
        // ==========================================
        $this->command->info('🛍️ Creando productos...');
        
        if ($shop) {
            $products = [
                ['name' => 'Smartphone Galaxy S25 Ultra', 'description' => 'El último smartphone con cámara de 200MP.', 'price' => 4999.00, 'image' => 'https://picsum.photos/seed/smartphone/400/400', 'stock' => 25],
                ['name' => 'Laptop Pro 16" M4', 'description' => 'Laptop de alto rendimiento para profesionales.', 'price' => 7999.00, 'image' => 'https://picsum.photos/seed/laptop/400/400', 'stock' => 15],
                ['name' => 'Auriculares Inalámbricos Pro', 'description' => 'Auriculares con cancelación de ruido activa.', 'price' => 499.00, 'image' => 'https://picsum.photos/seed/headphones/400/400', 'stock' => 50],
                ['name' => 'Tablet Pro 12.9"', 'description' => 'Tablet profesional con pantalla de 12.9 pulgadas.', 'price' => 2999.00, 'image' => 'https://picsum.photos/seed/tablet/400/400', 'stock' => 30],
                ['name' => 'Smartwatch Fitness Pro', 'description' => 'Reloj inteligente con monitoreo de actividad física.', 'price' => 899.00, 'image' => 'https://picsum.photos/seed/smartwatch/400/400', 'stock' => 40],
            ];

            foreach ($products as $productData) {
                Product::create([
                    'productable_type' => Shop::class,
                    'productable_id' => $shop->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'image' => $productData['image'],
                    'stock' => $productData['stock'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("✅ Producto creado: {$productData['name']}");
            }
        }

        // ==========================================
        // 10. CREAR SERVICIOS CON IMÁGENES
        // ==========================================
        $this->command->info('💼 Creando servicios con imágenes...');

        // 🔥 DEFINIR SERVICIOS CON IMÁGENES
        $allServices = [
            // ============================================================
            // 🏥 SERVICIOS MÉDICOS - DOCTORES
            // ============================================================
            [
                'name' => 'Consulta de Cardiología',
                'description' => 'Evaluación cardiovascular completa con historia clínica, examen físico y recomendaciones personalizadas.',
                'price' => 250.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/cardiologia_consulta/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Ecocardiograma',
                'description' => 'Estudio ecocardiográfico completo que permite evaluar la estructura y función del corazón.',
                'price' => 450.00,
                'duration' => 45,
                'image' => 'https://picsum.photos/seed/ecocardiograma_estudio/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Prueba de Esfuerzo',
                'description' => 'Evaluación cardiovascular durante el ejercicio controlado para detectar problemas cardíacos.',
                'price' => 350.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/prueba_esfuerzo_cardio/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Consulta Pediátrica',
                'description' => 'Atención médica integral para niños desde el nacimiento hasta la adolescencia.',
                'price' => 200.00,
                'duration' => 45,
                'image' => 'https://picsum.photos/seed/pediatria_consulta/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Control de Crecimiento',
                'description' => 'Evaluación completa del desarrollo físico, psicomotor y nutricional del niño.',
                'price' => 180.00,
                'duration' => 30,
                'image' => 'https://picsum.photos/seed/crecimiento_infantil/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Vacunación Infantil',
                'description' => 'Aplicación de vacunas según calendario nacional e internacional.',
                'price' => 150.00,
                'duration' => 30,
                'image' => 'https://picsum.photos/seed/vacunacion_infantil/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Consulta de Medicina Interna',
                'description' => 'Atención médica integral con enfoque en salud inclusiva y diversidad.',
                'price' => 220.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/medicina_interna_consulta/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Atención Inclusiva LGBT+',
                'description' => 'Consulta médica especializada con enfoque en la comunidad LGBT+ en un ambiente seguro y respetuoso.',
                'price' => 280.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/lgbt_salud_inclusiva/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Dermatología General',
                'description' => 'Consulta especializada en diagnóstico y tratamiento de enfermedades de la piel.',
                'price' => 230.00,
                'duration' => 45,
                'image' => 'https://picsum.photos/seed/dermatologia_consulta/400/300',
                'types' => ['doctor'],
            ],
            [
                'name' => 'Nutrición Clínica',
                'description' => 'Evaluación nutricional personalizada con planes de alimentación adaptados a tus necesidades.',
                'price' => 200.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/nutricion_clinica/400/300',
                'types' => ['doctor'],
            ],

            // ============================================================
            // ⚖️ SERVICIOS LEGALES - ABOGADOS
            // ============================================================
            [
                'name' => 'Consultoría Legal Corporativa',
                'description' => 'Asesoría legal integral para empresas en temas corporativos, societarios y comerciales.',
                'price' => 500.00,
                'duration' => 120,
                'image' => 'https://picsum.photos/seed/corporativo_legal/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Redacción de Contratos',
                'description' => 'Elaboración y revisión de contratos civiles, comerciales y laborales.',
                'price' => 350.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/contratos_legal/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Representación Legal',
                'description' => 'Representación en procesos judiciales, arbitrajes y procedimientos administrativos.',
                'price' => 800.00,
                'duration' => 180,
                'image' => 'https://picsum.photos/seed/representacion_legal/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Asesoría Laboral',
                'description' => 'Asesoría legal especializada en derechos laborales, contratos y beneficios sociales.',
                'price' => 300.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/laboral_asesoria/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Proceso de Divorcio',
                'description' => 'Asesoría y representación legal en procesos de divorcio, separación de bienes y custodia.',
                'price' => 600.00,
                'duration' => 120,
                'image' => 'https://picsum.photos/seed/divorcio_legal/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Sucesiones y Herencias',
                'description' => 'Asesoría legal en procesos de sucesión, herencias y testamentos.',
                'price' => 450.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/sucesiones_legal/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Asesoría en Derechos Humanos',
                'description' => 'Orientación y defensa legal en temas de derechos humanos y no discriminación.',
                'price' => 350.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/derechos_humanos_legal/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Cambio de Identidad de Género',
                'description' => 'Asesoría legal completa para el cambio de identidad de género en documentos oficiales.',
                'price' => 500.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/identidad_genero_legal/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Propiedad Intelectual',
                'description' => 'Asesoría en registro de marcas, patentes y derechos de autor.',
                'price' => 400.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/propiedad_intelectual/400/300',
                'types' => ['lawyer'],
            ],
            [
                'name' => 'Derecho Inmobiliario',
                'description' => 'Asesoría legal en compra-venta de inmuebles y contratos de arrendamiento.',
                'price' => 450.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/inmobiliario_legal/400/300',
                'types' => ['lawyer'],
            ],

            // ============================================================
            // 🤝 SERVICIOS - ASOCIACIONES
            // ============================================================
            [
                'name' => 'Taller de Nutrición Saludable',
                'description' => 'Taller práctico sobre alimentación balanceada y hábitos saludables para toda la familia.',
                'price' => 50.00,
                'duration' => 120,
                'image' => 'https://picsum.photos/seed/nutricion_taller/400/300',
                'types' => ['association'],
            ],
            [
                'name' => 'Charla de Bienestar Integral',
                'description' => 'Charlas educativas sobre bienestar físico, mental y emocional.',
                'price' => 30.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/bienestar_charla/400/300',
                'types' => ['association'],
            ],
            [
                'name' => 'Asesoría Nutricional',
                'description' => 'Consultoría personalizada para mejorar hábitos alimenticios y estilo de vida.',
                'price' => 80.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/asesoria_nutricional/400/300',
                'types' => ['association'],
            ],
            [
                'name' => 'Programa de Actividad Física',
                'description' => 'Programa de ejercicios y actividad física adaptado a diferentes edades.',
                'price' => 60.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/actividad_fisica/400/300',
                'types' => ['association'],
            ],
            [
                'name' => 'Taller de Mindfulness',
                'description' => 'Taller de técnicas de mindfulness y meditación para reducir el estrés.',
                'price' => 40.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/mindfulness_taller/400/300',
                'types' => ['association'],
            ],

            // ============================================================
            // 🛍️ SERVICIOS - TIENDAS
            // ============================================================
            [
                'name' => 'Asesoría Tecnológica',
                'description' => 'Consultoría personalizada para elegir los mejores productos tecnológicos según tus necesidades.',
                'price' => 100.00,
                'duration' => 60,
                'image' => 'https://picsum.photos/seed/asesoria_tecnologica/400/300',
                'types' => ['shop'],
            ],
            [
                'name' => 'Mantenimiento de Equipos',
                'description' => 'Servicio de mantenimiento preventivo y correctivo para equipos tecnológicos.',
                'price' => 150.00,
                'duration' => 120,
                'image' => 'https://picsum.photos/seed/mantenimiento_equipos/400/300',
                'types' => ['shop'],
            ],
            [
                'name' => 'Capacitación en Tecnología',
                'description' => 'Curso básico y avanzado de manejo de herramientas tecnológicas.',
                'price' => 200.00,
                'duration' => 180,
                'image' => 'https://picsum.photos/seed/capacitacion_tecnologica/400/300',
                'types' => ['shop'],
            ],
            [
                'name' => 'Instalación de Software',
                'description' => 'Instalación y configuración de software especializado y aplicaciones empresariales.',
                'price' => 120.00,
                'duration' => 90,
                'image' => 'https://picsum.photos/seed/instalacion_software/400/300',
                'types' => ['shop'],
            ],
            [
                'name' => 'Recuperación de Datos',
                'description' => 'Servicio especializado en recuperación de datos de discos duros y dispositivos de almacenamiento.',
                'price' => 250.00,
                'duration' => 120,
                'image' => 'https://picsum.photos/seed/recuperacion_datos/400/300',
                'types' => ['shop'],
            ],
        ];

        // 🔥 ASIGNAR SERVICIOS A DOCTORES
        $doctorServices = array_filter($allServices, function($service) {
            return in_array('doctor', $service['types']);
        });
        $doctorServices = array_values($doctorServices);
        
        foreach ($doctors as $doctor) {
            $randomServices = collect($doctorServices)->random(min(3, count($doctorServices)));
            
            foreach ($randomServices as $serviceData) {
                Service::create([
                    'serviceable_type' => Doctor::class,
                    'serviceable_id' => $doctor->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'image' => $serviceData['image'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command->info("✅ Servicios creados para doctores");

        // 🔥 ASIGNAR SERVICIOS A ABOGADOS
        $lawyerServices = array_filter($allServices, function($service) {
            return in_array('lawyer', $service['types']);
        });
        $lawyerServices = array_values($lawyerServices);
        
        foreach ($lawyers as $lawyer) {
            $randomServices = collect($lawyerServices)->random(min(3, count($lawyerServices)));
            
            foreach ($randomServices as $serviceData) {
                Service::create([
                    'serviceable_type' => Lawyer::class,
                    'serviceable_id' => $lawyer->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'image' => $serviceData['image'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command->info("✅ Servicios creados para abogados");

        // 🔥 ASIGNAR SERVICIOS A ASOCIACIONES
        $associationServices = array_filter($allServices, function($service) {
            return in_array('association', $service['types']);
        });
        $associationServices = array_values($associationServices);
        
        foreach ($associations as $association) {
            $randomServices = collect($associationServices)->random(min(2, count($associationServices)));
            
            foreach ($randomServices as $serviceData) {
                Service::create([
                    'serviceable_type' => Association::class,
                    'serviceable_id' => $association->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'image' => $serviceData['image'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command->info("✅ Servicios creados para asociaciones");

        // 🔥 ASIGNAR SERVICIOS A TIENDAS
        $shopServices = array_filter($allServices, function($service) {
            return in_array('shop', $service['types']);
        });
        $shopServices = array_values($shopServices);
        
        foreach ($shops as $shop) {
            $randomServices = collect($shopServices)->random(min(2, count($shopServices)));
            
            foreach ($randomServices as $serviceData) {
                Service::create([
                    'serviceable_type' => Shop::class,
                    'serviceable_id' => $shop->id,
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'price' => $serviceData['price'],
                    'duration' => $serviceData['duration'],
                    'image' => $serviceData['image'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->command->info("✅ Servicios creados para tiendas");

        // ==========================================
        // 11. CREAR COMENTARIOS
        // ==========================================
        $this->command->info('💬 Creando comentarios...');
        
        $userMale = $users['usuario@test.com'] ?? null;
        $userFemale = $users['usuaria@test.com'] ?? null;
        $userLgbt = $users['usuariolgbt@test.com'] ?? null;
        
        if ($userMale && $userFemale) {
            foreach ($posts as $index => $post) {
                Comment::create([
                    'user_id' => $userMale->id,
                    'commentable_type' => Post::class,
                    'commentable_id' => $post->id,
                    'content' => "Excelente publicación! Muy informativa. 👍",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Comment::create([
                    'user_id' => $userFemale->id,
                    'commentable_type' => Post::class,
                    'commentable_id' => $post->id,
                    'content' => "Gracias por compartir esta valiosa información.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                if ($userLgbt && $index % 2 == 0) {
                    Comment::create([
                        'user_id' => $userLgbt->id,
                        'commentable_type' => Post::class,
                        'commentable_id' => $post->id,
                        'content' => "Excelente contenido. Muy importante para la comunidad.",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                $this->command->info("✅ Comentarios añadidos al post: {$post->title}");
            }
        }

        // ==========================================
        // 12. CREAR FEEDBACKS
        // ==========================================
        $this->command->info('⭐ Creando feedbacks...');
        
        if ($doctor1 && $userMale && $userFemale) {
            $feedbacks = [
                ['user' => $userMale, 'feedbackable_type' => Doctor::class, 'feedbackable_id' => $doctor1->id, 'comment' => 'Excelente profesional, muy atento.', 'rating' => 5],
                ['user' => $userFemale, 'feedbackable_type' => Doctor::class, 'feedbackable_id' => $doctor1->id, 'comment' => 'Muy recomendado. Su experiencia es notable.', 'rating' => 4],
            ];
            
            foreach ($feedbacks as $feedbackData) {
                Feedback::create([
                    'user_id' => $feedbackData['user']->id,
                    'feedbackable_type' => $feedbackData['feedbackable_type'],
                    'feedbackable_id' => $feedbackData['feedbackable_id'],
                    'comment' => $feedbackData['comment'],
                    'rating' => $feedbackData['rating'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("✅ Feedback creado");
            }
        }
        
        if ($lawyer1 && $userMale && $userFemale) {
            $feedbacks = [
                ['user' => $userMale, 'feedbackable_type' => Lawyer::class, 'feedbackable_id' => $lawyer1->id, 'comment' => 'Excelente abogado, muy profesional.', 'rating' => 5],
                ['user' => $userFemale, 'feedbackable_type' => Lawyer::class, 'feedbackable_id' => $lawyer1->id, 'comment' => 'Muy recomendable. Conocimiento excepcional.', 'rating' => 5],
            ];
            
            foreach ($feedbacks as $feedbackData) {
                Feedback::create([
                    'user_id' => $feedbackData['user']->id,
                    'feedbackable_type' => $feedbackData['feedbackable_type'],
                    'feedbackable_id' => $feedbackData['feedbackable_id'],
                    'comment' => $feedbackData['comment'],
                    'rating' => $feedbackData['rating'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->command->info("✅ Feedback creado");
            }
        }

        $this->command->info('🎉 ¡Seeders completados exitosamente!');
        $this->command->info('📋 Usuarios con sexo:');
        $this->command->info('   - Masculino: admin, doctor, lawyer, usuario');
        $this->command->info('   - Femenino: doctora, abogada, usuaria');
        $this->command->info('   - LGBT+: doctorlgbt, abogadolgbt, usuariolgbt');
        $this->command->info('   - No especificado: association, shop');
        $this->command->info('📊 Total de servicios con imágenes: ' . Service::count());
    }

    // ==========================================
    // FUNCIÓN AUXILIAR PARA CREAR PERFILES
    // ==========================================
    private function createProfile($type, $user, $data)
    {
        switch ($type) {
            case 'doctor':
                Doctor::create(array_merge($data, ['user_id' => $user->id]));
                break;
            case 'lawyer':
                Lawyer::create(array_merge($data, ['user_id' => $user->id]));
                break;
            case 'association':
                Association::create(array_merge($data, ['user_id' => $user->id]));
                break;
            case 'shop':
                Shop::create(array_merge($data, ['user_id' => $user->id]));
                break;
        }
    }
}