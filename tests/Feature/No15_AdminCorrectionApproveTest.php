<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No15_AdminCorrectionApproveTest extends TestCase
{
    use RefreshDatabase;

    public function test_承認待ちの修正申請が全て表示されている()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user1 = User::factory()->create(['admin_status' => false]);
        $user2 = User::factory()->create(['admin_status' => false]);

        $record1 = AttendanceRecord::factory()->create(['user_id' => $user1->id, 'status' => '出勤中']);
        $record2 = AttendanceRecord::factory()->create(['user_id' => $user2->id, 'status' => '出勤中']);

        $correction1 = AttendanceCorrection::factory()->create(['user_id' => $user1->id, 'attendance_record_id' => $record1->id, 'status' => '承認待ち', 'comment' => '未承認理由１']);
        $correction2 = AttendanceCorrection::factory()->create(['user_id' => $user2->id, 'attendance_record_id' => $record2->id, 'status' => '承認待ち', 'comment' => '未承認理由２']);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=pending');

        $response->assertStatus(200)
            ->assertSee($correction1->comment)
            ->assertSee($correction2->comment);
    }

    public function test_承認済みの修正申請が全て表示されている()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user1 = User::factory()->create(['admin_status' => false]);

        $record1 = AttendanceRecord::factory()->create(['user_id' => $user1->id, 'status' => '出勤中']);
        $correction1 = AttendanceCorrection::factory()->create(['user_id' => $user1->id, 'attendance_record_id' => $record1->id, 'status' => '承認済み', 'comment' => '承認済み理由１']);

        $response = $this->actingAs($admin)->get('/stamp_correction_request/list?tab=approved');

        $response->assertStatus(200)
            ->assertSee($correction1->comment);
    }

    public function test_修正申請の詳細内容が正しく表示されている()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false, 'name' => '申請太郎']);
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '出勤中']);

        $correction = AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'status' => '承認待ち',
            'comment' => '遅延のため時間変更',
        ]);

        $correction->correctionBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/stamp_correction_request/approve/{$correction->id}");

        $response->assertStatus(200)
            ->assertSee('申請太郎')
            ->assertSee('遅延のため時間変更');
    }

    public function test_修正申請の承認処理が正しく行われる()
    {
        $admin = User::factory()->create(['admin_status' => true]);
        $user = User::factory()->create(['admin_status' => false]);
        $record = AttendanceRecord::factory()->create(['user_id' => $user->id, 'status' => '出勤中']);

        $correction = AttendanceCorrection::factory()->create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id,
            'clock_in' => '2026-10-07 09:30:00',
            'clock_out' => '2026-10-07 18:30:00',
            'status' => '承認待ち',
        ]);

        $correction->correctionBreaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)->post("/stamp_correction_request/approve/{$correction->id}");

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'status' => '承認済み',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'clock_in' => '2026-10-07 09:30:00',
            'clock_out' => '2026-10-07 18:30:00',
        ]);
    }
}
