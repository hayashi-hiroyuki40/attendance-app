<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * 管理者向けの勤怠修正申請の管理を制御するコントローラー。
 *
 * 修正申請の一覧表示、詳細確認、および申請の承認処理（勤怠データへの反映）を行う。
 */
class AdminCorrectionController extends Controller
{
    /**
     * 修正申請の一覧画面を表示する。
     *
     * 全ての勤怠修正申請を登録日時の降順で取得し、
     * ビュー側で扱いやすいようプロパティを整えてビューに渡す。
     *
     * @param  Request  $request  HTTPリクエスト
     * @return View 修正申請一覧画面のビュー
     */
    public function index(Request $request): View
    {
        $applications = AttendanceCorrection::with(['user', 'attendanceRecord'])
            ->orderBy('created_at', 'desc')
            ->get();

        $applications = $applications->map(function ($app) {
            $record = $app->attendanceRecord ?? $app->AttendanceRecord;

            $app->approval_status = $app->status;
            $app->application_date = $app->created_at;

            $app->AttendanceRecord = $record;

            return $app;
        });

        return view('admin.admin-application-list', compact('applications'));
    }

    /**
     * 指定された修正申請の詳細画面を表示する。
     *
     * 該当する修正申請、関連するユーザー、元の勤怠レコード、および修正用休憩情報をロードし、
     * 日付や時刻のフォーマットを整えてビューに渡す。
     *
     * @param  int  $id  対象の修正申請ID
     * @return View 修正申請詳細画面のビュー
     */
    public function show(int $id): View
    {
        $application = AttendanceCorrection::with(['user', 'attendanceRecord', 'correctionBreaks'])
            ->findOrFail($id);

        $recordDate = Carbon::parse($application->attendanceRecord->date);
        $application->new_date = $recordDate;
        $application->new_clock_in = $application->clock_in ? Carbon::parse($application->clock_in)->format('H:i') : '';
        $application->new_clock_out = $application->clock_out ? Carbon::parse($application->clock_out)->format('H:i') : '';
        $application->approval_status = $application->status;

        $user = $application->user;

        return view('admin.admin-application-detail', compact('application', 'user'));
    }

    /**
     * 修正申請を承認し、申請内容を元の勤怠レコードに反映する。
     *
     * データベーストランザクション内で、申請ステータスを「承認済み」に変更し、
     * 出退勤時刻の更新と休憩情報の置き換え（既存削除＆新規作成）を安全に実行する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  int      $id       対象の修正申請ID
     * @return RedirectResponse 修正申請一覧画面へのリダイレクト
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $application = AttendanceCorrection::with(['attendanceRecord', 'correctionBreaks'])
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

            foreach ($application->correctionBreaks as $break) {
                $record->attendanceBreaks()->create([
                    'break_in' => $break->break_in,
                    'break_out' => $break->break_out,
                ]);
            }
        });

        return redirect('/stamp_correction_request/list');
    }
}
