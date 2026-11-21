<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeesRequest;
use App\Http\Requests\UpdateEmployeesRequest;
use App\Models\Employees;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    public function index()
    {
        $viewer = auth()->user();

        // Repair older accounts that were created without an employee profile.
        // This makes every employee-role account visible to administrators.
        if (strtolower((string) $viewer->role) !== 'employee') {
            User::whereRaw('LOWER(role) = ?', ['employee'])->where('registration_status', 'approved')->orderBy('id')->get()->each(function (User $user) {
                Employees::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'employee_number' => ((int) Employees::max('employee_number')) + 1,
                        'position' => 'Not set',
                        'status' => 'active',
                    ]
                );
            });
            return response()->json(Employees::with('user')->whereHas('user', function ($query) { $query->where('registration_status', 'approved'); })->orderBy('id')->get(), 200);
        }

        // Employee accounts can see a safe team directory, without other
        // employees' contact numbers or email addresses.
        $team = Employees::with('user:id,full_name')
            ->whereHas('user', function ($query) { $query->where('registration_status', 'approved'); })
            ->orderBy('id')
            ->get()
            ->map(function (Employees $employee) {
                return [
                    'id' => (string) $employee->id,
                    'user_id' => (string) $employee->user_id,
                    'employee_number' => (string) $employee->employee_number,
                    'position' => $employee->position,
                    'status' => $employee->status,
                    'user' => $employee->user ? [
                        'full_name' => $employee->user->full_name,
                    ] : null,
                ];
            });

        return response()->json($team, 200);
    }

    public function store(StoreEmployeesRequest $request)
    {
        $this->ensureStaffCanManage();
        $data = $request->validated();
        $data['status'] = $data['status'] ?? 'active';
        $employee = Employees::create($data);
        return response()->json($employee->load('user'), 201);
    }

    public function show($id)
    {
        $employee = Employees::with('user')->findOrFail($id);
        if (strtolower((string) auth()->user()->role) === 'employee') {
            abort_unless((int) $employee->user_id === (int) auth()->id(), 403, 'You can only view your own employee profile.');
            return response()->json([
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
                'status' => $employee->status,
                'user' => $employee->user ? [
                    'id' => $employee->user->id,
                    'full_name' => $employee->user->full_name,
                    'email' => $employee->user->email,
                ] : null,
            ], 200);
        }
        return response()->json($employee->load('user'), 200);
    }

    public function update(UpdateEmployeesRequest $request, $id)
    {
        $this->ensureStaffCanManage();
        $employee = Employees::findOrFail($id);
        $currentPosition = trim((string) $employee->position);
        if ($currentPosition !== '' && strtolower($currentPosition) !== 'not set') {
            return response()->json([
                'message' => 'This employee already has a position. Positions can only be set once and cannot be edited afterward.',
            ], 422);
        }

        $employee->update($request->validated());
        return response()->json($employee->load('user'), 200);
    }

    public function destroy($id)
    {
        $this->ensureStaffCanManage();
        Employees::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    private function ensureStaffCanManage(): void
    {
        abort_unless(in_array(strtolower((string) auth()->user()->role), ['admin', 'manager'], true), 403, 'Only administrators can manage employee records.');
    }
}
