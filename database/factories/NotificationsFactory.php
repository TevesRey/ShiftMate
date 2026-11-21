<?php

namespace Database\Factories;

use App\Models\Notifications;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationsFactory extends Factory
{
    protected $model = Notifications::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'title' => $this->faker->randomElement(['Shift Update', 'Request Approved', 'New Notification', 'Urgent Alert']),
            'message' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['info', 'warning', 'success', 'error']),
            'is_read' => $this->faker->boolean(),
        ];
    }
}
