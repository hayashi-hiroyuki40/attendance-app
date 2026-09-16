<?php

namespace Database\Seeders;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            for ($i = 30; $i >= 0; $i--) {
                $date = today()->subDays($i);

                if ($date->isWeekend()) {
                    continue;
                }

                $clockIn = (clone $date)->setTime(8, 45)->addMinutes(rand(0, 30));
                $clockOut = (clone $date)->setTime(17, 30)->addMinutes(rand(0, 60));

                $attendance = AttendanceRecord::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                ]);

                AttendanceBreak::create([
                    'attendance_records_id' => $attendance->id,
                    'break_in' => (clone $date)->setTime(12, 0),
                    'break_out' => (clone $date)->setTime(13, 0),
                ]);
            }
        }
    }
}
