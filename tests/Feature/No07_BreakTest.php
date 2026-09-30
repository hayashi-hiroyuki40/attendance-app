<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No07_BreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_休憩ボタンが正しく機能する()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => today(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');

        $this->post('/attendance', [
            'action' => 'break_in',
        ]);

        $this->get('/attendance')->assertSee('休憩中');
    }

    public function test_休憩は一日に何回でもできる()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);
        $this->post('/attendance', ['action' => 'break_out']);

        $response = $this->get('/attendance');

        $response->assertSee('休憩入');
    }

    public function test_休憩戻ボタンが正しく機能する()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $this->post('/attendance', ['action' => 'break_out']);

        $this->get('/attendance')->assertSee('出勤中');
    }

    public function test_休憩戻は一日に何回でもできる()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);
        $this->post('/attendance', ['action' => 'break_out']);
        $this->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('休憩戻');
    }

    public function test_休憩時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => today(),
            'clock_in' => now()->subHours(4),
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $latestBreak = $attendance->attendanceBreaks()->latest()->first();
        if ($latestBreak) {
            $latestBreak->update([
                'break_in' => now()->subHour(),
            ]);
        }
        $this->post('/attendance', [
            'action' => 'break_out',
        ]);

        $response = $this->get('/attendance/list');

        $response->assertSee('01:00');
    }
}
