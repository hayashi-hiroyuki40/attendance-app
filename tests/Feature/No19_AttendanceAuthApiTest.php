<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class No19_AttendanceAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証時に書き込み系_ap_iで401が返ること()
    {
        $attendance = AttendanceRecord::factory()->create();

        $this->postJson('/api/v1/attendance-records', [])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->putJson("/api/v1/attendance-records/{$attendance->id}", [])
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);

        $this->deleteJson("/api/v1/attendance-records/{$attendance->id}")
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_認証済みユーザーが自分の勤怠を更新_削除できること()
    {
        $user = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user, ['*']);

        $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'clock_out' => '19:00:00',
        ])->assertStatus(200);

        $this->deleteJson("/api/v1/attendance-records/{$attendance->id}")
            ->assertStatus(204);
    }

    public function test_他ユーザーの勤怠を更新_削除しようとすると403が返ること()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $attendance = AttendanceRecord::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($otherUser, ['*']);

        $this->putJson("/api/v1/attendance-records/{$attendance->id}", [
            'clock_out' => '19:00:00',
        ])
            ->assertStatus(403)
            ->assertJson(['error' => 'この操作を実行する権限がありません。']);

        $this->deleteJson("/api/v1/attendance-records/{$attendance->id}")
            ->assertStatus(403)
            ->assertJson(['error' => 'この操作を実行する権限がありません。']);
    }
}
