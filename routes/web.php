<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminCorrectionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\StaffAttendanceController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])->group(function () {
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.index');
    Route::post('/attendance', [AttendanceRecordController::class, 'store'])->name('attendance.store');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');

    Route::get('/stamp_correction_request/list', [AttendanceCorrectionController::class, 'index'])->middleware('check.role:AdminCorrectionController')->name('stamp_correction_request.list');
});
Route::middleware(['auth', 'can:admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');

    Route::get('/admin/staff/list', [StaffAttendanceController::class, 'index'])->name('admin.staff.index');
    Route::get('/admin/attendance/staff/{id}', [StaffAttendanceController::class, 'show'])->name('admin.staff.show');

    Route::get('/stamp_correction_request/list', [AdminCorrectionController::class, 'index'])->name('admin.correction.index');

    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionController::class, 'show'])->name('admin.correction.show');

    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionController::class, 'update'])->name('admin.correction.update');
});
