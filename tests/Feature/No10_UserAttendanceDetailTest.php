<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No10_UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面の「名前」がログインユーザーの氏名になっている()
    {
        $user = User::factory()->create(['name' => '山田太郎', 'role' => 'user']);
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '出勤中']);

        $response = $this->actingAs($user)->get("/attendance/{$record->id}");

        $response->assertSee('山田太郎');
    }

    public function test_勤怠詳細画面の「日付」が選択した日付になっている()
    {
        $user = User::factory()->create(['role' => 'user']);
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-09-30', 'status' => '出勤中']);

        $response = $this->actingAs($user)->get("/attendance/{$record->id}");

        $response->assertSee('2026年')
            ->assertSee('9月30日');
    }

    public function test_出勤・退勤にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create(['role' => 'user']);
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '2026-09-30 09:00:00',
            'clock_out' => '2026-09-30 18:00:00',
            'status' => '退勤済',
        ]);

        $response = $this->actingAs($user)->get("/attendance/{$record->id}");

        $response->assertSee('09:00')->assertSee('18:00');
    }

    public function test_休憩にて記されている時間がログインユーザーの打刻と一致している()
    {
        $user = User::factory()->create(['role' => 'user']);
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-06-19',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => '退勤済',
        ]);
        AttendanceBreak::factory()->create([
            'attendance_record_id' => $record->id,
            'break_in' => '2026-09-30 12:00:00',
            'break_out' => '2026-09-30 13:00:00',
        ]);

        $response = $this->actingAs($user)->get("/attendance/{$record->id}");

        $response->assertSee('12:00')->assertSee('13:00');
    }
}
