<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'punch_in_time' => now()->setTime(9, 0, 0), // デフォルトは9:00出勤
            'punch_out_time' => null,   
        ];
    }
}
