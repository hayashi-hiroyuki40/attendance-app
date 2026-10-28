<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminCorrectionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\StaffAttendanceController;
use App\Http\Requests\AttendanceCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::middleware(['web'])->group(function () {
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create']);
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->middleware(['verified']);
    Route::post('/attendance', [AttendanceRecordController::class, 'store']);

    Route::get('/attendance/report', [AttendanceReportController::class, 'show']);

    Route::get('/attendance/list', [AttendanceController::class, 'index']);
    Route::get('/attendance/{id}', function (Request $request, int $id) {
        $user = $request->user();

        if ($user && ($user->admin_status === true || $user->admin_status === 1 || $user->admin_status === 'true')) {
            return app(AdminAttendanceController::class)->show($id);
        }

        return app(AttendanceController::class)->show($id);
    });

    Route::post('/attendance/{id}', function (AttendanceCorrectionRequest $request, int $id) {
        $user = $request->user();

        if ($user && ($user->admin_status === true || $user->admin_status === 1 || $user->admin_status === 'true')) {
            return app(AdminAttendanceController::class)->update($request, $id);
        }

        return app(AttendanceController::class)->store($request, $id);
    });

    Route::get('/stamp_correction_request/list', function (Request $request) {

        if ($request->user() && $request->user()->admin_status) {
            return app(AdminCorrectionController::class)->index($request);
        }

        return app(AttendanceCorrectionController::class)->index($request);
    });

    Route::get('/application/{id}', [AttendanceCorrectionController::class, 'show']);

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->name('verification.send');
});

Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);

    Route::get('/admin/staff/list', [StaffAttendanceController::class, 'index']);
    Route::get('/admin/attendance/staff/{id}', [StaffAttendanceController::class, 'show']);

    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionController::class, 'show']);

    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionController::class, 'update']);

    Route::post('/export', [AttendanceController::class, 'exportCsv']);
});
