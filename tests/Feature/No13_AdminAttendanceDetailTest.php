<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'name' => '山田太郎']);
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-07',
        ]);

        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendanceRecord->id}");

        $response->assertStatus(200)
            ->assertSee('山田太郎')
            ->assertSee('2026-10-07');
    }

    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attendanceRecord = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'break_in' => '12:00',
            'break_out' => '13:00',
            'comment' => '修正理由の記入',
        ]);

        $response->assertSessionHasErrors(['clock_in' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attendanceRecord = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_in' => '19:00',
            'break_out' => '20:00',
            'comment' => '修正理由の記入',
        ]);

        $response->assertSessionHasErrors(['break_in' => '休憩時間が不適切な値です']);
    }

    public function test_休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attendanceRecord = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_in' => '12:00',
            'break_out' => '19:00',
            'comment' => '修正理由の記入',
        ]);

        $response->assertSessionHasErrors(['break_out' => '休憩時間もしくは退勤時間が不適切な値です']);
    }

    public function test_備考欄が未入力の場合のエラーメッセージが表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $attendanceRecord = AttendanceRecord::factory()->create();

        $response = $this->actingAs($admin)->post("/admin/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_in' => '12:00',
            'break_out' => '13:00',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }
}
