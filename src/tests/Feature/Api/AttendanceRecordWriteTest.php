<?php

namespace Tests\Feature\api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Laravel\Sanctum\Sanctum;

class AttendanceRecordWriteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * POST /api/v1/attendance-records で勤怠が作成される
     */
    public function test_can_create_attendance_record(): void
    {
        $user = User::factory()->create();
        // 認証済みユーザーとしてリクエストを送信
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response->assertStatus(201);
        $response->assertJsonFragment([
            'user_id' => $user->id,
            'date' => '2026-01-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'punch_in_time' => '2026-01-01 09:00:00',
            'punch_out_time' => '2026-01-01 18:00:00',
        ]);
    }

    /**
     * バリデーションエラー時に422と日本語エラーメッセージが返る
     */
    public function test_validation_error_returns_422_with_japanese_messages(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'user_id'   => $user->id,
            'date'      => '', // 空の値を送信してバリデーションエラーを発生させる
            'clock_in'  => '', 
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'date'      => '勤怠日は必須です。',
            'clock_in'  => '出勤時刻は必須です。',
        ]);
    }

    /**
     * PUT /api/v1/attendance-records/{attendanceRecord} で勤怠が更新される
     */
    public function test_can_update_attendance_record(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => '2026-01-01 09:00:00',
            'punch_out_time' => '2026-01-01 18:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/attendance-records/{$attendanceRecord->id}", [
            'date' => '2026-01-01',
            'clock_in' => '09:30:00',
            'clock_out' => '19:00:00',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'clock_in' => '09:30:00',
            'clock_out' => '19:00:00',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'punch_in_time' => '2026-01-01 09:30:00',
            'punch_out_time' => '2026-01-01 19:00:00',
        ]);
    }

    /**
     * 存在しない ID に対して PUT を実行すると 404 を返す
     */
    public function test_update_returns_404_if_not_found(): void
    {
        $user = User::factory()->create();
        $missingRecordId = 99999; // 存在しない ID
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/attendance-records/{$missingRecordId}", [
            'date' => '2026-01-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response->assertStatus(404);
    }

    /**
     * DELETE /api/v1/attendance-records/{attendanceRecord} で勤怠が削除される
     */
    public function test_can_delete_attendance_record(): void
    {
        $user = User::factory()->create();
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'punch_in_time' => '2026-01-01 09:00:00',
            'punch_out_time' => '2026-01-01 18:00:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/attendance-records/{$attendanceRecord->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    /**
     *存在しない ID に対して DELETE を実行
     */
    public function test_delete_returns_404_if_not_found(): void
    {
        $user = User::factory()->create();
        $missingRecordId = 99999; // 存在しない ID
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/attendance-records/{missingRecordId}");

        $response->assertStatus(404);
    }
}