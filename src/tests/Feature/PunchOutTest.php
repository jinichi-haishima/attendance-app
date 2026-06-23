<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AttendanceRecord;

class PunchOutTest extends TestCase
{
    use RefreshDatabase;

    //ログインユーザーを作成
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);
    }

    /**
     * 退勤ボタンが正しく機能すること
     */
    public function test_punch_out_button_functionality(): void
    {
        Carbon::setTestNow(now());

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subHours(3),
            'punch_out_time' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $response = $this->actingAs($this->user)->post('/attendance/punch-out');
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'punch_out_time' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

        /**
        * 退勤時刻が勤怠一覧で確認できること
         */
    public function test_punch_out_time_displayed_in_attendance_list(): void
    {
        $todayDate = now()->isoFormat('MM/DD');

        Carbon::setTestNow(now()->setTime(10, 0, 0)); // テスト用に現在時間を固定
        $response = $this->actingAs($this->user)->post('/attendance/punch-in');
        $response->assertStatus(302);

        Carbon::setTestNow(now()->setTime(19, 0, 0)); // テスト用に現在時間を固定
        $response = $this->actingAs($this->user)->post('/attendance/punch-out');
        $response->assertStatus(302);

        $response = $this->actingAs($this->user)->get('/attendance-list');
        $response->assertStatus(200);

        $response->assertSeeInOrder([
            $todayDate, // 今日の日付
            '10:00', // 出勤時刻
            '19:00', // 退勤時刻
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(10, 0, 0),
            'punch_out_time' => now()->setTime(19, 0, 0),
        ]);
        Carbon::setTestNow();
    }
}
