<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 管理者向けのスタッフ別勤怠管理およびCSVエクスポートを制御するコントローラー。
 *
 * 一般スタッフの一覧表示、特定スタッフの月次勤怠詳細の閲覧、および勤怠データのCSV出力を行う。
 */
class StaffAttendanceController extends Controller
{
    /**
     * 一般スタッフ一覧画面を表示する。
     *
     * 管理者権限を持たない一般ユーザー（admin_status が false）の一覧を取得してビューに渡す。
     *
     * @return View スタッフ一覧画面のビュー
     */
    public function index(): View
    {
        $users = User::where('admin_status', false)->get();

        return view('admin.staff-list', compact('users'));
    }

    /**
     * 指定されたスタッフの月次勤怠一覧画面を表示する。
     *
     * クエリパラメータ（month または date）で指定された月、または現在月を基準に、
     * 該当スタッフの勤怠レコードや休憩時間を取得し、各日ごとの数値を整形してビューに渡す。
     *
     * @param  Request  $request  クエリパラメータ（month, date等）を含むHTTPリクエスト
     * @param  int      $id       対象スタッフのユーザーID
     * @return View スタッフ別月次勤怠一覧画面のビュー
     */
    public function show(Request $request, int $id): View
    {
        $user = User::where('admin_status', false)->findOrFail($id);

        $dateParam = $request->query('month') ?? $request->query('date');

        if ($dateParam) {
            $date = Carbon::parse($dateParam);
        } else {
            $date = Carbon::now();
        }
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

    /**
     * 指定されたスタッフの月次勤怠データをCSV形式でエクスポートする。
     *
     * 該当ユーザーおよび対象月の勤怠レコードをストリーミングレスポンスで出力し、
     * Excel等での文字化けを防ぐためのBOM付きUTF-8としてCSVファイルをダウンロードさせる。
     *
     * @param  Request  $request  対象ユーザーID（user_id）と対象月（year_month）を含むHTTPリクエスト
     * @return StreamedResponse CSVファイルのストリーミングダウンロードレスポンス
     */
    public function exportCsv(Request $request): StreamedResponse
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

            foreach ($records as $r) {
                $breakMins = $r->attendanceBreaks->sum(
                    fn($b) => $b->break_in && $b->break_out ? Carbon::parse($b->break_in)->diffInMinutes(Carbon::parse($b->break_out)) : 0
                );

                $workMins = ($r->clock_in && $r->clock_out)
                    ? Carbon::parse($r->clock_in)->diffInMinutes(Carbon::parse($r->clock_out)) - $breakMins
                    : 0;

                $row = [
                    $r->date,
                    $r->clock_in ? Carbon::parse($r->clock_in)->format('G:i') : '',
                    $r->clock_out ? Carbon::parse($r->clock_out)->format('G:i') : '',
                    $breakMins ? sprintf('%d:%02d', intdiv($breakMins, 60), $breakMins % 60) : '',
                    $workMins > 0 ? sprintf('%d:%02d', intdiv($workMins, 60), $workMins % 60) : '',
                ];

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
