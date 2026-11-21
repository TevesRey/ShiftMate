<?php

namespace Database\Seeders;

use App\Models\Shifts;
use Illuminate\Database\Seeder;

/**
 * Seed the standard schedule choices used by the weekly calendar.
 */
class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'shift_name' => 'Morning',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'description' => 'Morning shift, 6:00 AM to 2:00 PM',
            ],
            [
                'shift_name' => 'Afternoon',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'description' => 'Afternoon shift, 2:00 PM to 10:00 PM',
            ],
            [
                'shift_name' => 'Evening',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'description' => 'Evening shift, 10:00 PM to 6:00 AM',
            ],
            [
                'shift_name' => 'Restday',
                'start_time' => '00:00:00',
                'end_time' => '00:00:00',
                'description' => 'Rest day — no working hours',
            ],
        ];

        // Keep existing schedules that used the old "Night" shift connected to Evening.
        $legacyNight = Shifts::where('shift_name', 'Night')->first();
        $evening = Shifts::where('shift_name', 'Evening')->first();
        if ($legacyNight && ! $evening) {
            $legacyNight->update([
                'shift_name' => 'Evening',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'description' => 'Evening shift, 10:00 PM to 6:00 AM',
            ]);
        } elseif ($legacyNight && $evening) {
            // Repoint any existing schedules before removing the obsolete duplicate.
            if (\Illuminate\Support\Facades\Schema::hasTable('schedules')) {
                \Illuminate\Support\Facades\DB::table('schedules')
                    ->where('shift_id', $legacyNight->id)
                    ->update(['shift_id' => $evening->id]);
            }
            $legacyNight->delete();
        }

        foreach ($shifts as $shift) {
            Shifts::updateOrCreate(
                ['shift_name' => $shift['shift_name']],
                $shift,
            );
        }
    }
}
