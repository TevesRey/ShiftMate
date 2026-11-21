<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email_verified_at')) {
            return;
        }

        // Preserve access for existing approved accounts created before the
        // verification workflow was introduced. New registrations remain null.
        $query = DB::table('users')->whereNull('email_verified_at');
        if (Schema::hasColumn('users', 'registration_status')) {
            $query->where('registration_status', 'approved');
        }
        $query->update(['email_verified_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        // Do not clear verification timestamps on rollback; they may be genuine.
    }
};
