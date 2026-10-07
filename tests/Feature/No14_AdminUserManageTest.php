<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_管理者ユーザーが全一般ユーザーの氏名とメールアドレスを確認できる()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'user', 'name' => 'ユーザー一号', 'email' => 'user1@example.com']);
        $user2 = User::factory()->create(['role' => 'user', 'name' => 'ユーザー二号', 'email' => 'user2@example.com']);

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200)
            ->assertSee($user1->name)->assertSee($user1->email)
            ->assertSee($user2->name)->assertSee($user2->email);
    }

    public function test_ユーザーの勤怠情報が正しく表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id, 'date' => '2026-10-01']);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200)
            ->assertSee('2026-10-01');
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=2026-09");

        $response->assertStatus(200)
            ->assertSee('2026-09');
    }

    public function test_翌月を押下した時に表示月の翌月の情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}?month=2026-11");

        $response->assertStatus(200)
            ->assertSee('2026-11');
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $attendanceRecord = AttendanceRecord::factory()->create(['user_id' => $user->id]);

        $this->actingAs($admin)->get("/admin/attendance/staff/{$user->id}")->assertStatus(200);

        $response = $this->actingAs($admin)->get("/admin/attendance/{$attendanceRecord->id}");

        $response->assertStatus(200);
    }
}
