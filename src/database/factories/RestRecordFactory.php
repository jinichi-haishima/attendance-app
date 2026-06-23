<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RestRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'attendance_record_id' => \App\Models\AttendanceRecord::factory(),
            'rest_in_time' => now()->setTime(12, 0, 0), // デフォルトは12:00休憩開始
            'rest_out_time' => now()->setTime(13, 0, 0), // デフォルトは13:00休憩終了
        ];
    }
}
