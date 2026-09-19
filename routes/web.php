<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceRecordController;


Route::get('/', function () {
    return view('welcome');
});
Route::get('/attendance', [AttendanceRecordController::class, 'index'])->name('attendance.index');
Route::post('/attendance', [AttendanceRecordController::class, 'store'])->name('attendance.store');
