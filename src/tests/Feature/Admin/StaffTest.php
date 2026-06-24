<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    /**
    * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    */
    public function test_admin_can_view_staff_name_and_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee($staff1->email);
        $response->assertSee($staff2->name);
        $response->assertSee($staff2->email);
    }

    /**
     *ユーザーの勤怠情報が正しく表示される
     */
    public function test_admin_can_view_staff_attendance_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create();

        $todayAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'punch_in_time' => now()->subHours(8),
            'punch_out_time' => now(),
        ]);

        $yesterdayAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'punch_in_time' => now()->subDay()->subHours(8),
            'punch_out_time' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.list'));

        $staffUrl = route('admin.staff.show', ['id' => $staff->id]);
        $response = $this->actingAs($admin)->get($staffUrl);
        $response->assertStatus(200);
        $response->assertSee($todayAttendanceRecord->punch_in_time->format('H:i'));
        $response->assertSee($todayAttendanceRecord->punch_out_time->format('H:i'));
        $response->assertSee($yesterdayAttendanceRecord->punch_in_time->format('H:i'));
        $response->assertSee($yesterdayAttendanceRecord->punch_out_time->format('H:i'));
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_admin_can_view_previous_month_attendance_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create();

        $lastMonthAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'punch_in_time' => now()->subMonth()->subHours(8),
            'punch_out_time' => now()->subMonth(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.show', ['id' => $staff->id]));
        $response->assertStatus(200);
        $response->assertDontSee($lastMonthAttendanceRecord->punch_in_time->format('H:i'));

        $prevMonth = now()->subMonth()->format('Y-m');
        $response = $this->actingAs($admin)->get(route('admin.staff.show', ['id' => $staff->id, 'date' => $prevMonth]));

        $response->assertStatus(200);
        $response->assertSee($lastMonthAttendanceRecord->punch_in_time->format('H:i'));
        $response->assertSee($lastMonthAttendanceRecord->punch_out_time->format('H:i'));
    }

    /**
     * 「翌月」を押下した時に表示月の次月の情報が表示される
     */
    public function test_admin_can_view_next_month_attendance_records(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create();

        $nextMonthAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'punch_in_time' => now()->addMonth()->subHours(8),
            'punch_out_time' => now()->addMonth(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.show', ['id' => $staff->id]));
        $response->assertStatus(200);
        $response->assertDontSee($nextMonthAttendanceRecord->punch_in_time->format('H:i'));

        $nextMonth = now()->addMonth()->format('Y-m');
        $response = $this->actingAs($admin)->get(route('admin.staff.show', ['id' => $staff->id, 'date' => $nextMonth]));

        $response->assertStatus(200);
        $response->assertSee($nextMonthAttendanceRecord->punch_in_time->format('H:i'));
        $response->assertSee($nextMonthAttendanceRecord->punch_out_time->format('H:i'));
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_admin_can_navigate_to_attendance_detail_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.show', ['id' => $staff->id]));
        $response->assertStatus(200);

        $detailUrl = route('admin.detail', ['id' => $staff->id]) . '?date=' . $attendanceRecord->punch_in_time->format('Y-m-d');
        $response->assertSee($detailUrl);

        $response = $this->actingAs($admin)->get($detailUrl);
        $response->assertStatus(200);
        $response->assertSee($attendanceRecord->punch_in_time->format('H:i'));
        $response->assertSee($attendanceRecord->punch_out_time->format('H:i'));
    }
}