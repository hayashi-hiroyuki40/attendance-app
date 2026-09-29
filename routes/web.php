<?php

use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionController;
use App\Http\Controllers\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.index');
Route::post('/attendance', [AttendanceRecordController::class, 'store'])->name('attendance.store');

Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');

Route::get('/stamp_correction_request/list', [AttendanceCorrectionController::class, 'index'])->name('stamp_correction_request.list');

Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.attendance.index');
Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
