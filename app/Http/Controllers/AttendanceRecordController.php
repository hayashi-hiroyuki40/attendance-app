<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 一般ユーザー向けの出退勤打刻を制御するコントローラー。
 *
 * 本日の打刻画面の表示（現在のステータス判定）、および出勤・休憩・退勤の打刻アクション処理を行う。
 */
class AttendanceRecordController extends Controller
{
    /**
     * 本日の打刻画面を表示する。
     *
     * 現在の日時、曜日、およびログインユーザーの今日の打刻状態（勤務外 / 出勤中 / 休憩中 / 退勤済）を取得し、
     * ビューに渡して表示する。
     *
     * @return View 打刻画面のビュー
     */
    public function index(): View
    {
        $user = Auth::user();
        $now = Carbon::now();
        $today = Carbon::today();

        $week = ['日', '月', '火', '水', '木', '金', '土'];
        $formattedDate = $now->format("Y年n月j日({$week[$now->dayOfWeek]})");
        $formattedTime = $now->format('H:i');

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $attendanceStatus = $attendance ? $attendance->status : '勤務外';

        $user->attendance_status = $attendanceStatus;

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 打刻アクション（出勤・休憩入・休憩戻・退勤）を実行する。
     *
     * リクエストに含まれる action パラメータに応じて、現在のステータスやバリデーションを考慮しながら
     * 勤怠レコードおよび休憩レコードの状態を更新する。
     *
     * @param  Request  $request  アクション名（clock_in, break_in等）を含むHTTPリクエスト
     * @return RedirectResponse 処理完了後の直前のページへのリダイレクト
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        $currentStatus = $attendance ? $attendance->status : '勤務外';
        $action = $request->input('action');

        switch ($action) {
            case 'clock_in':
                if ($currentStatus === '勤務外' && ! $attendance) {
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
