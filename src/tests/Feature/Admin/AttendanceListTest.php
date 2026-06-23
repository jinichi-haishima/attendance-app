<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;

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
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_attendance_list_shows_all_users_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $User1 = User::factory()->create();
        $user2 = User::factory()->create();

        $attendanceRecord1 = AttendanceRecord::factory()->create([
            'user_id' => $User1->id,
            'punch_in_time' => now()->subHours(8),
            'punch_out_time' => now(),
        ]);

        $attendanceRecord2 = AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'punch_in_time' => now()->subHours(7),
            'punch_out_time' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertSee($User1->name);
        $response->assertSee($user2->name);
        $response->assertSee($attendanceRecord1->punch_in_time->format('H:i'));
        $response->assertSee($attendanceRecord2->punch_in_time->format('H:i'));
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_attendance_list_shows_current_date(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.index'));
        $response->assertStatus(200);
        $response->assertSee(now()->format('Y年m月d日'));
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_attendance_list_shows_previous_day_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => now()->subDay()->setTime(9, 0, 0),
            'punch_out_time' => now()->subDay()->setTime(18, 0, 0),
        ]);

        $todayResponse = $this->actingAs($admin)->get(route('admin.index'));
        $todayResponse->assertStatus(200);

        $previousDayUrl = route('admin.index', ['date' => now()->subDay()->format('Y-m-d')]);
        $todayResponse->assertSee($previousDayUrl);

        $previousDayResponse = $this->actingAs($admin)->get($previousDayUrl);
        $previousDayResponse->assertStatus(200);

        $previousDayResponse->assertSee($attendanceRecord->punch_in_time->format('H:i'));
        $previousDayResponse->assertSee($attendanceRecord->punch_out_time->format('H:i'));
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_attendance_list_shows_next_day_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => now()->addDay()->setTime(9, 0, 0),
            'punch_out_time' => now()->addDay()->setTime(18, 0, 0),
        ]);

        $todayResponse = $this->actingAs($admin)->get(route('admin.index'));
        $todayResponse->assertStatus(200);

        $nextDayUrl = route('admin.index', ['date' => now()->addDay()->format('Y-m-d')]);
        $todayResponse->assertSee($nextDayUrl);

        $nextDayResponse = $this->actingAs($admin)->get($nextDayUrl);
        $nextDayResponse->assertStatus(200);

        $nextDayResponse->assertSee($attendanceRecord->punch_in_time->format('H:i'));
        $nextDayResponse->assertSee($attendanceRecord->punch_out_time->format('H:i'));
    }
}