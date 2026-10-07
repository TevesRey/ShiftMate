<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchedulesRequest;
use App\Http\Requests\UpdateSchedulesRequest;
use App\Models\Schedules;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        return response()->json(Schedules::with('user', 'shifts')->get(), 200);
    }

    public function store(StoreSchedulesRequest $request)
    {
        $schedule = Schedules::create($request->validated());
        return response()->json($schedule, 201);
    }

    public function show($id)
    {
        $schedule = Schedules::with('user', 'shifts')->findOrFail($id);
        return response()->json($schedule, 200);
    }

    public function update(UpdateSchedulesRequest $request, $id)
    {
        $schedule = Schedules::findOrFail($id);
        $schedule->update($request->validated());
        return response()->json($schedule, 200);
    }

    public function destroy($id)
    {
        Schedules::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
