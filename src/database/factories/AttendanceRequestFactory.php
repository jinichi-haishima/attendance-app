<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\AttendanceRecord;

class AttendanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(), // ユーザーIDをランダムに生成
            'attendance_record_id' => AttendanceRecord::factory(), // 勤怠レコードIDをランダムに生成
            'status' => 'pending', // デフォルトのステータスは「承認待ち」
            'reason' => $this->faker->sentence(20), 
        ];
    }
}
