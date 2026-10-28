<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No17_AttendanceReadApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_公開_ap_iで勤怠一覧が正常に取得できること()
    {
        $user = User::factory()->create();
        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'status' => '出勤中',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'date',
                        'clock_in',
                        'clock_out',
                        'total_time',
                        'total_break_time',
                        'comment',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_公開_ap_iで勤怠詳細が正常に取得できること()
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-05-01',
            'status' => '出勤中',
            'clock_in' => '09:00:00',
        ]);

        $response = $this->getJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $record->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user',
                    'date',
                    'clock_in',
                    'clock_out',
                    'breaks',
                    'applications',
                    'comment',
                ],
            ]);
    }

    public function test_存在しない勤怠_i_dを指定した場合は404エラーが返ること()
    {
        $response = $this->getJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
