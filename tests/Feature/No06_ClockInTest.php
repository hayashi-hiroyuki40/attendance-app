<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No06_ClockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤ボタンが正しく機能する()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤');

        $this->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $responseAfter = $this->get('/attendance');
        $responseAfter->assertSee('出勤中');
    }

    public function test_出勤は一日一回のみできる()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'status' => '退勤済',
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertDontSee('出勤');
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる()
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->post('/attendance');

        $response = $this->get('/attendance/list');

        $response->assertSee(now()->format('H:i'));
    }
}
