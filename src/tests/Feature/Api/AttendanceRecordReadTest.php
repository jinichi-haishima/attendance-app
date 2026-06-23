<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\AttendanceRecord;
use Database\Seeders\DatabaseSeeder;

class AttendanceRecordReadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GET /api/v1/attendance-records で勤怠一覧が JSON で取得できる
     */
    public function test_can_get_attendance_records(): void
    {
        // データベースシーダーの実行
        $this->seed(DatabaseSeeder::class);

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                ]
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]
        ]);
    }

    /**
     * GET /api/v1/attendance-records/{attendanceRecord} で勤怠詳細が JSON で取得できる
     */
    public function test_can_get_attendance_record_detail(): void
    {
        $this->seed(DatabaseSeeder::class);
        $attendanceRecord = AttendanceRecord::first();

        $response = $this->getJson("/api/v1/attendance-records/{$attendanceRecord->id}");

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',
                'user',
                'breaks',
                'applications',
            ]
        ]);
    }

    /**
     * 存在しない ID では 404 とエラー JSON が返る
     */
    public function test_get_nonexistent_attendance_record_returns_404(): void
    {
        $this->seed(DatabaseSeeder::class);
        $nonexistentId = 999999; // 存在しない ID を指定

        $response = $this->getJson("/api/v1/attendance-records/{$nonexistentId}");

        $response->assertStatus(404);

        $response->assertJson([
            'error' => '勤怠情報が見つかりませんでした。'
        ]);
    }
}