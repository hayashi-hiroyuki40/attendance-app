<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class No05_AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤務外の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    public function test_出勤中の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => Carbon::today(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    public function test_休憩中の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '休憩中',
            'date' => Carbon::today(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    public function test_退勤済の場合_勤怠ステータスが正しく表示される()
    {
        $user = User::factory()->create(['role' => 'user']);
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '退勤済',
            'date' => Carbon::today(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }
}
