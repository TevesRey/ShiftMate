<?php

namespace Database\Factories;

use App\Models\AbsenceRequests;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsenceRequestsFactory extends Factory
{
    protected $model = AbsenceRequests::class;

    public function definition(): array
    {
        return [
            'employee_id' => \App\Models\Employees::factory(),
            'schedule_id' => \App\Models\Schedules::factory(),
            'absence_date' => $this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'reviewed_by' => \App\Models\User::factory(),
            'reviewed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
