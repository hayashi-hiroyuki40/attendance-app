<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class No08_ClockOutTest extends TestCase
{
    use RefreshDatabase;

    public function test_退勤ボタンが正しく機能する()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => today(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤');

        $this->post('/attendance', ['action' => 'clock_out']);

        $this->get('/attendance')->assertSee('退勤済');
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる()
    {
        Carbon::setTestNow('2026-09-30 09:00:00');

        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);
        Carbon::setTestNow('2026-09-30 18:00:00');
        $this->post('/attendance', ['action' => 'clock_out']);

        $response = $this->get('/attendance/list');

        $response->assertSee('18:00');

        Carbon::setTestNow();
    }
}
