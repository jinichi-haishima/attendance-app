<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;

class RestTest extends TestCase
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
     * 休憩ボタンが正しく機能すること
     */
    public function test_rest_button_functionality(): void
    {
        Carbon::setTestNow(now());

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subHours(3),
            'punch_out_time' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');
        
        $response = $this->actingAs($this->user)->post('/attendance/rest-in');
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('rest_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now(),
            'rest_out_time' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    /**
     * 休憩は1日に何度も出来ること
     */
    public function test_multiple_rest_periods_in_a_day(): void
    {
        Carbon::setTestNow(now());

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subHours(8),
            'punch_out_time' => null,
        ]);

        $restRecord1 = RestRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now()->subHours(5),
            'rest_out_time' => now()->subHours(4),
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $response = $this->actingAs($this->user)->post('/attendance/rest-in');
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('rest_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now(),
            'rest_out_time' => null,
        ]);

        Carbon::setTestNow();
    }

    /**
     * 休憩戻りボタンが正しく機能すること
     */
    public function test_rest_out_button_functionality(): void
    {
        Carbon::setTestNow(now());

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subHours(3),
            'punch_out_time' => null,
        ]);

        $restRecord = RestRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now()->subHours(1),
            'rest_out_time' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        $response = $this->actingAs($this->user)->post('/attendance/rest-out');
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('rest_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => $restRecord->rest_in_time,
            'rest_out_time' => now(),
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    /**
    * 休憩戻は1日に何回でも出来ること
    */
    public function test_multiple_rest_outs_in_a_day(): void
    {
        Carbon::setTestNow(now()->subHours(3));

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subHours(5),
            'punch_out_time' => null,
        ]);

        $restRecord1 = RestRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now()->subHours(4),
            'rest_out_time' => now()->subHours(3),
        ]);
        Carbon::setTestNow(now()->addHours(3));

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $response = $this->actingAs($this->user)->post('/attendance/rest-in');
        $response->assertStatus(302);
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('rest_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now(),
            'rest_out_time' => null,
        ]);

        $response = $this->actingAs($this->user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できること
     */
    public function test_rest_times_are_displayed_in_attendance_list(): void
    {
        Carbon::setTestNow(now());

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(8, 25, 0),
            'punch_out_time' => null,
        ]);

        $restRecord = RestRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now()->setTime(11, 0, 0),
            'rest_out_time' => now()->setTime(12, 30, 0),
        ]);

        $todayDate = now()->isoFormat('MM/DD');

        $response = $this->actingAs($this->user)->get('/attendance-list');
        $response->assertStatus(200);
        $response->assertSee('1:30'); 

        // 勤怠一覧画面で、日付、出勤時間、休憩時間の順番で表示されていることを確認
        $response->assertSeeInOrder([
            $todayDate,
            '08:25',
            '1:30'
        ]);

        $this->assertDatabaseHas('rest_records', [
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => $restRecord->rest_in_time,
            'rest_out_time' => $restRecord->rest_out_time,
        ]);

        Carbon::setTestNow();
    }

}