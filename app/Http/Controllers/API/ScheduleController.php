<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchedulesRequest;
use App\Http\Requests\UpdateSchedulesRequest;
use App\Models\Schedules;
use App\Models\Notifications;
use App\Models\Employees;
use App\Models\AbsenceRequests;
use App\Models\RestDayRequests;
use App\Services\RestDayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function __construct(private RestDayService $rests)
    {
    }

    public function index()
    {
        $query = Schedules::query()->with([
            'user:id,full_name,email,role',
            'shifts:id,shift_name,start_time,end_time,description',
        ]);

        if (strtolower((string) auth()->user()->role) === 'employee') {
            $query->where('user_id', auth()->id());
        }

        return response()->json($query->orderBy('work_date')->orderBy('id')->get());
    }

    public function store(StoreSchedulesRequest $request)
    {
        $this->ensureStaffCanManage();
        $data = $request->validated();
        $shift = \App\Models\Shifts::findOrFail($data['shift_id']);
        $isRestDay = RestDayService::isRestShift($shift);
        $userId = (int) $data['user_id'];

        if ($isRestDay) {
            // The rest day becomes the employee's CURRENT rest day (calendar + pointer in one step).
            $this->rests->assertAssignable((string) $data['work_date']);
            $this->rests->assign($userId, (string) $data['work_date'], auth()->id());
            $schedule = Schedules::where('user_id', $userId)->whereDate('work_date', (string) $data['work_date'])->firstOrFail();
            $this->rests->notify(
                $userId,
                'Current rest day updated',
                'Your current rest day is now ' . date('F j, Y', strtotime($data['work_date'])) . '. You can request a change from this date.'
            );
        } else {
            $this->ensureDateIsSchedulable($userId, (string) $data['work_date']);

            $schedule = DB::transaction(function () use ($data, $shift, $userId) {
                if (Schedules::where('user_id', $userId)->whereDate('work_date', (string) $data['work_date'])->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages([
                        'work_date' => ['This employee already has a locked schedule for that date.'],
                    ]);
                }

                // Time in / time out are picked by the administrator from the dropdowns
                // (defaulting to the shift template) and locked the moment it is assigned.
                $timeIn = ! empty($data['start_time']) ? date('H:i:s', strtotime($data['start_time'])) : $shift->start_time;
                $timeOut = ! empty($data['end_time']) ? date('H:i:s', strtotime($data['end_time'])) : $shift->end_time;

                return Schedules::create([
                    'user_id' => $userId,
                    'shift_id' => $shift->id,
                    'work_date' => $data['work_date'],
                    'start_time' => $timeIn,
                    'end_time' => $timeOut,
                    'status' => $data['status'] ?? 'active',
                ] + RestDayService::lockAttributes());
            });
        }

        $schedule->load(['user', 'shifts']);
        $this->sendScheduleEmail($schedule, $isRestDay ? 'Your rest day has been assigned.' : 'Your work schedule has been added.');
        if (! $isRestDay) $this->notifyEmployeeScheduleUpdate($schedule, 'Your work schedule has been added and locked.');

        return response()->json($schedule, 201);
    }

    public function show($id)
    {
        $schedule = Schedules::with(['user', 'shifts'])->findOrFail($id);
        $this->ensureScheduleVisible($schedule);

        return response()->json($schedule);
    }

    public function update(UpdateSchedulesRequest $request, $id)
    {
        $this->ensureStaffCanManage();
        $schedule = Schedules::findOrFail($id);

        // Every assigned schedule is locked, including its automatic time in / time out.
        if ($schedule->locked_at !== null) {
            abort(403, 'This schedule is locked after assignment and cannot be edited.');
        }

        $data = $request->validated();
        unset($data['start_time'], $data['end_time'], $data['locked_at']);
        $schedule->update($data);

        return response()->json($schedule->load(['user', 'shifts']));
    }

    public function destroy($id)
    {
        $this->ensureStaffCanManage();
        Schedules::findOrFail($id);
        abort(403, 'Schedules cannot be deleted after assignment.');
    }

    private function ensureStaffCanManage(): void
    {
        if (! in_array(strtolower((string) auth()->user()->role), ['admin', 'manager'], true)) {
            abort(403, 'Only administrators can manage schedules.');
        }
    }

    private function ensureScheduleVisible(Schedules $schedule): void
    {
        if (strtolower((string) auth()->user()->role) === 'employee' && (int) $schedule->user_id !== (int) auth()->id()) {
            abort(403, 'You can only view your own schedules.');
        }
    }



    /** Prevent assigning a shift on a date already approved for absence or rest day. */
    private function ensureDateIsSchedulable(int $userId, string $workDate): void
    {
        // Past dates cannot receive new schedules, even if the request bypasses the browser UI.
        if ($workDate < RestDayService::today()) {
            throw ValidationException::withMessages([
                'work_date' => ['Past dates are closed. You can only assign schedules for today or a future date.'],
            ]);
        }

        // Sundays are closed for all employees; no shift can be assigned.
        if ((int) date('w', strtotime($workDate)) === 0) {
            throw ValidationException::withMessages([
                'work_date' => ['Sunday is a closed day. No shifts can be assigned.'],
            ]);
        }

        if ($this->philippineHolidayName($workDate)) {
            throw ValidationException::withMessages([
                'work_date' => ['This date is a Philippine public holiday and is closed. No shifts can be assigned.'],
            ]);
        }

        $employeeId = Employees::where('user_id', $userId)->value('id');
        if (! $employeeId) {
            return;
        }

        $approvedAbsence = AbsenceRequests::where('employee_id', $employeeId)
            ->whereDate('absence_date', $workDate)
            ->whereRaw('LOWER(status) = ?', ['approved'])
            ->exists();

        if ($approvedAbsence) {
            throw ValidationException::withMessages([
                'work_date' => ["Cannot assign a schedule on {$workDate}: this employee has an approved absence for that date."],
            ]);
        }
    }

    private function philippineHolidayName(string $workDate): ?string
    {
        return RestDayService::holidayName($workDate);
    }

    private function notifyEmployeeScheduleUpdate(Schedules $schedule, string $message): void
    {
        $employee = $schedule->user;
        if (! $employee) return;
        $shiftName = $schedule->shifts?->shift_name ?? 'Assigned shift';
        Notifications::create([
            'user_id' => $employee->id,
            'title' => 'Schedule updated',
            'message' => $message . ' Shift: ' . $shiftName . '. Date: ' . $schedule->work_date . '.',
            'type' => 'schedule_update',
            'is_read' => false,
        ]);
    }

    /** Send a schedule email without blocking schedule management if mail is unavailable. */
    private function sendScheduleEmail(Schedules $schedule, string $message): void
    {
        $schedule->loadMissing(['user', 'shifts']);
        $employee = $schedule->user;
        if (! $employee || ! $employee->email) {
            return;
        }

        $shiftName = $schedule->shifts?->shift_name ?? 'Assigned shift';
        $body = "Hello {$employee->full_name},\n\n{$message}\n\nShift: {$shiftName}\nDate: {$schedule->work_date}\nStatus: {$schedule->status}\n\nPlease sign in to ShiftMate to view your schedule.\n\n- ShiftMate";

        try {
            Mail::raw($body, function ($mail) use ($employee) {
                $mail->to($employee->email)->subject('ShiftMate schedule update');
            });
        } catch (\Throwable $exception) {
            Log::warning('ShiftMate schedule email could not be sent.', [
                'schedule_id' => $schedule->id,
                'user_id' => $employee->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
