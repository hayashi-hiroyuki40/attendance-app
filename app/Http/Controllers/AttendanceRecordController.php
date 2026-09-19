<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceRecordController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $today = Carbon::today();

        $week = ['日', '月', '火', '水', '木', '金', '土'];
        $formattedDate = $now->format("Y年n月j日({$week[$now->dayOfWeek]})");
        $formattedTime = $now->format('H:i');

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $attendanceStatus = $attendance ? $attendance->status : '勤務外';

        $user->attendance_status = $attendanceStatus;

        return view('attendance.register', compact('user', 'formattedDate', 'formattedTime'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $currentStatus = $attendance ? $attendance->status : '勤務外';
        $action = $request->input('action');

        switch ($action) {
            case 'clock_in':
                if ($currentStatus === '勤務外' && !$attendance) {
                    AttendanceRecord::create([
                        'user_id' => $user->id,
                        'date' => $today,
                        'clock_in' => $now,
                        'status' => '出勤中',
                    ]);
                }
                break;

            case 'break_in':
                if ($currentStatus === '出勤中' && $attendance) {
                    $attendance->attendanceBreaks()->create([
                        'break_in' => $now,
                    ]);
                    $attendance->update(['status' => '休憩中']);
                }
                break;

            case 'break_out':
                if ($currentStatus === '休憩中' && $attendance) {
                    $latestBreak = $attendance->attendanceBreaks()
                        ->whereNull('break_out')
                        ->latest()
                        ->first();

                    if ($latestBreak) {
                        $latestBreak->update(['break_out' => $now]);
                        $attendance->update(['status' => '出勤中']);
                    }
                }
                break;

            case 'clock_out':
                if ($currentStatus === '出勤中' && $attendance) {
                    $attendance->update([
                        'clock_out' => $now,
                        'status' => '退勤済',
                    ]);

                    return redirect()->back()->with('message', 'お疲れ様でした。');
                }
                break;
        }

        return redirect()->back();
    }
}
