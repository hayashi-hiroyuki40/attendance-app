<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.index');
Route::post('/attendance', [AttendanceRecordController::class, 'store'])->name('attendance.store');

Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
