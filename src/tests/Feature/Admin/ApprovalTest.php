<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRequest;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 承認待ちの修正申請が全て表示されていること
     */
    public function test_admin_can_approve_attendance_correction_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();

        $request1 = AttendanceRequest::factory()->create([
            'user_id' => $staff1->id,
            'reason' => '遅刻の理由',
            'status' => 'pending',
        ]);

        $request2 = AttendanceRequest::factory()->create([
            'user_id' => $staff2->id,
            'reason' => '早退の理由',
            'status' => 'pending',
        ]);

        $request3 = AttendanceRequest::factory()->create([
            'user_id' => $staff1->id,
            'reason' => '別の理由',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('attendance-requests.index'));

        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee($request1->reason);

        $response->assertSee($staff2->name);
        $response->assertSee($request2->reason);

        $response->assertDontSee($request3->reason);
    }

    /**
     * 承認済みの修正申請が全て表示されていること
     */
    public function test_admin_can_view_approved_attendance_correction_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();

        $request1 = AttendanceRequest::factory()->create([
            'user_id' => $staff1->id,
            'reason' => '遅刻の理由',
            'status' => 'approved',
        ]);

        $request2 = AttendanceRequest::factory()->create([
            'user_id' => $staff2->id,
            'reason' => '早退の理由',
            'status' => 'approved',
        ]);

        $request3 = AttendanceRequest::factory()->create([
            'user_id' => $staff1->id,
            'reason' => '別の理由',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('attendance-requests.index', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee($staff1->name);
        $response->assertSee($request1->reason);

        $response->assertSee($staff2->name);
        $response->assertSee($request2->reason);

        $response->assertDontSee($request3->reason);
    }

    /**
     * 修正申請の詳細内容が正しく表示されていること
     */
    public function test_admin_can_view_attendance_correction_request_detail(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create();

        $date = now();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'punch_in_time' => $date->copy()->setTime(8, 0, 0)->format('H:i'),
            'punch_out_time' => $date->copy()->setTime(12, 0, 0)->format('H:i'),
        ]);

        $request = AttendanceRequest::factory()->create([
            'user_id' => $staff->id,
            'attendance_record_id' => $attendanceRecord->id,
            'punch_in_time' => $date->copy()->setTime(13, 0, 0)->format('H:i'),
            'punch_out_time' => $date->copy()->setTime(17, 0, 0)->format('H:i'),
            'reason' => '遅刻の理由',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.approval', ['attendance_correct_request_id' => $request->id]));

        $response->assertStatus(200);
        $response->assertSee($staff->name);
        $response->assertSee($request->reason);
        $response->assertSee($request->punch_in_time);
        $response->assertSee($request->punch_out_time);
    }

    /**
     * 修正申請の承認処理が正しく行われる
     */
    public function test_admin_can_approve_attendance_correction_request_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $staff = User::factory()->create();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $staff->id,
            'punch_in_time' => now()->subHours(8),
            'punch_out_time' => now(),
        ]);

        $request = AttendanceRequest::factory()->create([
            'user_id' => $staff->id,
            'attendance_record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->subHours(7),
            'punch_out_time' => now(),
            'reason' => '遅刻の理由',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.attendance.approval.update', ['attendance_correct_request_id' => $request->id]), [
            'action' => 'approve',
        ]);

        $response->assertStatus(200);

        $request->refresh();
        $this->assertEquals('承認済み', $request->status);
        $attendanceRecord->refresh();
        $this->assertEquals($request->punch_in_time, $attendanceRecord->punch_in_time);
        $this->assertEquals($request->punch_out_time, $attendanceRecord->punch_out_time);
    }
}