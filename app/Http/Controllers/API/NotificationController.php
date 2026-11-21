<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNotificationRequest;
use App\Http\Requests\UpdateNotificationRequest;
use App\Models\Notifications;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        return response()->json(
            Notifications::where('user_id', auth()->id())->orderByDesc('created_at')->limit(60)->get(),
            200
        );
    }

    public function store(StoreNotificationRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $notification = Notifications::create($data);
        return response()->json($notification, 201);
    }



    public function reply(Request $request, $id)
    {
        abort_unless($this->isStaff(), 403, 'Only administrators can reply to employee requests.');
        $validated = $request->validate(['message' => ['required', 'string', 'max:1000']]);
        $notification = Notifications::findOrFail($id);
        $this->ensureCanAccess($notification);
        abort_unless(in_array($notification->type, ['absence_request', 'rest_day_request'], true), 422, 'This notification is not a request.');
        if (! preg_match('/Employee user ID: (\d+)/', $notification->message, $matches)) {
            return response()->json(['message' => 'Could not identify the employee for this request.'], 422);
        }
        $employee = User::findOrFail((int) $matches[1]);
        $sender = auth()->user();
        $senderName = $sender->full_name ?: $sender->name ?: $sender->email;
        $reply = Notifications::create([
            'user_id' => $employee->id,
            'title' => 'Reply from ' . $senderName,
            'message' => trim($validated['message']),
            'type' => 'request_reply',
            'is_read' => false,
        ]);
        return response()->json(['message' => 'Reply sent.', 'notification' => $reply], 201);
    }

    public function show($id)
    {
        $notification = Notifications::findOrFail($id);
        $this->ensureCanAccess($notification);
        return response()->json($notification, 200);
    }

    public function update(UpdateNotificationRequest $request, $id)
    {
        $notification = Notifications::findOrFail($id);
        $this->ensureCanAccess($notification);
        $data = $request->validated();
        unset($data['user_id'], $data['title'], $data['message'], $data['type']);
        $notification->update($data);
        return response()->json($notification, 200);
    }

    public function destroy($id)
    {
        $notification = Notifications::findOrFail($id);
        $this->ensureCanAccess($notification);
        $notification->delete();
        return response()->json(null, 204);
    }

    private function isStaff(): bool
    {
        return in_array(strtolower((string) auth()->user()->role), ['admin', 'manager'], true);
    }

    private function ensureCanAccess(Notifications $notification): void
    {
        abort_unless((int) $notification->user_id === (int) auth()->id(), 403, 'You cannot access another user’s notification.');
    }
}
