<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 未認証時に書き込み系 API で 401 が返ること
     */
    public function test_unauthenticated_user_cannot_access_write_api(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id' => 1,
            'date' => '2026-01-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    /**
     * 認証なしで PUT を実行すると 401 が返る
     */
    public function test_put_returns_401_if_unauthenticated(): void
    {
        $response = $this->putJson('/api/v1/attendance-records/1', [
            'date' => '2026-01-01',
            'clock_in' => '09:00:00',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * 認証なしで DELETE を実行すると 401 が返る
     */
    public function test_delete_returns_401_if_unauthenticated(): void
    {
        $response = $this->deleteJson('/api/v1/attendance-records/1');

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * 認証済みユーザーは自分の勤怠を更新・削除できる
     */
    public function test_authenticated_user_can_update_and_delete_own_attendance_record(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => '2026-01-01 09:00:00',
            'punch_out_time' => '2026-01-01 18:00:00',
        ]);

        // PUT リクエストで勤怠を更新
        $response = $this->putJson("/api/v1/attendance-records/{$attendanceRecord->id}", [
            'date' => '2026-01-01',
            'clock_in' => '09:30:00',
            'clock_out' => '19:00:00',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'punch_in_time' => '2026-01-01 09:30:00',
            'punch_out_time' => '2026-01-01 19:00:00',
        ]);

        // DELETE リクエストで勤怠を削除
        $response = $this->deleteJson("/api/v1/attendance-records/{$attendanceRecord->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    /**
     * 他ユーザーの勤怠を更新・削除しようとすると 403 が返る
     */
    public function test_authenticated_user_cannot_update_or_delete_others_attendance_record(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'punch_in_time' => '2026-01-01 09:00:00',
            'punch_out_time' => '2026-01-01 18:00:00',
        ]);

        $this->actingAs($user1);

        // 他ユーザーの勤怠を更新しようとする
        $response = $this->putJson("/api/v1/attendance-records/{$attendanceRecord->id}", [
            'date' => '2026-01-01',
            'clock_in' => '09:30:00',
            'clock_out' => '19:00:00',
        ]);

        $response->assertStatus(403);

        // 他ユーザーの勤怠を削除しようとする
        $response = $this->deleteJson("/api/v1/attendance-records/{$attendanceRecord->id}");

        $response->assertStatus(403);

    }
}