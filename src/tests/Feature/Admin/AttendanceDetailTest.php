<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     */
    public function test_attendance_detail_shows_correct_information(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->setTime(0, 0, 0);
        
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => $targetDate->copy()->addHours(9),
            'punch_out_time' => $targetDate->copy()->addHours(18),
        ]);

        $restRecord = RestRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => $targetDate->copy()->addHours(12),
            'rest_out_time' => $targetDate->copy()->addHours(13),
        ]);

    
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get(route('admin.detail', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id]));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($attendanceRecord->punch_in_time->format('H:i'));
        $response->assertSee($attendanceRecord->punch_out_time->format('H:i'));
        $response->assertSee($restRecord->rest_in_time->format('H:i'));
        $response->assertSee($restRecord->rest_out_time->format('H:i'));
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_attendance_detail_shows_error_when_clock_in_after_clock_out(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->setTime(0, 0, 0);
        
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => $targetDate->copy()->addHours(9),
            'punch_out_time' => $targetDate->copy()->addHours(13),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->get(route('admin.detail', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id]));

        $response->assertStatus(200);
        $response = $this->post(route('admin.attendance.update', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id]), [
            'punch_in_time' => '15:00',
            'punch_out_time' => '13:00',
            'reason' => 'テストのため',
            'rest_records' => []
        ]);

        $response->assertSessionHasErrors([
            'punch_in_time' => '出勤時間もしくは退勤時間が不適切な値です']);

        $response = $this->followRedirects($response);
        $response->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_attendance_detail_shows_error_when_rest_in_after_clock_out(): void
    { 
        $user = User::factory()->create();
        $targetDate = now()->setTime(0, 0, 0);
        
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => $targetDate->copy()->addHours(9),
            'punch_out_time' => $targetDate->copy()->addHours(18),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $response =$this->actingAs($admin)->get(route('admin.detail', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id
            ]));

        $response = $this->post(route('admin.attendance.update', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id]), [
            'punch_in_time' => '09:00',
            'punch_out_time' => '18:00',
            'reason' => 'テストのため',
            'rest_records' => [
                'new' => [
                    'rest_in_time' => '19:00', 
                    'rest_out_time' => '20:00'
                ]
            ]
        ]);

        $response->assertSessionHasErrors();

        $response = $this->followRedirects($response);
        $response->assertSee('休憩時間が不適切な値です');
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_attendance_detail_shows_error_when_rest_out_after_clock_out(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->setTime(0, 0, 0);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => $targetDate->copy()->addHours(9),
            'punch_out_time' => $targetDate->copy()->addHours(13),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $response =$this->actingAs($admin)->get(route('admin.detail', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id
            ]));

        $response = $this->post(route('admin.attendance.update', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id
            ]), [
            'punch_in_time' => '09:00',
            'punch_out_time' => '13:00',
            'reason' => 'テストのため',
            'rest_records' => [
                'new' => [
                    'rest_in_time' => '12:00', 
                    'rest_out_time' => '15:00'
                ]
            ]
        ]);

        $response->assertSessionHasErrors();

        $response = $this->followRedirects($response);
        $response->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }
    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_attendance_detail_shows_error_when_reason_is_empty(): void
    {
        $user = User::factory()->create();
        $targetDate = now()->setTime(0, 0, 0);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => $targetDate->copy()->addHours(9),
            'punch_out_time' => $targetDate->copy()->addHours(18),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $response =$this->actingAs($admin)->get(route('admin.detail', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id
            ]));

        $response = $this->post(route('admin.attendance.update', [
            'date' => $targetDate->format('Y-m-d'),  
            'user_id' => $user->id
            ]), [
            'punch_in_time' => '10:00',
            'punch_out_time' => '18:00',
            'reason' => '',
            'rest_records' => [
                'new' => [
                    'rest_in_time' => '12:00', 
                    'rest_out_time' => '13:00'
                ]
            ]
        ]);

        $response->assertSessionHasErrors();

        $response = $this->followRedirects($response);
        $response->assertSee('備考を記入してください');
    }
}