<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\RestRecord;
use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $user1 = User::create([
            'name' => 'ユーザー1(一般)',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $user2 = User::create([
            'name' => 'ユーザー2(一般)',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        $user3 = User::create([
            'name' => 'ユーザー3(管理者)',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_admin' => true,
        ]);

    for ($i = 5; $i >= 1; $i--) {
            $targetMonth = Carbon::now()->subMonths($i);
            $workDaysCount = 0;

            // その月の1日から末日までループ
            $date = $targetMonth->copy()->startOfMonth();
            while ($date->month == $targetMonth->month) {
                // 平日（月〜金）かつ 15日に達するまで作成
                if ($date->isWeekday() && $workDaysCount < 15) {
                    $this->createAttendance($user1->id, $date->copy(), '09:00:00', '18:00:00');
                    $workDaysCount++;
                }
                $date->addDay();
            }
        }

        // --- 📊 B. 当月のデータ (計17日分のパターン出し分け) ---
        // 当月の平日を配列で取得
        $currentMonth = Carbon::now()->startOfMonth();
        $availableDates = [];
        while ($currentMonth->month == Carbon::now()->month) {
            if ($currentMonth->isWeekday() && $currentMonth->lt(Carbon::now())) {
                $availableDates[] = $currentMonth->copy();
            }
            $currentMonth->addDay();
        }

        // 17日分のパターンを定義
        $patterns = [
            // パターン名 => [出勤時間, 退勤時間, 日数]
            'normal'    => ['09:00:00', '18:00:00', 10], // 通常 10日
            'overtime'  => ['09:00:00', '20:00:00', 3],  // 残業 3日
            'late'      => ['09:30:00', '18:00:00', 2],  // 遅刻 2日
            'early'     => ['09:00:00', '17:00:00', 1],  // 早退 1日
            'long'      => ['08:00:00', '21:00:00', 1],  // 長時間 1日
        ];

        $dateIndex = 0;
        foreach ($patterns as $key => $info) {
            for ($j = 0; $j < $info[2]; $j++) {
                if (isset($availableDates[$dateIndex])) {
                    $this->createAttendance($user1->id, $availableDates[$dateIndex], $info[0], $info[1]);
                    $dateIndex++;
                }
            }
        }
    }
    private function createAttendance($userId, Carbon $date, $inTime, $outTime)
    {
        // 勤怠レコードの作成（clock_in_time などのカラム名はご自身の設計に合わせてください）
        $attendance = AttendanceRecord::create([
            'user_id' => $userId,
            'punch_in_time' => $date->copy()->setTimeFromTimeString($inTime),
            'punch_out_time' => $date->copy()->setTimeFromTimeString($outTime),
        ]);

        // ★ 固定休憩 12:00-13:00（1時間）の付与
        RestRecord::create([
            'attendance_record_id' => $attendance->id,
            'rest_in_time' => $date->copy()->setTimeFromTimeString('12:00:00'),
            'rest_out_time' => $date->copy()->setTimeFromTimeString('13:00:00'),
        ]);
    }
}
