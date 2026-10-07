<?php

namespace Database\Factories;

use App\Models\Employees;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeesFactory extends Factory
{
    protected $model = Employees::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'employee_number' => $this->faker->unique()->numberBetween(1000, 9999),
            'position' => $this->faker->jobTitle(),
            'department' => $this->faker->randomElement(['HR', 'Engineering', 'Operations', 'Sales', 'Support']),
            'contact_number' => $this->faker->phoneNumber(),
            'status' => 'active',
        ];
    }
}
