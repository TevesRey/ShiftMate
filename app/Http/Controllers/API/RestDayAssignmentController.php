<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employees;
use App\Models\RestDayAssignment;
use App\Models\User;
use App\Services\RestDayService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * The employee's CURRENT rest day. Everything goes through RestDayService so the
 * calendar and this record can never disagree.
 */
class RestDayAssignmentController extends Controller
{
    public function __construct(private RestDayService $rests)
    {
    }

    public function index()
    {
        $query = RestDayAssignment::with(['user:id,full_name,email', 'assignedBy:id,full_name']);
        if (strtolower((string) auth()->user()->role) === 'employee') {
            $query->where('user_id', auth()->id());
        }

        return response()->json($query->orderBy('rest_day')->get());
    }

    public function store(Request $request)
    {
        $this->ensureStaff('Only administrators can assign rest days.');
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'rest_day' => ['required', 'date_format:Y-m-d'],
        ]);
        $user = User::findOrFail($data['user_id']);
        if (strtolower((string) $user->role) !== 'employee' || ! Employees::where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['user_id' => ['Choose a linked employee account.']]);
        }

        try {
            $assignment = $this->rests->assign((int) $user->id, $data['rest_day'], auth()->id());
        } catch (ValidationException $e) {
            // The field is called rest_day on this endpoint.
            throw ValidationException::withMessages(['rest_day' => collect($e->errors())->flatten()->all()]);
        }

        $this->rests->notify(
            (int) $user->id,
            'Current rest day updated',
            'Your current rest day is now ' . $assignment->rest_day->format('F j, Y') . '. You can request a change from this date.'
        );

        return response()->json($assignment->load(['user:id,full_name,email', 'assignedBy:id,full_name']), 200);
    }

    public function destroy($id)
    {
        $this->ensureStaff('Only administrators can remove rest-day assignments.');
        $assignment = RestDayAssignment::findOrFail($id);
        $date = $assignment->rest_day->format('F j, Y');
        $userId = (int) $assignment->user_id;

        $this->rests->clear($userId);
        $this->rests->notify($userId, 'Rest day assignment removed', 'Your assigned rest day (' . $date . ') was removed by an administrator.');

        return response()->json(['message' => 'Rest-day assignment removed.'], 200);
    }

    private function ensureStaff(string $message): void
    {
        abort_unless(in_array(strtolower((string) auth()->user()->role), ['admin', 'manager'], true), 403, $message);
    }
}
