<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Requests\UpdateNotificationRequest;
use App\Models\Notifications;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        // Get notifications for the authenticated user
        return response()->json(Notifications::where('user_id', auth()->id())->get(), 200);
    }

    public function store(StoreNotificationRequest $request)
    {
        $notification = Notifications::create($request->validated());
        return response()->json($notification, 201);
    }

    public function show($id)
    {
        $notification = Notifications::findOrFail($id);
        return response()->json($notification, 200);
    }

    public function update(UpdateNotificationRequest $request, $id)
    {
        $notification = Notifications::findOrFail($id);
        $notification->update($request->validated());
        return response()->json($notification, 200);
    }

    public function destroy($id)
    {
        Notifications::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
