<?php

namespace Database\Factories;

use App\Models\Shifts;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftsFactory extends Factory
{
    protected $model = Shifts::class;

    public function definition(): array
    {
        $shifts = [
            ['Morning', '08:00:00', '16:00:00', 'Standard morning shift'],
            ['Afternoon', '16:00:00', '00:00:00', 'Standard afternoon shift'],
            ['Night', '00:00:00', '08:00:00', 'Standard night shift'],
        ];

        $selected = $this->faker->randomElement($shifts);

        return [
            'shift_name' => $selected[0],
            'start_time' => $selected[1],
            'end_time' => $selected[2],
            'description' => $selected[3],
        ];
    }
}
