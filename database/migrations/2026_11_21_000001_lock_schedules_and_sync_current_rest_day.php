<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Schedules become locked as soon as an administrator assigns them.
        if (Schema::hasTable('schedules') && ! Schema::hasColumn('schedules', 'locked_at')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->timestamp('locked_at')->nullable();
            });
        }
        if (Schema::hasTable('schedules')) {
            // Everything that was already assigned is now locked too.
            DB::table('schedules')->whereNull('locked_at')->update(['locked_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)')]);

            // Time in / time out is automatic: fill any shift row that has no times
            // from its shift template (rest-day rows intentionally keep no times).
            $shifts = DB::table('shifts')->get()->keyBy('id');
            DB::table('schedules')
                ->where(fn ($q) => $q->whereNull('start_time')->orWhereNull('end_time'))
                ->chunkById(200, function ($rows) use ($shifts) {
                    foreach ($rows as $row) {
                        $shift = $shifts[$row->shift_id] ?? null;
                        if (! $shift) continue;
                        $name = strtolower(trim((string) $shift->shift_name));
                        if (in_array($name, ['restday', 'rest day'], true)) continue;
                        DB::table('schedules')->where('id', $row->id)->update([
                            'start_time' => $row->start_time ?: $shift->start_time,
                            'end_time' => $row->end_time ?: $shift->end_time,
                        ]);
                    }
                });
        }

        // 2) rest_day_assignments becomes the employee's *current rest day* pointer.
        //    Backfill it from the rest-day rows already on the calendar so nothing is lost.
        if (Schema::hasTable('rest_day_assignments') && Schema::hasTable('schedules') && Schema::hasTable('shifts')) {
            $restShiftIds = DB::table('shifts')->whereRaw('LOWER(shift_name) IN (?, ?)', ['restday', 'rest day'])->pluck('id');
            if ($restShiftIds->isNotEmpty()) {
                $today = now('Asia/Manila')->toDateString();
                $rows = DB::table('schedules')->whereIn('shift_id', $restShiftIds)->orderBy('work_date')->get()->groupBy('user_id');
                foreach ($rows as $userId => $items) {
                    if (DB::table('rest_day_assignments')->where('user_id', $userId)->exists()) continue;
                    $dates = $items->pluck('work_date')->map(fn ($d) => substr((string) $d, 0, 10))->sort()->values();
                    $current = $dates->first(fn ($d) => $d >= $today) ?? $dates->last();
                    DB::table('rest_day_assignments')->insert([
                        'user_id' => $userId,
                        'rest_day' => $current,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schedules') && Schema::hasColumn('schedules', 'locked_at')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropColumn('locked_at');
            });
        }
    }
};
