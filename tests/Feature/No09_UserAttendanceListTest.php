<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No09_UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分が行った勤怠情報が全て表示されている()
    {
        $user = User::factory()->create(['role' => 'user']);
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'status' => '出勤中',
            'date' => now()->format('Y-m-d'),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $record->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee(Carbon::parse($record->date)->format('m/d'))
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('1:00')
            ->assertSee('8:00');
    }

    public function test_勤怠一覧画面に遷移した際に現在の月が表示される()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertSee(now()->format('Y/m'));
    }

    public function test_前月を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/attendance/list?month='.now()->subMonth()->format('Y-m'));

        $response->assertSee(now()->subMonth()->format('Y-m'));
    }

    public function test_翌月を押下した時に表示月の前月の情報が表示される()
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/attendance/list?month='.now()->addMonth()->format('Y-m'));

        $response->assertSee(now()->addMonth()->format('Y-m'));
    }

    public function test_詳細を押下するとその日の勤怠詳細画面に遷移する()
    {
        $user = User::factory()->create(['role' => 'user']);
        $record = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'status' => '出勤中',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');
        $response->assertStatus(200);

        $response = $this->actingAs($user)->get("/attendance/{$record->id}");

        $response->assertStatus(200);
    }
}
