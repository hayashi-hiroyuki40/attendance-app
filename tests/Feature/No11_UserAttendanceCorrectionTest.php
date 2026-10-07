<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class No11_UserAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '退勤済',]);

        $response = $this->actingAs($user)->post("/attendance/{$attendanceRecord->id}", [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'break_in' => '12:00',
            'break_out' => '13:00',
            'comment' => '修正理由の記入',
        ]);

        $response->assertSessionHasErrors(['clock_in' => '出勤時間が不適切な値です']);
    }

    public function test_休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '退勤済',]);

        $response = $this->actingAs($user)->post("/attendance/{$attendanceRecord->id}", [
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
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '退勤済',]);

        $response = $this->actingAs($user)->post("/attendance/{$attendanceRecord->id}", [
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
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '退勤済',]);

        $response = $this->actingAs($user)->post("/attendance/{$attendanceRecord->id}", [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_in' => '12:00',
            'break_out' => '13:00',
            'comment' => '',
        ]);

        $response->assertSessionHasErrors(['comment' => '備考を記入してください']);
    }

    public function test_修正申請処理が実行される()
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '出勤中',]);

        $this->actingAs($user)->post("/attendance/{$attendanceRecord->id}", [
            'clock_in' => '2026-10-07 08:30:00',
            'clock_out' => '2026-10-07 17:30:00',
            'comment' => '電車遅延のため修正',
        ]);

        $this->assertDatabaseHas('attendance_corrections', [
            'attendance_record_id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'comment' => '電車遅延のため修正',
            'status' => '承認待ち',
        ]);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list');

        $response->assertStatus(200)
            ->assertSee($user->name)
            ->assertSee('電車遅延のため修正');
    }

    public function test_承認待ちにログインユーザーが行った申請が全て表示されていること()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '出勤中',]);

        $attendanceCorrection = AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'status' => '承認待ち',
            'comment' => '承認待ちのテスト用申請理由',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200)
            ->assertSee($attendanceCorrection->comment);
    }

    public function test_承認済みに管理者が承認した修正申請が全て表示されている()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '出勤中',]);

        $attendanceCorrection = AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'status' => '承認済み',
            'comment' => '承認済みのテスト用申請理由',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200)
            ->assertSee($attendanceCorrection->comment);
    }

    public function test_各申請の詳細を押下すると勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '出勤中',]);

        AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'status' => '承認待ち',
        ]);

        $this->actingAs($user)->get('/stamp_correction_request/list')->assertStatus(200);

        $response = $this->actingAs($user)->get("/attendance/{$attendanceRecord->id}");

        $response->assertStatus(200);
    }
}
