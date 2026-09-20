<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $records = AttendanceRecord::with('attendanceBreaks')
            ->where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date', 'asc')
            ->get();

        $formattedAttendanceRecords = $records->map(function ($record) {
            $breakMinutes = $record->breaks->sum(function ($b) {
                return match (true) {
                    ! empty($b->break_in) && ! empty($b->break_out) => Carbon::parse($b->break_out)->diffInMinutes(Carbon::parse($b->break_in)),
                    default => 0,
                };
            });

            $totalMinutes = 0;
            if ($record->clock_in && $record->clock_out) {
                $workMinutes = Carbon::parse($record->clock_out)->diffInMinutes(Carbon::parse($record->clock_in));
                $totalMinutes = $workMinutes - $breakMinutes;

                $totalMinutes = $workMinutes - $breakMinutes;

                if ($totalMinutes < 0) {
                    $totalMinutes = 0;
                }
            }

            return [
                'id' => $record->id,
                'date' => Carbon::parse($record->date)->format('m/d'),
                'clock_in' => $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '',
                'clock_out' => $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
                'total_break_time' => $breakMinutes > 0 ? Carbon::today()->addMinutes($breakMinutes) : '',
                'total_time' => $totalMinutes > 0 ? Carbon::today()->addMinutes($totalMinutes) : '',
            ];
        });

        return view('attendance.index', compact('date', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords'));
    }

    public function show(int $id)
    {
        $user = Auth::user();

        $record = AttendanceRecord::with(['attendanceBreaks', 'attendanceCorrections' => fn ($q) => $q->where('status', 'pending')])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $carbonDate = Carbon::parse($record->date);

        $pendingCorrection = $record->attendanceCorrections->first();

        $data = [
            'id' => $record->id,
            'year' => $carbonDate->format('Y年'),
            'date' => $carbonDate->format('n月j日'),
            'clock_in' => $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '',
            'clock_out' => $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
            'comment' => $pendingCorrection->reason ?? '',
            'application' => $pendingCorrection,
            'breaks' => $record->breaks->map(fn ($b) => [
                'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
            ])->toArray(),
        ];

        return view('attendance.detail', compact('user', 'data'));
    }
}
