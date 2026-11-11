<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 管理者向けの勤怠管理を制御するコントローラー。
 *
 * 管理者による全スタッフの勤怠一覧の閲覧、詳細確認、および勤怠情報の修正・更新処理を行う。
 */
class AdminAttendanceController extends Controller
{
    /**
     * 指定された日付の管理者用勤怠一覧画面を表示する。
     *
     * クエリパラメータで指定された日付、または本日の日付を基準に、
     * 一般ユーザー一覧と該当日の勤怠データを取得してビューに渡す。
     *
     * @param  Request  $request  クエリパラメータ（date等）を含むHTTPリクエスト
     * @return View 管理者用勤怠一覧画面のビュー
     */
    public function index(Request $request): View
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $previousDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        $users = User::where('admin_status', false)->get();
        $attendanceRecords = AttendanceRecord::with('attendanceBreaks')->whereDate('date', $date)->get();

        return view('admin.admin-attendance-list', compact('date', 'previousDay', 'nextDay', 'users', 'attendanceRecords'));
    }

    /**
     * 指定された勤怠IDの詳細画面を表示する。
     *
     * 該当する勤怠レコード、ユーザー情報、休憩、修正申請をロードし、
     * ビュー側で扱いやすい形式に成形して渡す。
     *
     * @param  int  $id  対象の勤怠レコードID
     * @return View 管理者用勤怠詳細画面のビュー
     */
    public function show(int $id): View
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
            'breaks' => $record->attendanceBreaks->map(fn($b) => [
                'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
            ])->toArray(),
        ];

        return view('admin.admin-detail', compact('user', 'attendanceRecord'));
    }

    /**
     * 指定された勤怠情報を更新する。
     *
     * 承認待ちの修正申請が存在する場合は更新を拒否し、
     * データベーストランザクション内で出退勤時刻の更新と休憩情報の再登録を行う。
     *
     * @param  UpdateAttendanceRequest  $updateRequest  バリデーション済みの更新リクエスト
     * @param  int                       $id             対象の勤怠レコードID
     * @return RedirectResponse 更新完了後の詳細画面へのリダイレクト、またはエラー時の差し戻し
     */
    public function update(UpdateAttendanceRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        $record = AttendanceRecord::findOrFail($id);

        if ($record->attendanceCorrections()->where('status', '承認待ち')->exists()) {
            return back()->withErrors(['status' => '承認待ちのため修正はできません。']);
        }

        DB::transaction(function () use ($validated, $record) {
            $dateStr = Carbon::parse($record->date)->format('Y-m-d');

            $record->update([
                'clock_in' => Carbon::parse($dateStr . ' ' . $validated['new_clock_in']),
                'clock_out' => Carbon::parse($dateStr . ' ' . $validated['new_clock_out']),
                'comment' => $validated['comment'] ?? '',
            ]);

            $record->attendanceBreaks()->delete();

            if (! empty($validated['new_break_in']) && is_array($validated['new_break_in'])) {
                foreach ($validated['new_break_in'] as $index => $breakIn) {
                    $breakOut = $validated['new_break_out'][$index] ?? null;

                    if (! empty($breakIn) && ! empty($breakOut)) {
                        $record->attendanceBreaks()->create([
                            'break_in' => Carbon::parse($dateStr . ' ' . $breakIn),
                            'break_out' => Carbon::parse($dateStr . ' ' . $breakOut),
                        ]);
                    }
                }
            }
        });

        return redirect('/attendance/' . $id);
    }
}
