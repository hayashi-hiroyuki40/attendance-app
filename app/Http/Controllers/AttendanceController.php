<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceCorrectionRequest;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\CorrectionBreak;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 一般ユーザー向けの勤怠管理および修正申請を制御するコントローラー。
 *
 * ユーザー自身の勤怠一覧の閲覧、詳細確認、および勤怠修正の申請処理を行う。
 */
class AttendanceController extends Controller
{
    /**
     * ログインユーザーの月次勤怠一覧画面を表示する。
     *
     * 指定された月（または現在月）の勤怠データを取得し、
     * 各レコードの休憩時間や合計勤務時間を計算してビューに渡す。
     *
     * @param  Request  $request  クエリパラメータ（date等）を含むHTTPリクエスト
     * @return View 一般ユーザー用勤怠一覧画面のビュー
     */
    public function index(Request $request): View
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

    /**
     * ログインユーザーの指定勤怠詳細画面を表示する。
     *
     * 承認待ちの修正申請が存在する場合はその申請内容を優先して表示し、
     * 存在しない場合は通常の勤怠データ（またはバリデーションエラー時の old 入力値）を反映してビューに渡す。
     *
     * @param  int  $id  対象の勤怠レコードID
     * @return View 一般ユーザー用勤怠詳細画面のビュー
     */
    public function show(int $id): View
    {
        $user = Auth::user();

        $record = AttendanceRecord::with(['attendanceBreaks', 'attendanceCorrections' => fn($q) => $q->where('status', '承認待ち')->with('correctionBreaks')])
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
                'comment' => $pendingCorrection->comment ?? '',
                'application' => $pendingCorrection,
                'breaks' => $pendingCorrection->correctionBreaks->map(fn($b) => [
                    'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                    'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
                ])->toArray(),
            ];
        } else {
            $clockIn = old('new_clock_in', $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i') : '');
            $clockOut = old('new_clock_out', $record->clock_out ? Carbon::parse($record->clock_out)->format('H:i') : '');
            $comment = old('comment', $record->comment ?? '');

            if (old('new_break_in') !== null && is_array(old('new_break_in'))) {
                $breaks = [];
                $oldBreakIns = old('new_break_in');
                $oldBreakOuts = old('new_break_out', []);

                foreach ($oldBreakIns as $index => $breakIn) {
                    $breaks[] = [
                        'break_in' => $breakIn ?? '',
                        'break_out' => $oldBreakOuts[$index] ?? '',
                    ];
                }

                if (! empty($breaks) && end($breaks)['break_in'] === '' && end($breaks)['break_out'] === '') {
                    array_pop($breaks);
                }
            } else {
                $breaks = $record->attendanceBreaks->map(fn($b) => [
                    'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                    'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
                ])->toArray();
            }

            $data = [
                'id' => $record->id,
                'year' => $carbonDate->format('Y年'),
                'date' => $carbonDate->format('n月j日'),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'comment' => $comment,
                'application' => $pendingCorrection,
                'breaks' => $breaks,
            ];
        }

        return view('user.user-detail', compact('user', 'data'));
    }

    /**
     * 勤怠の修正申請を新規作成（ストア）する。
     *
     * すでに承認待ちの申請が存在する場合は処理を拒否し、
     * データベーストランザクション内で修正申請本体および関連する休憩修正データを安全に登録する。
     *
     * @param  AttendanceCorrectionRequest  $request  バリデーション済みの修正申請リクエスト
     * @param  int                           $id       対象の勤怠レコードID
     * @return RedirectResponse 申請一覧画面へのリダイレクト、またはエラー時の差し戻し
     */
    public function store(AttendanceCorrectionRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        $record = AttendanceRecord::where('user_id', Auth::id())->findOrFail($id);

        if ($record->attendanceCorrections()->where('status', '承認待ち')->exists()) {
            return back()->withErrors(['status' => '承認待ちのため修正できません。']);
        }

        DB::transaction(function () use ($validated, $record) {
            $dateStr = Carbon::parse($record->date)->format('Y-m-d');

            $clockIn = $validated['new_clock_in'];
            $clockOut = $validated['new_clock_out'];

            $correction = AttendanceCorrection::create([
                'attendance_record_id' => $record->id,
                'user_id' => Auth::id(),
                'clock_in' => Carbon::parse($dateStr . ' ' . $clockIn),
                'clock_out' => Carbon::parse($dateStr . ' ' . $clockOut),
                'comment' => $validated['comment'],
                'status' => '承認待ち',
            ]);

            $breakIns = $validated['new_break_in'] ?? [];
            $breakOuts = $validated['new_break_out'] ?? [];

            if (! empty($breakIns) && is_array($breakIns)) {
                foreach ($breakIns as $index => $breakIn) {
                    $breakOut = $breakOuts[$index] ?? null;

                    if (! empty($breakIn) && ! empty($breakOut)) {
                        CorrectionBreak::create([
                            'attendance_correction_id' => $correction->id,
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
