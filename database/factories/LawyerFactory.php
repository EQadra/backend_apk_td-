<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LawyerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name'   => $this->faker->firstName(),
            'last_name'    => $this->faker->lastName(),
            'description'  => $this->faker->sentence(),
            'specialty'    => $this->faker->randomElement(['Civil', 'Penal', 'Laboral', 'Comercial']),
            'license_code' => strtoupper($this->faker->bothify('ABO###')),
            'city'         => $this->faker->city(),
            'university'   => $this->faker->randomElement(['PUCP', 'UNMSM', 'USMP', 'UPC']),
            'image'        => null,
            'schedule'     => null,
        ];
    }
}
