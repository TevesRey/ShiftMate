<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAbsenceRequest;
use App\Http\Requests\UpdateAbsenceRequest;
use App\Models\AbsenceRequests;
use Illuminate\Http\Request;

class AbsenceRequestController extends Controller
{
    public function index()
    {
        return response()->json(AbsenceRequests::with('employee')->get(), 200);
    }

    public function store(StoreAbsenceRequest $request)
    {
        $absence = AbsenceRequests::create($request->validated());
        return response()->json($absence, 201);
    }

    public function show($id)
    {
        $absence = AbsenceRequests::with('employee')->findOrFail($id);
        return response()->json($absence, 200);
    }

    public function update(UpdateAbsenceRequest $request, $id)
    {
        $absence = AbsenceRequests::findOrFail($id);
        $absence->update($request->validated());
        return response()->json($absence, 200);
    }

    public function destroy($id)
    {
        AbsenceRequests::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
