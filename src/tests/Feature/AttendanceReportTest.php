<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use Laravel\Sanctum\Sanctum;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ゲストはレポートページにアクセスできない
     */
    public function test_guest_cannot_access_report_page(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertStatus(302);
        $response->assertRedirect('/login'); 
    }

    /**
     * 認証ユーザーの統計情報が正しく計算される
     */
    public function test_authenticated_user_sees_correct_report_data_from_seeded_database(): void
    {
        $this->seed(); // データベースにシードデータを挿入

        $user1 = User::where('email', 'user1@example.com')->first();

        Sanctum::actingAs($user1);

        $response = $this->get('attendance/report');
        $response->assertStatus(200);
        $response->assertViewHas('summary');
        $response->assertViewHas('graphData');

        //⭐️ ここで、シードデータに基づいて期待される統計情報を確認する
        $response->assertSee('744h 0m'); // 過去6ヶ月 総労働時間
        $response->assertSee('10h 0m');  // 過去6ヶ月 総残業時間
        $response->assertSee('8h 5m/日'); // 過去6ヶ月 平均労働時間/日
        $response->assertSee('16回');     // 遅刻回数
        $response->assertSee('1回');     // 早退回数
        $response->assertSee('4日');     // 長時間労働回数
    }

    /**
     * 勤怠記録がないユーザーで安全に処理される
     */
    public function test_user_with_no_attendance_records_sees_empty_report(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->get('attendance/report');

        $response->assertStatus(200);
        $response->assertViewHas('summary');
        $response->assertViewHas('graphData');

        // 勤怠記録がない場合、統計情報はゼロまたは空であることを確認
        $response->assertSee('0h 0m'); 
        $response->assertSee('0h 0m/日');
        $response->assertSee('0回');
        $response->assertSee('0日');
    }
}
