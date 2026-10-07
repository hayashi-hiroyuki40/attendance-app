<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class No04_CurrentDateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_現在の日時情報が_u_iと同じ形式で出力されている()
    {
        $user = User::factory()->create(['role' => 'user']);
        Carbon::setTestNow('2026-09-30 10:00:00');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('2026年9月30日(水)');
        $response->assertSee('10:00');
    }
}
