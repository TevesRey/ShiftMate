<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbsenceRequest;
use App\Http\Requests\UpdateAbsenceRequest;
use App\Models\AbsenceRequests;
use App\Models\Employees;
use App\Models\Notifications;
use App\Models\User;
use Illuminate\Http\Request;

class AbsenceRequestController extends Controller
{
    public function index()
    {
        $query = AbsenceRequests::with([
            'employee.user:id,full_name,email',
            'reviewer:id,full_name',
        ]);
        if (strtolower((string) auth()->user()->role) === 'employee') {
            $employee = Employees::where('user_id', auth()->id())->first();
            $query->where('employee_id', $employee?->id ?? 0);
        }

        return response()->json($query->orderByDesc('created_at')->orderByDesc('id')->get());
    }

    public function store(StoreAbsenceRequest $request)
    {
        $data = $request->validated();
        if (strtolower((string) auth()->user()->role) === 'employee') {
            $employee = Employees::where('user_id', auth()->id())->first();
            if (! $employee) {
                return response()->json(['message' => 'Your account is not linked to an employee profile. Please contact an administrator.'], 422);
            }
            $data['employee_id'] = (string) $employee->id;
        }
        $data['status'] = 'pending';
        unset($data['reviewed_by'], $data['reviewed_at']);

        $absence = AbsenceRequests::create($data)->load(['employee.user', 'reviewer']);
        $sender = auth()->user();
        $employeeUserId = (int) $sender->id;
        $employeeName = $sender->full_name ?: $sender->name ?: $sender->email;
        $message = 'Employee user ID: ' . $employeeUserId . ' | Employee: ' . $employeeName . ' | Date: ' . ($absence->absence_date ?? 'Not specified') . ' | Reason: ' . ($absence->description ?? 'Not specified');
        User::whereIn('role', ['admin', 'manager'])->get()->each(function ($admin) use ($message) {
            Notifications::create(['user_id' => $admin->id, 'title' => 'New absence request', 'message' => $message, 'type' => 'absence_request', 'is_read' => false]);
        });

        return response()->json($absence, 201);
    }

    public function show($id)
    {
        $absence = AbsenceRequests::with(['employee.user', 'reviewer'])->findOrFail($id);
        $this->ensureRequestVisible($absence);

        return response()->json($absence);
    }

    public function update(UpdateAbsenceRequest $request, $id)
    {
        $this->ensureStaffCanReview();
        $absence = AbsenceRequests::findOrFail($id);
        $data = $request->validated();
        $oldStatus = strtolower((string) $absence->status);
        if (array_key_exists('status', $data)) {
            $data['reviewed_by'] = auth()->id();
            $data['reviewed_at'] = now();
        }
        $absence->update($data);
        if (isset($data['status']) && strtolower((string) $data['status']) !== $oldStatus) {
            $employeeUserId = $absence->employee?->user_id ?? Employees::whereKey($absence->employee_id)->value('user_id');
            if ($employeeUserId) {
                $approved = strtolower((string) $data['status']) === 'approved';
                Notifications::create([
                    'user_id' => $employeeUserId,
                    'title' => 'Absence request ' . strtolower((string) $data['status']),
                    'message' => $approved
                        ? 'Your absence for ' . ($absence->absence_date ?? 'the requested date') . ' was approved. Your schedule will show Absent for that date.'
                        : 'Your absence request for ' . ($absence->absence_date ?? 'the requested date') . ' was ' . strtolower((string) $data['status']) . '.',
                    'type' => 'absence_decision',
                    'is_read' => false,
                ]);
            }
        }
        return response()->json($absence->load(['employee.user', 'reviewer']));
    }

    public function destroy($id)
    {
        $this->ensureStaffCanReview();
        AbsenceRequests::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    private function ensureRequestVisible(AbsenceRequests $absence): void
    {
        if (strtolower((string) auth()->user()->role) === 'employee') {
            $employeeId = Employees::where('user_id', auth()->id())->value('id');
            abort_unless((int) $absence->employee_id === (int) $employeeId, 403, 'You can only view your own requests.');
        }
    }

    private function ensureStaffCanReview(): void
    {
        abort_unless(in_array(strtolower((string) auth()->user()->role), ['admin', 'manager'], true), 403, 'Only administrators can review requests.');
    }
}
