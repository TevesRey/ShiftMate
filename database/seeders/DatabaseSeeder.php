<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SampleDataSeeder::class,
        ]);

        // Initial administrator account. Production must provide explicit
        // credentials; never silently seed a publicly known default password.
        $adminName = env('SHIFTMATE_ADMIN_NAME', 'ShiftMate Administrator');
        $adminEmail = strtolower(trim((string) env('SHIFTMATE_ADMIN_EMAIL', 'admin@shiftmate.local')));
        $adminPassword = (string) env('SHIFTMATE_ADMIN_PASSWORD', 'ChangeThisAdminPassword!2026');

        if (app()->environment('production') && (
            ! env('SHIFTMATE_ADMIN_EMAIL') ||
            ! env('SHIFTMATE_ADMIN_PASSWORD') ||
            $adminPassword === 'ChangeThisAdminPassword!2026'
        )) {
            throw new \RuntimeException('Set a unique SHIFTMATE_ADMIN_EMAIL and SHIFTMATE_ADMIN_PASSWORD before running the production seeder.');
        }

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'full_name' => $adminName,
                'password' => \Illuminate\Support\Facades\Hash::make($adminPassword),
                'role' => 'admin',
                'status' => 'active',
                'registration_status' => 'approved',
            ]
        );
    }
}
