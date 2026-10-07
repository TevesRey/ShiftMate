<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeesRequest;
use App\Http\Requests\UpdateEmployeesRequest;
use App\Models\Employees;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(Employees::all(), 200);
    }

    public function store(StoreEmployeesRequest $request)
    {
        $employee = Employees::create($request->validated());
        return response()->json($employee, 201);
    }

    public function show($id)
    {
        $employee = Employees::findOrFail($id);
        return response()->json($employee, 200);
    }

    public function update(UpdateEmployeesRequest $request, $id)
    {
        $employee = Employees::findOrFail($id);
        $employee->update($request->validated());
        return response()->json($employee, 200);
    }

    public function destroy($id)
    {
        Employees::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
