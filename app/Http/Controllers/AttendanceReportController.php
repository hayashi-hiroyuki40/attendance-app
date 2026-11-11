<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ユーザーの勤怠レポートおよび統計情報の集計を制御するコントローラー。
 *
 * 過去6ヶ月間の勤怠データをもとに、総労働時間、残業時間、月次推移グラフ用データ、
 * および今月の異常値（遅刻・早退・長時間労働）の集計を行い、レポート画面に表示する。
 */
class AttendanceReportController extends Controller
{
    /**
     * 勤怠レポート画面を表示する。
     *
     * ログインユーザーの直近6ヶ月間の勤怠レコードを取得し、
     * 休憩時間や実労働時間、残業時間の算出、月ごとのトレンド集計、および今月の統計データの算出を行う。
     *
     * @param  Request  $request  HTTPリクエスト
     * @return View 勤怠レポート画面のビュー
     */
    public function show(Request $request): View
    {
        $now = Carbon::now();
        $currentMonth = $now->format('Y-m');

        $records = AttendanceRecord::with('attendanceBreaks')
            ->where('user_id', $request->user()->id)
            ->whereBetween('date', [
                $now->copy()->subMonths(5)->startOfMonth()->format('Y-m-d'),
                $now->copy()->endOfMonth()->format('Y-m-d'),
            ])->get();

        $reportData = $records->map(function ($r) use ($currentMonth) {
            $breakMins = $r->attendanceBreaks->sum(
                fn($b) => $b->break_in && $b->break_out ? Carbon::parse($b->break_in)->diffInMinutes($b->break_out) : 0
            );

            $workMins = ($r->clock_in && $r->clock_out)
                ? max(0, Carbon::parse($r->clock_in)->diffInMinutes($r->clock_out) - $breakMins)
                : 0;

            $month = Carbon::parse($r->date)->format('Y-m');

            return [
                'month' => $month,
                'is_current' => ($month === $currentMonth),
                'work' => $workMins,
                'over' => max(0, $workMins - 480),
                'late' => $r->clock_in && Carbon::parse($r->clock_in)->format('H:i') > '09:00',
                'early' => $r->clock_out && Carbon::parse($r->clock_out)->format('H:i') < '18:00',
                'long' => $workMins > 600,
            ];
        })->filter(fn($r) => $r['work'] > 0);

        $summary = [
            'total_work_minutes' => $reportData->sum('work'),
            'total_overtime_minutes' => $reportData->sum('over'),
            'avg_work_minutes' => $reportData->count() ? (int) round($reportData->sum('work') / $reportData->count()) : 0,
        ];

        $monthlyTrend = collect(range(5, 0))->mapWithKeys(fn($i) => [
            ($m = $now->copy()->subMonths($i)->format('Y-m')) => [
                'month' => $m,
                'work_minutes' => $reportData->where('month', $m)->sum('work'),
                'overtime_minutes' => $reportData->where('month', $m)->sum('over'),
            ],
        ]);

        $currentMonthData = $reportData->where('is_current', true);
        $anomalies = [
            'late_count' => $currentMonthData->where('late', true)->count(),
            'early_leave_count' => $currentMonthData->where('early', true)->count(),
            'long_work_count' => $currentMonthData->where('long', true)->count(),
        ];

        return view('reports.index', compact('summary', 'monthlyTrend', 'anomalies'));
    }
}
