<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftsRequest;
use App\Http\Requests\UpdateShifsRequest;
use App\Models\Shifts;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        return response()->json(Shifts::all(), 200);
    }

    public function store(StoreShiftsRequest $request)
    {
        $this->ensureStaffCanManage();
        $shift = Shifts::create($request->validated());
        return response()->json($shift, 201);
    }

    public function show($id)
    {
        $shift = Shifts::findOrFail($id);
        return response()->json($shift, 200);
    }

    public function update(UpdateShifsRequest $request, $id)
    {
        $this->ensureStaffCanManage();
        $shift = Shifts::findOrFail($id);
        $shift->update($request->validated());
        return response()->json($shift, 200);
    }

    public function destroy($id)
    {
        $this->ensureStaffCanManage();
        Shifts::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    private function ensureStaffCanManage(): void
    {
        abort_unless(in_array(strtolower((string) auth()->user()->role), ['admin', 'manager'], true), 403, 'Only administrators can manage shift templates.');
    }
}
