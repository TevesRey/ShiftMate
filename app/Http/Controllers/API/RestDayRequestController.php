<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestDayRequestsRequest;
use App\Http\Requests\UpdateRestDayRequestsRequest;
use App\Models\RestDayRequests;
use Illuminate\Http\Request;

class RestDayRequestController extends Controller
{
    public function index()
    {
        return response()->json(RestDayRequests::with('employee')->get(), 200);
    }

    public function store(StoreRestDayRequestsRequest $request)
    {
        $requestData = RestDayRequests::create($request->validated());
        return response()->json($requestData, 201);
    }

    public function show($id)
    {
        $requestData = RestDayRequests::with('employee')->findOrFail($id);
        return response()->json($requestData, 200);
    }

    public function update(UpdateRestDayRequestsRequest $request, $id)
    {
        $requestData = RestDayRequests::findOrFail($id);
        $requestData->update($request->validated());
        return response()->json($requestData, 200);
    }

    public function destroy($id)
    {
        RestDayRequests::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
