<?php

namespace Database\Factories;

use App\Models\AttendanceCorrection;
use App\Models\CorrectionBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorrectionBreak>
 */
class CorrectionBreakFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_correction_id' => AttendanceCorrection::factory(),
            'break_in' => fake()->dateTime()->format('Y-m-d 12:00:00'),
            'break_out' => fake()->dateTime()->format('Y-m-d 13:00:00'),
        ];
    }
}
