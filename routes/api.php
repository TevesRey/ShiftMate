<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\EmployeeController;
use App\Http\Controllers\API\ShiftController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\RestDayRequestController;
use App\Http\Controllers\API\RestDayAssignmentController;
use App\Http\Controllers\API\AbsenceRequestController;
use App\Http\Controllers\API\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Auth Routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['signed', 'throttle:6,1'])->name('api.email.verify');
Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');

// Protected Routes (Require Authentication)
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth User Routes
    Route::get('/admin/registrations/pending', [AuthController::class, 'pendingRegistrations']);
    Route::post('/admin/registrations/{id}/review', [AuthController::class, 'reviewRegistration']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/profile/photo', [AuthController::class, 'uploadProfilePhoto']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    // Employees
    Route::apiResource('employees', EmployeeController::class);

    // Shifts
    Route::apiResource('shifts', ShiftController::class);

    // Schedules
    Route::apiResource('schedules', ScheduleController::class);

    // Rest Day Requests
    Route::apiResource('rest-day-requests', RestDayRequestController::class);

    // Current rest day (one per employee; kept in sync with the schedule calendar)
    Route::apiResource('rest-day-assignments', RestDayAssignmentController::class)->only(['index', 'store', 'destroy']);

    // Absence Requests
    Route::apiResource('absence-requests', AbsenceRequestController::class);

    // Notifications and admin/employee messages
    Route::post('/notifications/{id}/reply', [NotificationController::class, 'reply']);
    Route::apiResource('notifications', NotificationController::class);
});
