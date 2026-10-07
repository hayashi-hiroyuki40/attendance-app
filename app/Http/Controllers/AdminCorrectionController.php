<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCorrectionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', '承認待ち');

        $applications = AttendanceCorrection::with(['user', 'AttendanceRecord'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.admin-application-list', compact('applications'));
    }

    public function show(int $id)
    {
        $application = AttendanceCorrection::with(['user', 'AttendanceRecord', 'proposalBreaks'])
            ->findOrFail($id);

        $recordDate = Carbon::parse($application->AttendanceRecord->date);
        $application->new_date = $recordDate;
        $application->new_clock_in = $application->clock_in ? Carbon::parse($application->clock_in)->format('H:i') : '';
        $application->new_clock_out = $application->clock_out ? Carbon::parse($application->clock_out)->format('H:i') : '';
        $application->approval_status = $application->status; // '承認待ち' または '承認済み'

        $user = $application->user;

        return view('admin.admin-application-detail', compact('application', 'user'));
    }

    public function update(Request $request, int $id)
    {
        $application = AttendanceCorrection::with(['AttendanceRecord', 'proposalBreaks'])
            ->findOrFail($id);

        DB::transaction(function () use ($application) {
            $application->update([
                'status' => '承認済み',
            ]);

            $record = $application->AttendanceRecord;
            $record->update([
                'clock_in' => $application->clock_in,
                'clock_out' => $application->clock_out,
            ]);

            $record->attendanceBreaks()->delete();
            foreach ($application->proposalBreaks as $break) {
                $record->attendanceBreaks()->create([
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
                ]);
            }
        });

        return redirect('/stamp_correction_request/list');
    }
}
