<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('notification') && Schema::hasColumn('notification', 'type')) {
            DB::table('notification')->where('type', 'task')->delete();
        }
        Schema::dropIfExists('tasks');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'current_team_id')) {
            try {
                Schema::table('users', function ($table) {
                    $table->dropConstrainedForeignId('current_team_id');
                });
            } catch (\Throwable $e) {
                if (Schema::hasColumn('users', 'current_team_id')) {
                    Schema::table('users', function ($table) { $table->dropColumn('current_team_id'); });
                }
            }
        }

        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }

    public function down(): void
    {
        // Removed modules are outside SHIFTMATE's approved scope; restore from backup if needed.
    }
};
