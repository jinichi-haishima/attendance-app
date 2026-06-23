<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠打刻画面の現在時間が表示されること 
     */
    public function test_attendance_clock_is_display(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee(now()->format('Y年n月j日'));
        $response->assertSee(now()->format('H:i'));
    }

    /**
     * 勤怠打刻画面で、勤務外のステータスが表示されること
     */
    public function test_attendance_status_is_logged_when_not_working(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /**
     * 勤怠打刻画面で、出勤中のステータスが表示されること
     */
    public function test_attendance_status_is_logged_when_working(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => now()->subHours(2), // 2時間前に出勤
            'punch_out_time' => null, // まだ退勤していない
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /**
     * 勤怠打刻画面で、休憩中のステータスが表示されること
     */
    public function test_attendance_status_is_logged_when_rested(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => now()->setTime(9, 0, 0), // 9:00に出勤
            'punch_out_time' => null, // まだ退勤していない
        ]);

        RestRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now()->setTime(12, 0, 0), // 12:00に休憩開始
            'rest_out_time' => null, // まだ休憩終了していない
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /**
     * 勤怠打刻画面で、退勤中のステータスが表示されること
     */
    public function test_attendance_status_is_logged_when_off_working(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => now()->subHours(8), // 8時間前に出勤
            'punch_out_time' => now(), // 退勤済み
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('退勤済み');
    }

    /** 
     * 出勤ボタンが正しく機能すること
     */
    public function test_attendance_button_works(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)->post(route('attendance.punch-in'));

        $response->assertStatus(302); // リダイレクトされることを確認
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'punch_in_time' => now(),
        ]);
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中'); 
    }

    /** 
     * 出勤は1日1回しかできないこと
     */
    public function test_attendance_button_is_not_displayed_after_punch_out(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => now()->setTime(9, 0, 0), 
            'punch_out_time' => now()->setTime(15, 0, 0), 
        ]);


        $response = $this->actingAs($user)->get('/attendance');

        // 出勤する際のアクション（送信先URL）が画面に含まれていないことを確認
        $response->assertDontSee('action="http://localhost/attendance/punch-in"');

        // または、出勤ボタンそのもののタグが存在しないことを確認
        $response->assertDontSee('value="work_start"');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できること
     */
    public function test_attendance_time_is_displayed_in_attendance_list(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($user)->post(route('attendance.punch-in'));

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee(now()->format('H:i')); // 出勤時刻が表示されていることを確認
    }
}
