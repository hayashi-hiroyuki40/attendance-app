<?php

namespace Tests\Feature;

use Tests\TestCase;

class No11_UserAttendanceCorrectionTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
