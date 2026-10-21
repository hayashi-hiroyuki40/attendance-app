<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAttendanceController extends Controller
{
    public function index()
    {
        $users = User::where('admin_status', false)->get();

        return view('admin.staff-list', compact('users'));
    }

    public function show(Request $request, int $id)
    {
        $user = User::where('admin_status', false)->findOrFail($id);

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
                return (! empty($b->break_in) && ! empty($b->break_out))
                    ? Carbon::parse($b->break_out)->diffInMinutes(Carbon::parse($b->break_in))
                    : 0;
            });

            $totalMinutes = 0;
            if ($record->clock_in && $record->clock_out) {
                $workMinutes = Carbon::parse($record->clock_out)->diffInMinutes(Carbon::parse($record->clock_in));
                $totalMinutes = max(0, $workMinutes - $breakMinutes);
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

        return view('admin.staff-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }

    public function exportCsv(Request $request)
    {
        $userId = $request->input('user_id');
        $date = Carbon::parse(str_replace('/', '-', $request->input('year_month')));

        $records = AttendanceRecord::with('attendanceBreaks')
            ->where('user_id', $userId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date')
            ->get();

        $fileName = "attendance_{$userId}_{$date->format('Y-m')}.csv";

        return new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            $records->map(function ($r) {
                $breakMins = $r->attendanceBreaks->sum(
                    fn($b) =>
                    $b->break_in && $b->break_out ? Carbon::parse($b->break_in)->diffInMinutes(Carbon::parse($b->break_out)) : 0
                );

                $workMins = ($r->clock_in && $r->clock_out)
                    ? Carbon::parse($r->clock_in)->diffInMinutes(Carbon::parse($r->clock_out)) - $breakMins
                    : 0;

                return [
                    $r->date,
                    $r->clock_in ? Carbon::parse($r->clock_in)->format('G:i') : '',
                    $r->clock_out ? Carbon::parse($r->clock_out)->format('G:i') : '',
                    $breakMins ? sprintf('%d:%02d', intdiv($breakMins, 60), $breakMins % 60) : '',
                    $workMins > 0 ? sprintf('%d:%02d', intdiv($workMins, 60), $workMins % 60) : '',
                ];
            })->each(fn($row) => fputcsv($handle, $row));

            fclose($handle);
        }, 200, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$fileName}\""]);
    }
}
