<?php

namespace App\Services;

use App\Models\Notifications;
use App\Models\RestDayAssignment;
use App\Models\Schedules;
use App\Models\Shifts;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * One place that decides what an employee's CURRENT rest day is.
 *
 * - rest_day_assignments holds exactly one row per employee: their current rest day.
 * - The calendar (schedules) shows that same date with the "Restday" shift.
 * Both are always written together here, so they can never disagree.
 */
class RestDayService
{
    public const TIMEZONE = 'Asia/Manila';

    /** Lock marker for a schedule row (skipped, not crashed, if the lock migration has not been run yet). */
    public static function lockAttributes(): array
    {
        return Schema::hasColumn('schedules', 'locked_at') ? ['locked_at' => now()] : [];
    }

    public static function today(): string
    {
        return now(self::TIMEZONE)->toDateString();
    }

    public static function isRestShift(?Shifts $shift): bool
    {
        return $shift && in_array(strtolower(trim((string) $shift->shift_name)), ['restday', 'rest day'], true);
    }

    /** The Restday shift template; created on demand so approvals never fail because it is missing. */
    public function restShift(): Shifts
    {
        $shift = Shifts::whereRaw('LOWER(shift_name) IN (?, ?)', ['restday', 'rest day'])->first();

        return $shift ?: Shifts::create([
            'shift_name' => 'Restday',
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'description' => 'Rest day — no working hours',
        ]);
    }

    /** IDs of every shift template that means "rest day". */
    public function restShiftIds(): array
    {
        return Shifts::whereRaw('LOWER(shift_name) IN (?, ?)', ['restday', 'rest day'])->pluck('id')->all();
    }

    /** The employee's current rest day (Y-m-d) or null when none was ever assigned. */
    public function currentFor(int $userId): ?string
    {
        $assignment = Schema::hasTable('rest_day_assignments') ? RestDayAssignment::where('user_id', $userId)->first() : null;
        if ($assignment && $assignment->rest_day) {
            return $assignment->rest_day->format('Y-m-d');
        }

        // Fallback for accounts that predate rest_day_assignments.
        $dates = Schedules::where('user_id', $userId)
            ->whereIn('shift_id', $this->restShiftIds())
            ->orderBy('work_date')
            ->pluck('work_date')
            ->map(fn ($d) => substr((string) $d, 0, 10));
        $today = self::today();

        return $dates->first(fn ($d) => $d >= $today) ?? $dates->last();
    }

    /** Throws a validation error when the date can never be a rest day. */
    public function assertAssignable(string $date, string $field = 'work_date'): void
    {
        if ($date < self::today()) {
            throw ValidationException::withMessages([$field => ['Past dates cannot be used as a rest day.']]);
        }
        if ((int) date('w', strtotime($date)) === 0) {
            throw ValidationException::withMessages([$field => ['Sunday is already a closed day, so it cannot be a rest day.']]);
        }
        if (self::holidayName($date)) {
            throw ValidationException::withMessages([$field => ['This date is a Philippine public holiday (closed), so it cannot be a rest day.']]);
        }
    }

    /**
     * Make $date the employee's current rest day.
     *
     * A work shift already on that date is only replaced when $replaceShift is true
     * (used when an administrator approves the employee's rest-day request).
     */
    public function assign(int $userId, string $date, ?int $assignedBy, bool $replaceShift = false): RestDayAssignment
    {
        $this->assertAssignable($date);
        $restShift = $this->restShift();

        return DB::transaction(function () use ($userId, $date, $assignedBy, $replaceShift, $restShift) {
            $existing = Schedules::where('user_id', $userId)->whereDate('work_date', $date)->first();
            if ($existing && (int) $existing->shift_id !== (int) $restShift->id && ! $replaceShift) {
                throw ValidationException::withMessages([
                    'work_date' => ['This employee already has a locked shift on that date. Choose a different rest day.'],
                ]);
            }

            $values = ['shift_id' => $restShift->id, 'start_time' => null, 'end_time' => null, 'status' => 'active'] + self::lockAttributes();
            $schedule = $existing
                ? tap($existing)->update($values)
                : Schedules::create($values + ['user_id' => $userId, 'work_date' => $date]);

            // Only one upcoming rest day per employee; past rest days stay as history.
            Schedules::where('user_id', $userId)
                ->where('id', '!=', $schedule->id)
                ->whereDate('work_date', '>=', self::today())
                ->whereIn('shift_id', $this->restShiftIds())
                ->delete();

            return RestDayAssignment::updateOrCreate(
                ['user_id' => $userId],
                ['rest_day' => $date, 'assigned_by' => $assignedBy]
            );
        });
    }

    /** Remove the current rest day (calendar row for today/future + the pointer). */
    public function clear(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            Schedules::where('user_id', $userId)
                ->whereDate('work_date', '>=', self::today())
                ->whereIn('shift_id', $this->restShiftIds())
                ->delete();
            RestDayAssignment::where('user_id', $userId)->delete();
        });
    }

    public function notify(int $userId, string $title, string $message, string $type = 'rest_day_assignment'): void
    {
        Notifications::create(['user_id' => $userId, 'title' => $title, 'message' => $message, 'type' => $type, 'is_read' => false]);
    }

    /** Approximate recurring national holidays; proclamation-based dates such as Eid must be updated yearly. */
    public static function holidayName(string $workDate): ?string
    {
        $year = (int) date('Y', strtotime($workDate));
        $fixed = [
            "$year-01-01" => "New Year's Day",
            "$year-02-25" => 'EDSA People Power Anniversary',
            "$year-04-09" => 'Araw ng Kagitingan',
            "$year-05-01" => 'Labor Day',
            "$year-06-12" => 'Independence Day',
            "$year-08-21" => 'Ninoy Aquino Day',
            "$year-11-01" => "All Saints' Day",
            "$year-11-02" => "All Souls' Day",
            "$year-11-30" => 'Bonifacio Day',
            "$year-12-08" => 'Feast of the Immaculate Conception',
            "$year-12-25" => 'Christmas Day',
            "$year-12-30" => 'Rizal Day',
            "$year-12-31" => 'Last Day of the Year',
        ];
        if (isset($fixed[$workDate])) return $fixed[$workDate];
        $lastAugust = strtotime("last day of August $year");
        while ((int) date('N', $lastAugust) !== 1) $lastAugust = strtotime('-1 day', $lastAugust);
        if ($workDate === date('Y-m-d', $lastAugust)) return 'National Heroes Day';
        if (function_exists('easter_date')) {
            $easter = easter_date($year);
            foreach ([['-3 days', 'Maundy Thursday'], ['-2 days', 'Good Friday']] as [$offset, $name]) {
                if ($workDate === date('Y-m-d', strtotime($offset, $easter))) return $name;
            }
        }
        return null;
    }
}
