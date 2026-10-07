<?php

namespace Database\Factories;

use App\Models\Schedules;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchedulesFactory extends Factory
{
    protected $model = Schedules::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'shift_id' => \App\Models\Shifts::factory(),
            'work_date' => $this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'status' => 'active',
        ];
    }
}
