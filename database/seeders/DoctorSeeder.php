<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        User::role('doctor')->each(function ($user) {
            Doctor::factory()->create([
                'user_id' => $user->id,
            ]);
        });
    }
}
