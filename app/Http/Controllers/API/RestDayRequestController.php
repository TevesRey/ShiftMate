<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestDayRequestsRequest;
use App\Http\Requests\UpdateRestDayRequestsRequest;
use App\Models\Employees;
use App\Models\Notifications;
use App\Models\User;
use App\Models\RestDayRequests;
use App\Services\RestDayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class RestDayRequestController extends Controller
{
    public function __construct(private RestDayService $rests)
    {
    }

    public function index()
    {
        $query = RestDayRequests::with(['employee.user:id,full_name,email', 'reviewer:id,full_name']);
        if (strtolower((string) auth()->user()->role) === 'employee') {
            $employee = Employees::where('user_id', auth()->id())->first();
            $query->where('employee_id', $employee?->id ?? 0);
        }

        return response()->json($query->orderByDesc('created_at')->orderByDesc('id')->get());
    }

    public function store(StoreRestDayRequestsRequest $request)
    {
        $data = $request->validated();
        $isEmployee = strtolower((string) auth()->user()->role) === 'employee';
        $employee = $isEmployee
            ? Employees::where('user_id', auth()->id())->first()
            : Employees::find($data['employee_id'] ?? 0);

        if ($isEmployee && ! $employee) {
            return response()->json(['message' => 'Your account is not linked to an employee profile. Please contact an administrator.'], 422);
        }

        // The employee's current rest day always comes from the server, never from the browser.
        if ($employee) {
            $currentRestDay = $this->rests->currentFor((int) $employee->user_id);
            if (! $currentRestDay) {
                return response()->json(['message' => 'No rest day is currently assigned to your account. Please contact your administrator.'], 422);
            }
            $data['employee_id'] = (string) $employee->id;
            $data['current_rest_day'] = $currentRestDay;
        }

        if (($data['current_rest_day'] ?? null) === ($data['requested_rest_day'] ?? null)) {
            return response()->json(['message' => 'The requested rest day must be different from the current assigned rest day.'], 422);
        }

        $this->rests->assertAssignable((string) $data['requested_rest_day'], 'requested_rest_day');

        // An employee may send only one rest-day change request every 7 days.
        $lastRequest = RestDayRequests::where('employee_id', $data['employee_id'])->orderByDesc('created_at')->first();
        if ($lastRequest && $lastRequest->created_at && $lastRequest->created_at->gt(now()->subDays(7))) {
            $nextDate = $lastRequest->created_at->copy()->addDays(7)->setTimezone(RestDayService::TIMEZONE)->format('F j, Y');
            return response()->json([
                'message' => 'You can only request a rest-day change once a week. You can send another request on ' . $nextDate . '.',
            ], 422);
        }

        $data['status'] = 'pending';
        unset($data['reviewed_by'], $data['reviewed_at']);

        $requestData = RestDayRequests::create($data)->load(['employee.user', 'reviewer']);
        $sender = auth()->user();
        $employeeUserId = (int) ($employee->user_id ?? $sender->id);
        $employeeName = $sender->full_name ?: $sender->name ?: $sender->email;
        $date = $requestData->requested_rest_day ?? $requestData->request_rest_day ?? 'Not specified';
        $message = 'Employee user ID: ' . $employeeUserId . ' | Employee: ' . $employeeName . ' | Current rest day: ' . ($requestData->current_rest_day ?? 'Not specified') . ' | Requested rest day: ' . $date . ' | Reason: ' . ($requestData->reason ?? 'Not specified');
        User::whereIn('role', ['admin', 'manager'])->get()->each(function ($admin) use ($message) {
            Notifications::create(['user_id' => $admin->id, 'title' => 'New rest-day request', 'message' => $message, 'type' => 'rest_day_request', 'is_read' => false]);
        });

        return response()->json($requestData, 201);
    }

    public function show($id)
    {
        $requestData = RestDayRequests::with(['employee.user', 'reviewer'])->findOrFail($id);
        $this->ensureRequestVisible($requestData);

        return response()->json($requestData);
    }

    public function update(UpdateRestDayRequestsRequest $request, $id)
    {
        $this->ensureStaffCanReview();
        $requestData = RestDayRequests::findOrFail($id);
        $data = $request->validated();
        $oldStatus = strtolower((string) $requestData->status);
        $newStatus = isset($data['status']) ? strtolower((string) $data['status']) : null;

        if ($newStatus !== null && $oldStatus !== 'pending' && $newStatus !== $oldStatus) {
            return response()->json(['message' => 'This request was already ' . $oldStatus . ' and can no longer be changed.'], 422);
        }
        if ($newStatus !== null) {
            $data['reviewed_by'] = auth()->id();
            $data['reviewed_at'] = now();
        }

        $employeeUserId = $requestData->employee?->user_id ?? Employees::whereKey($requestData->employee_id)->value('user_id');
        $requestedDate = substr((string) ($requestData->requested_rest_day ?? $requestData->request_rest_day), 0, 10);

        DB::transaction(function () use ($requestData, $data, $newStatus, $oldStatus, $employeeUserId, $requestedDate) {
            $requestData->update($data);

            if ($newStatus === 'approved' && $oldStatus !== 'approved' && $employeeUserId && $requestedDate) {
                // The approved date becomes the employee's current rest day. An approved request is
                // the only thing allowed to replace an already-locked shift on that date.
                $this->rests->assign((int) $employeeUserId, $requestedDate, auth()->id(), true);
            }
        });

        if ($newStatus !== null && $newStatus !== $oldStatus && $employeeUserId) {
            $date = $requestedDate ?: 'the requested date';
            $this->rests->notify(
                (int) $employeeUserId,
                'Rest-day request ' . $newStatus,
                'Your rest-day change from ' . ($requestData->current_rest_day ?? 'your current rest day') . ' to ' . $date . ' was ' . $newStatus . '.' . ($newStatus === 'approved' ? ' This is now your current rest day.' : ''),
                'rest_day_decision'
            );
        }

        return response()->json($requestData->load(['employee.user', 'reviewer']));
    }

    public function destroy($id)
    {
        $this->ensureStaffCanReview();
        RestDayRequests::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    private function ensureRequestVisible(RestDayRequests $requestData): void
    {
        if (strtolower((string) auth()->user()->role) === 'employee') {
            $employeeId = Employees::where('user_id', auth()->id())->value('id');
            abort_unless((int) $requestData->employee_id === (int) $employeeId, 403, 'You can only view your own requests.');
        }
    }

    private function ensureStaffCanReview(): void
    {
        abort_unless(in_array(strtolower((string) auth()->user()->role), ['admin', 'manager'], true), 403, 'Only administrators can review requests.');
    }
}
