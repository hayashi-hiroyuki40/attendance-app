<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $applications = AttendanceCorrection::with('attendanceRecord')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedApplications = $applications->map(function ($app) {
            return [
                'id' => $app->attendance_record_id,
                'approval_status' => $app->status === 'pending' ? '承認待ち' : '承認済み',
                'date' => Carbon::parse($app->attendanceRecord->date)->format('Y/m/d'),
                'comment' => $app->reason,
                'application_date' => Carbon::parse($app->created_at)->format('Y/m/d'),
            ];
        });

        return view('user.application-list', compact('user', 'formattedApplications'));
    }
}
