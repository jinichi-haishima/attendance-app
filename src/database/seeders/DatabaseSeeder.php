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

    // 💡 1. 一般ユーザー（User1）を取得
    $user1 = User::where('is_admin', false)->first();

    if (!$user1) return;

        // --- 📊 A. 過去5ヶ月分のデータ作成ループ ---
        for ($i = 5; $i >= 1; $i--) {
            $targetMonth = Carbon::now()->subMonths($i);
            $workDaysCount = 0;

            $date = $targetMonth->copy()->startOfMonth();
            while ($date->month == $targetMonth->month) {
                // 教材通り、各月平日「15日間」のデータを生成
                if ($date->isWeekday() && $workDaysCount < 15) {
                    $in = '09:00:00';
                    $out = '18:00:00';

                    $this->createAttendance($user1->id, $date->copy(), $in, $out);
                    $workDaysCount++;
                }
                $date->addDay();
            }
        }

        // --- 📊 B. 当月のデータ (今日が何日であれ、確実に17日分作る) ---
        // 💡 複雑な条件を辞めて、「今月の1日」から順番に平日を25日分、強制的に配列に確保します
        $currentMonth = Carbon::now()->startOfMonth();
        $availableDates = [];

        for ($day = 0; $day < 30; $day++) {
            if ($currentMonth->isWeekday()) {
                $availableDates[] = $currentMonth->copy();
            }
            $currentMonth->addDay();
        }

        // 17日分のパターンを定義
        $patterns = [
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
