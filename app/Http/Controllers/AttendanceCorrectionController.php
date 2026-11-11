<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 一般ユーザー向けの勤怠修正申請の履歴・詳細を制御するコントローラー。
 *
 * 修正申請の一覧表示（承認待ち/承認済みタブ切り替え）および詳細確認を行う。
 */
class AttendanceCorrectionController extends Controller
{
    /**
     * 修正申請の一覧画面を表示する。
     *
     * クエリパラメータで指定されたタブ（承認待ち or 承認済み）に応じて申請を絞り込み、
     * ビュー側で扱いやすい形式に成形して一覧を渡す。
     *
     * @param  Request  $request  クエリパラメータ（tab等）を含むHTTPリクエスト
     * @return View 修正申請一覧画面のビュー
     */
    public function index(Request $request): View
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
                'id' => $app->attendance_record_id,
                'approval_status' => $app->status,
                'user_name' => $app->user->name,
                'date' => Carbon::parse($app->attendanceRecord->date)->format('Y/m/d'),
                'comment' => $app->comment,
                'application_date' => Carbon::parse($app->created_at)->format('Y/m/d'),
            ];
        });

        $firstApp = $applications->first();
        $user = ($firstApp && $firstApp->user) ? $firstApp->user : $loginUser;

        return view('user.user-application-list', compact('formattedApplications', 'user', 'tab'));
    }

    /**
     * 指定された修正申請の詳細画面を表示する。
     *
     * 該当する修正申請、関連するユーザー、元の勤怠レコード、および修正用休憩情報をロードし、
     * 日付や時刻のフォーマットを整えてビューに渡す。
     *
     * @param  int|string  $id  対象の修正申請ID
     * @return View 修正申請詳細画面のビュー
     */
    public function show($id): View
    {
        $application = AttendanceCorrection::with(['user', 'attendanceRecord', 'correctionBreaks'])->findOrFail($id);

        $data = [
            'id' => $application->attendance_record_id,
            'application' => $application,
            'year' => Carbon::parse($application->attendanceRecord->date)->format('Y年'),
            'date' => Carbon::parse($application->attendanceRecord->date)->format('n月j日'),
            'clock_in' => $application->clock_in ? Carbon::parse($application->clock_in)->format('H:i') : '',
            'clock_out' => $application->clock_out ? Carbon::parse($application->clock_out)->format('H:i') : '',
            'comment' => $application->comment,
            'breaks' => $application->correctionBreaks->map(fn($b) => [
                'break_in' => $b->break_in ? Carbon::parse($b->break_in)->format('H:i') : '',
                'break_out' => $b->break_out ? Carbon::parse($b->break_out)->format('H:i') : '',
            ])->toArray(),
        ];

        $user = $application->user;

        return view('user.user-detail', compact('data', 'user'));
    }
}
