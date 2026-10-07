<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\ShiftController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\RestDayRequestController;
use App\Http\Controllers\API\AbsenceRequestController;
use App\Http\Controllers\API\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes (Require Authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Auth User Routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Employees
    Route::apiResource('employees', EmployeeController::class);

    // Shifts
    Route::apiResource('shifts', ShiftController::class);

    // Schedules
    Route::apiResource('schedules', ScheduleController::class);

    // Rest Day Requests
    Route::apiResource('rest-day-requests', RestDayRequestController::class);

    // Absence Requests
    Route::apiResource('absence-requests', AbsenceRequestController::class);

    // Notifications
    Route::apiResource('notifications', NotificationController::class);
});
