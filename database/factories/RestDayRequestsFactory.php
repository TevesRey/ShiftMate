<?php

namespace Database\Factories;

use App\Models\RestDayRequests;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestDayRequestsFactory extends Factory
{
    protected $model = RestDayRequests::class;

    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\Employees::factory(),
            'current_rest_day' => $this->faker->date(),
            'requested_rest_day' => $this->faker->date(),
            'reason' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'reviewed_by' => \App\Models\User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
