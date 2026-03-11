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
                'Medicina General',
                'Cardiología',
                'Pediatría',
            ]),
            'graduation_code' => $this->faker->unique()->numerify('CMP-#####'),
            'city' => $this->faker->city(),
            'university' => $this->faker->company(),
            'services' => [],
            'rating' => $this->faker->randomFloat(1, 3, 5),
            'image' => null,
            'schedule' => 'L-V 9:00-18:00',
        ];
    }
}
