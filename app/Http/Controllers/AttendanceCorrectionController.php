<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceCorrectionController extends Controller
{
    public function index(Request $request)
    {
        $loginUser = Auth::user();

        $tab = $request->query('tab', 'pending');

        $query = AttendanceCorrection::with(['user', 'attendanceRecord'])
            ->orderBy('created_at', 'desc');

        if ($tab === 'approved') {
            $query->where('status', '承認済み');
        } else {
            $query->where('status', '承認待ち');
        }

        $applications = $query->get();

        $formattedApplications = $applications->map(function ($app) {
            return [
                'id'               => $app->attendance_record_id,
                'approval_status'  => $app->status,
                'date'             => Carbon::parse($app->attendanceRecord->date)->format('Y/m/d'),
                'comment'          => $app->comment,
                'application_date' => Carbon::parse($app->created_at)->format('Y/m/d'),
            ];
        });

        $firstApp = $applications->first();
        $user = ($firstApp && $firstApp->user) ? $firstApp->user : $loginUser;

        return view('user.user-application-list', compact('formattedApplications', 'user', 'tab'));
    }

    public function show($id)
    {
        $application = AttendanceCorrection::with(['user', 'attendanceRecord', 'correctionBreaks'])->findOrFail($id);


        $data = [
            'id'          => $application->attendance_record_id,
            'application' => $application,
            'year'        => Carbon::parse($application->attendanceRecord->date)->format('Y年'),
            'date'        => Carbon::parse($application->attendanceRecord->date)->format('n月j日'),
            'clock_in'    => $application->clock_in ? Carbon::parse($application->clock_in)->format('H:i') : '',
            'clock_out'   => $application->clock_out ? Carbon::parse($application->clock_out)->format('H:i') : '',
            'comment'     => $application->comment,
            'breaks'      => $application->correctionBreaks->map(fn($b) => [
                'break_in'  => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
            ])->toArray(),
        ];

        $user = $application->user;

        return view('user.user-detail', compact('data', 'user'));
    }
}
