<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\CorrectionBreak;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            $breakMinutes = $record->attendanceBreaks->sum(function ($b) {
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

        return view('user.user-attendance-list', compact('date', 'previousMonth', 'nextMonth', 'formattedAttendanceRecords'));
    }

    public function show(int $id)
    {
        $user = Auth::user();

        $record = AttendanceRecord::with(['attendanceBreaks', 'attendanceCorrections' => fn($q) => $q->where('status', 'pending')->with('correctionsBreaks')])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $carbonDate = Carbon::parse($record->date);

        $pendingCorrection = $record->attendanceCorrections->first();

        if ($pendingCorrection) {
            $data = [
                'id' => $record->id,
                'year' => $carbonDate->format('Y年'),
                'date' => $carbonDate->format('n月j日'),
                'clock_in' => $pendingCorrection->clock_in ? Carbon::parse($pendingCorrection->clock_in)->format('H:i') : '',
                'clock_out' => $pendingCorrection->clock_out ? Carbon::parse($pendingCorrection->clock_out)->format('H:i') : '',
                'comment' => $pendingCorrection->reason ?? '',
                'application' => $pendingCorrection,
                'breaks' => $pendingCorrection->correctionsBreaks->map(fn($b) => [
                    'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                    'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
                ])->toArray(),
            ];
        } else {
            $data = [
                'id' => $record->id,
                'year' => $carbonDate->format('Y年'),
                'date' => $carbonDate->format('n月j日'),
                'clock_in' => $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '',
                'clock_out' => $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
                'comment' => $pendingCorrection->reason ?? '',
                'application' => $pendingCorrection,
                'breaks' => $record->attendanceBreaks->map(fn($b) => [
                    'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                    'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
                ])->toArray(),
            ];
        }

        return view('user.user-detail', compact('user', 'data'));
    }

    public function store(AttendanceCorrectionRequest $request, int $id)
    {
        $validated = $request->validated();

        $record = AttendanceRecord::where('user_id', Auth::id())->findOrFail($id);

        if ($record->attendanceCorrections()->where('status', '承認待ち')->exists()) {
            return back()->withErrors(['status' => '承認待ちのため修正できません。']);
        }

        DB::transaction(function () use ($validated, $record) {
            $dateStr = Carbon::parse($record->date)->format('Y-m-d');

            $clockIn = $validated['clock_in'] ?? $validated['new_clock_in'];
            $clockOut = $validated['clock_out'] ?? $validated['new_clock_out'];

            $correction = AttendanceCorrection::create([
                'attendance_record_id' => $record->id,
                'user_id' => Auth::id(),
                'clock_in' => Carbon::parse($dateStr . ' ' . $clockIn),
                'clock_out' => Carbon::parse($dateStr . ' ' . $clockOut),
                'comment' => $validated['comment'],
                'status' => '承認待ち',
            ]);

            $breakIns = $validated['break_in'] ?? $validated['new_break_in'] ?? [];
            $breakOuts = $validated['break_out'] ?? $validated['new_break_out'] ?? [];

            if (! empty($breakIns) && is_array($breakIns)) {
                foreach ($breakIns as $index => $breakIn) {
                    $breakOut = $breakOuts[$index] ?? null;

                    if (! empty($breakIn) && ! empty($breakOut)) {
                        CorrectionBreak::create([
                            'attendance_corrections_id' => $correction->id,
                            'break_in' => Carbon::parse($dateStr . ' ' . $breakIn),
                            'break_out' => Carbon::parse($dateStr . ' ' . $breakOut),
                        ]);
                    }
                }
            }
        });

        return redirect('user/user-application-list');
    }
}
