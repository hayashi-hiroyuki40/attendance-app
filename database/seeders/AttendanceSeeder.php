<?php

namespace Database\Seeders;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
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
                    'attendance_record_id' => $attendance->id,
                    'break_in' => (clone $date)->setTime(12, 0),
                    'break_out' => (clone $date)->setTime(13, 0),
                ]);
            }
        }

        $user1 = User::where('email', 'user1@example.com')->first();

        if (! $user1) {
            return;
        }

        $now = Carbon::now();

        for ($m = 5; $m >= 1; $m--) {
            $targetMonth = $now->copy()->subMonths($m);
            $dayCount = 0;
            $date = $targetMonth->copy()->startOfMonth();

            while ($date->lte($targetMonth->copy()->endOfMonth()) && $dayCount < 15) {
                if ($date->isWeekday()) {
                    $this->saveRecord($user1->id, $date, '9:00', '18:00');
                    $dayCount++;
                }
                $date->addDay();
            }
        }

        $currentMonth = $now->copy()->startOfMonth();
        $dayCount = 0;
        $date = $currentMonth->copy();

        while ($date->lte($currentMonth->copy()->endOfMonth()) && $dayCount < 17) {
            if ($date->isWeekday()) {
                if ($dayCount < 10) {
                    $this->saveRecord($user1->id, $date, '9:00', '18:00');
                } elseif ($dayCount < 13) {
                    $this->saveRecord($user1->id, $date, '9:00', '20:00');
                } elseif ($dayCount < 15) {
                    $this->saveRecord($user1->id, $date, '9:30', '18:00');
                } elseif ($dayCount == 15) {
                    $this->saveRecord($user1->id, $date, '9:00', '17:00');
                } else {
                    $this->saveRecord($user1->id, $date, '8:00', '21:00');
                }
                $dayCount++;
            }
            $date->addDay();
        }
    }

    private function saveRecord($userId, $date, $clockInTime, $clockOutTime): void
    {
        $attendance = AttendanceRecord::create([
            'user_id' => $userId,
            'date' => $date->toDateString(),
            'clock_in' => $date->copy()->setTimeFromTimeString($clockInTime),
            'clock_out' => $date->copy()->setTimeFromTimeString($clockOutTime),
            'status' => '退勤済',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $attendance->id,
            'break_in' => $date->copy()->setTime(12, 0, 0),
            'break_out' => $date->copy()->setTime(13, 0, 0),
        ]);
    }
}
