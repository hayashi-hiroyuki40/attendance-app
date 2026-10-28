<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class No18_AttendanceWriteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーが勤怠を新規登録できること()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $data = [
            'date' => '2026-05-02',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '備考',
        ];

        $response = $this->postJson('/api/v1/attendance-records', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.date', '2026-05-02')
            ->assertJsonPath('data.comment', '備考');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-05-02',
        ]);
    }

    public function test_不正データで勤怠登録した場合は422エラーと日本語メッセージが返ること()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/attendance-records', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date', 'clock_in']);
    }

    public function test_認証済みユーザーが自分の勤怠を更新できること()
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-05-03',
            'status' => '出勤中',
            'clock_in' => '09:00:00',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson("/api/v1/attendance-records/{$record->id}", [
            'date' => '2026-05-03',
            'clock_in' => '09:30:00',
            'comment' => '更新しました',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.clock_in', '09:30:00')
            ->assertJsonPath('data.comment', '更新しました');
    }

    public function test_存在しない勤怠を更新しようとした場合は404エラーが返ること()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/attendance-records/99999', [
            'date' => '2026-05-03',
            'clock_in' => '09:00:00',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }

    public function test_認証済みユーザーが自分の勤怠を削除できること()
    {
        $user = User::factory()->create();
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'clock_in' => '09:00:00',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson("/api/v1/attendance-records/{$record->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
    }

    public function test_存在しない勤怠を削除しようとした場合は404エラーが返ること()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->deleteJson('/api/v1/attendance-records/99999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => '勤怠情報が見つかりませんでした。',
            ]);
    }
}
