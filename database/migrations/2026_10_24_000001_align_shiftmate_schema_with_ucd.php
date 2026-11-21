<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees')) {
            $drop = [];
            if (Schema::hasColumn('employees', 'department')) $drop[] = 'department';
            if (Schema::hasColumn('employees', 'contact_number')) $drop[] = 'contact_number';
            if ($drop) Schema::table('employees', function (Blueprint $table) use ($drop) { $table->dropColumn($drop); });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'contact_number')) {
            Schema::table('users', function (Blueprint $table) { $table->dropColumn('contact_number'); });
        }

        if (Schema::hasTable('rest_day_request') && Schema::hasColumn('rest_day_request', 'current_rest_day')) {
            Schema::table('rest_day_request', function (Blueprint $table) { $table->dropColumn('current_rest_day'); });
        }

        if (Schema::hasTable('absence_requests') && Schema::hasColumn('absence_requests', 'schedule_id')) {
            Schema::table('absence_requests', function (Blueprint $table) {
                try { $table->dropForeign(['schedule_id']); } catch (Throwable $e) { /* no foreign key on older schemas */ }
                $table->dropColumn('schedule_id');
            });
        }
    }

    public function down(): void
    {
        // Removed fields intentionally stay removed; restoring them would recreate
        // undocumented UCD fields without the original data.
    }
};
