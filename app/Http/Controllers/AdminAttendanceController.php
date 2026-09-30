<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        $users = User::where('role', 'user')->get();
        $attendanceRecords = AttendanceRecord::with('attendanceBreaks')->whereDate('date', $date)->get();

        return view('admin.admin-attendance-list', compact('date', 'previousDay', 'nextDay', 'users', 'attendanceRecords'));
    }

    public function show(int $id)
    {
        $record = AttendanceRecord::with(['user', 'attendanceBreaks', 'attendanceCorrections'])->findOrFail($id);

        $user = $record->user;
        $carbonDate = Carbon::parse($record->date);

        $attendanceRecord = [
            'id' => $record->id,
            'year' => $carbonDate->format('Y年'),
            'date' => $carbonDate->format('n月j日'),
            'clock_in' => $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '',
            'clock_out' => $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '',
            'comment' => '',
            'breaks' => $record->attendanceBreaks->map(fn ($b) => [
                'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
            ])->toArray(),
        ];

        return view('admin.admin-detail', compact('user', 'attendanceRecord'));
    }

    public function update(UpdateAttendanceRequest $request, int $id)
    {
        $validated = $request->validated();

        $record = AttendanceRecord::findOrFail($id);

        if ($record->attendanceCorrections()->where('status', 'pending')->exists()) {
            return back()->withErrors(['status' => '承認待ちのため修正はできません。']);
        }

        DB::transaction(function () use ($validated, $record) {
            $dateStr = Carbon::parse($record->date)->format('Y-m-d');

            $record->update([
                'clock_in' => Carbon::parse($dateStr.' '.$validated['new_clock_in']),
                'clock_out' => Carbon::parse($dateStr.' '.$validated['new_clock_out']),
            ]);

            $record->attendanceBreaks()->delete();

            if (! empty($validated['new_break_in']) && is_array($validated['new_break_in'])) {
                foreach ($validated['new_break_in'] as $index => $breakIn) {
                    $breakOut = $validated['new_break_out'][$index] ?? null;

                    if (! empty($breakIn) && ! empty($breakOut)) {
                        $record->attendanceBreaks()->create([
                            'break_in' => Carbon::parse($dateStr.' '.$breakIn),
                            'break_out' => Carbon::parse($dateStr.' '.$breakOut),
                        ]);
                    }
                }
            }
        });

        return redirect('/attendance/'.$id);
    }
}
