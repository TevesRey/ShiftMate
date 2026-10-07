<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use App\Models\Membership;
use App\Models\Employees;
use App\Models\Shifts;
use App\Models\Schedules;
use App\Models\AbsenceRequests;
use App\Models\RestDayRequests;
use App\Models\Notifications;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Teams
        $teams = Team::factory(3)->create();

        // 2. Create Users & Memberships
        $users = [];
        foreach ($teams as $team) {
            $teamUsers = User::factory(5)->create([
                'email' => function() { return 'user-'.fake()->unique()->safeEmail(); }
            ]);

            foreach ($teamUsers as $user) {
                Membership::create([
                    'user_id' => $user->id,
                    'team_id' => $team->id,
                    'role' => \App\Enums\TeamRole::Member->value,
                ]);
                $users[] = $user;
            }
        }

        // 3. Create Employees linked to Users
        foreach ($users as $user) {
            Employees::factory()->create([
                'user_id' => $user->id,
            ]);
        }

        // 4. Create Standard Shifts
        $morningShift = Shifts::create([
            'shift_name' => 'Morning',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'description' => 'Standard morning shift',
        ]);
        $afternoonShift = Shifts::create([
            'shift_name' => 'Afternoon',
            'start_time' => '16:00:00',
            'end_time' => '00:00:00',
            'description' => 'Standard afternoon shift',
        ]);
        $nightShift = Shifts::create([
            'shift_name' => 'Night',
            'start_time' => '00:00:00',
            'end_time' => '08:00:00',
            'description' => 'Standard night shift',
        ]);

        $allShifts = [$morningShift, $afternoonShift, $nightShift];

        // 5. Create Schedules
        $employees = Employees::all();
        foreach ($employees as $employee) {
            for ($i = 0; $i < 10; $i++) {
                Schedules::create([
                    'user_id' => $employee->user_id,
                    'shift_id' => $allShifts[array_rand($allShifts)]->id,
                    'work_date' => now()->addDays($i)->format('Y-m-d'),
                    'status' => 'active',
                ]);
            }
        }

        // 6. Create Requests
        foreach ($employees as $employee) {
            if (rand(0, 1)) {
                AbsenceRequests::factory()->create([
                    'employee_id' => $employee->id,
                ]);
            }
            if (rand(0, 1)) {
                RestDayRequests::factory()->create([
                    'employee_id' => $employee->id,
                ]);
            }
        }

        // 7. Create Notifications
        foreach ($users as $user) {
            Notifications::factory(2)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
