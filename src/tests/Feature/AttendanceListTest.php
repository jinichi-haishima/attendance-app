<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;

class AttendanceListTest extends TestCase
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
     * 自分が行った勤怠情報が全て表示されていること
     */
    public function test_attendance_list_shows_all_records(): void
    {
        // 1日目の勤怠データを作成
        $yesterday = now()->subDay();
        $record1 = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => $yesterday->copy()->setTime(9, 0, 0),
            'punch_out_time' => $yesterday->copy()->setTime(18, 0, 0),
        ]); 

        RestRecord::factory()->create([
            'attendance_record_id' => $record1->id,
            'rest_in_time' => $yesterday->copy()->setTime(12, 0, 0),
            'rest_out_time' => $yesterday->copy()->setTime(13, 0, 0),
        ]);

        // 2日目の勤怠データを作成
        $today = now();
        $record2 = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => $today->copy()->setTime(10, 0, 0),
            'punch_out_time' => $today->copy()->setTime(19, 0, 0),
        ]);

        RestRecord::factory()->create([
            'attendance_record_id' => $record2->id,
            'rest_in_time' => $today->copy()->setTime(13, 0, 0),
            'rest_out_time' => $today->copy()->setTime(14, 30, 0),
        ]);

        $response = $this->actingAs($this->user)->get('/attendance-list');
        $response->assertStatus(200);

        // 勤怠データが表示されているか確認
        $response->assertSeeInOrder([
            $yesterday->isoFormat('MM/DD'), // 1日目の日付
            '09:00', // 1日目の出勤時刻
            '18:00', // 1日目の退勤時刻
            '1:00', // 1日目の休憩時間
            '8:00', // 1日目の勤務時間（休憩時間を除く）
            $today->isoFormat('MM/DD'), // 2日目の日付
            '10:00', // 2日目の出勤時刻
            '19:00', // 2日目の退勤時刻
            '1:30', // 2日目の休憩時間
            '7:30', // 2日目の勤務時間（休憩時間を除く）
        ]);
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示されていること
     */
    public function test_current_month_is_displayed(): void
    {
        $this->travelTo(now()->startOfMonth()); // テスト用に現在時間を月初に固定

        $response = $this->actingAs($this->user)->get(route('attendance-records.index'));
        $response->assertStatus(200);

        $response->assertSee(now()->isoFormat('YYYY年MM月')); // 例: 2026年06月   
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示されること
     */
    public function test_previous_month_is_displayed(): void
    {
        $this->travelTo(now()->startOfMonth());

        $response = $this->actingAs($this->user)->get(route('attendance-records.index'));
        $response->assertStatus(200);

        $prevMonthParam = now()->subMonth()->format('Y-m');
        $prevMonthDisplay = now()->subMonth()->format('Y年m月');

        $response = $this->actingAs($this->user)->get(route('attendance-records.index', ['date' => $prevMonthParam])); 
        $response->assertStatus(200);
        $response->assertSee($prevMonthDisplay);
    }

    /**
     * 「翌月」を押下した時に表示月の次月の情報が表示されること
     */
    public function test_next_month_is_displayed(): void
    {
        $this->travelTo(now()->startOfMonth());

        $response = $this->actingAs($this->user)->get(route('attendance-records.index'));
        $response->assertStatus(200);

        $nextMonthParam = now()->addMonth()->format('Y-m');
        $nextMonthDisplay = now()->addMonth()->format('Y年m月');

        $response = $this->actingAs($this->user)->get(route('attendance-records.index', ['date' => $nextMonthParam])); 
        $response->assertStatus(200);
        $response->assertSee($nextMonthDisplay);
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移すること
     */
    public function test_detail_button_navigates_to_detail_page(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->get('/attendance-list');
        $response->assertStatus(200);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail',[
            'id' => $attendanceRecord->id
        ]));
        
        $response->assertStatus(200);
        $response->assertSee($attendanceRecord->punch_in_time->format('H:i')); 
        $response->assertSee($attendanceRecord->punch_out_time->format('H:i')); 
    }
}
