<?php

namespace Database\Factories;

use App\Models\Feedback;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeedbackFactory extends Factory
{
    protected $model = Feedback::class;

    public function definition()
    {
        return [
            'rating' => rand(1, 5),
            'comment' => $this->faker->sentence(),
            'user_id' => 1,
        ];
    }
}
