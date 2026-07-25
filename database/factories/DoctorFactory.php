<?php

namespace Database\Factories;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'description' => $this->faker->paragraph(),
            'degree' => 'Médico Cirujano',
            'specialty' => $this->faker->randomElement([
                'Cardiología', 'Pediatría', 'Medicina General', 
                'Neurología', 'Dermatología', 'Ginecología'
            ]),
            'graduation_code' => 'CMP-' . $this->faker->numberBetween(10000, 99999),
            'city' => $this->faker->city(),
            'university' => $this->faker->randomElement([
                'UNMSM', 'PUCP', 'UPCH', 'UNI', 'UPC', 'USMP'
            ]),
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'schedule' => $this->faker->randomElement([
                'L-V 9:00-18:00',
                'L-V 8:00-20:00',
                'L-S 9:00-19:00'
            ]),
            // Nuevos campos
            'phone' => '9' . $this->faker->numberBetween(10000000, 99999999),
            'emergency_phone' => '9' . $this->faker->numberBetween(10000000, 99999999),
            'clinic_phone' => '01' . $this->faker->numberBetween(1000000, 9999999),
        ];
    }
}