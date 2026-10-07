<?php

namespace Database\Factories;

use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
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
            'attendance_record_id' => AttendanceRecord::factory(),
            'user_id' => User::factory(),
            'clock_in' => $date->format('Y-m-d 09:00:00'),
            'clock_out' => $date->format('Y-m-d 18:00:00'),
            'comment' => fake()->realText(15),
            'status' => '承認待ち',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => '承認済み',
        ]);
    }
}
