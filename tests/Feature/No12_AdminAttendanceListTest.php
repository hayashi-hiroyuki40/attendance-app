<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_その日になされた全ユーザーの勤怠情報が正確に確認できる()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'user', 'name' => 'テスト太郎']);
        $user2 = User::factory()->create(['role' => 'user', 'name' => 'テスト花子']);

        $today = Carbon::today()->format('Y-m-d');
        AttendanceRecord::factory()->create(['user_id' => $user1->id, 'date' => $today]);
        AttendanceRecord::factory()->create(['user_id' => $user2->id, 'date' => $today]);

        $response = $this->actingAs($admin)->get("/admin/attendance/list?date={$today}");

        $response->assertStatus(200)
            ->assertSee('テスト太郎')
            ->assertSee('テスト花子');
    }

    public function test_遷移した際に現在の日付が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $today = Carbon::today()->format('Y-m-d');

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200)
            ->assertSee($today);
    }

    public function test_前日を押下した時に前の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $response = $this->actingAs($admin)->get("/admin/attendance/list?date={$yesterday}");

        $response->assertStatus(200)
            ->assertSee($yesterday);
    }

    public function test_翌日を押下した時に次の日の勤怠情報が表示される()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        $response = $this->actingAs($admin)->get("/admin/attendance/list?date={$tomorrow}");

        $response->assertStatus(200)
            ->assertSee($tomorrow);
    }
}
