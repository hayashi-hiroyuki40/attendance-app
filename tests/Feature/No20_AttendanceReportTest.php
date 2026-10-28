<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No20_AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_ゲストはレポートページにアクセスできない()
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    public function test_認証ユーザーの統計情報が正しく計算される()
    {
        $user = User::factory()->create();

        $targetDate = Carbon::now()->startOfMonth()->toDateString();
        $clockIn = Carbon::parse($targetDate.' 09:15:00');
        $clockOut = Carbon::parse($targetDate.' 19:15:00');

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => $targetDate,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'status' => '退勤済',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $record->id,
            'break_in' => Carbon::parse($targetDate.' 12:00:00'),
            'break_out' => Carbon::parse($targetDate.' 13:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertStatus(200);
        $response->assertViewHas('summary');
        $response->assertViewHas('monthlyTrend');
        $response->assertViewHas('anomalies');
    }

    public function test_勤怠記録がないユーザーで安全に処理される()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance/report');

        $response->assertStatus(200);
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_work_minutes'] === 0
                && $summary['total_overtime_minutes'] === 0
                && $summary['avg_work_minutes'] === 0;
        });
    }
}
