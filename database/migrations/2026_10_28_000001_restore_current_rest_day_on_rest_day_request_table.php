<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rest_day_request')) {
            return;
        }

        if (! Schema::hasColumn('rest_day_request', 'current_rest_day')) {
            Schema::table('rest_day_request', function (Blueprint $table) {
                $table->date('current_rest_day')->nullable()->after('employee_id');
            });
        }

        // Preserve existing requests by using their originally requested date as
        // a best-effort historical value when no previous current date was stored.
        if (Schema::hasColumn('rest_day_request', 'requested_rest_day')) {
            DB::table('rest_day_request')
                ->whereNull('current_rest_day')
                ->update(['current_rest_day' => DB::raw('requested_rest_day')]);
        }
    }

    public function down(): void
    {
        // Keep the column to avoid deleting rest-day history on rollback.
    }
};
