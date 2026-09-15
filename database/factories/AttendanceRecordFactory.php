<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeThisYear();

        return [
            'user_id' => User::factory(),
            'date' => $date->format('Y-m-d'),
            'clock_in' => $date->format('Y-m-d 09:00:00'),
            'clock_out' => $date->format('Y-m-d 18:00:00'),
        ];
    }
}
