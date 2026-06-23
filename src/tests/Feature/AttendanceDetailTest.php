<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;

class AttendanceDetailTest extends TestCase
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
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function test_attendance_detail_shows_user_name(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response->assertSee($this->user->name);
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function test_attendance_detail_shows_selected_date():void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subDays(3)->setTime(10, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response->assertSee($attendanceRecord->punch_in_time->format('Y年'));
        $response->assertSee($attendanceRecord->punch_in_time->isoFormat('M月D日'));
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_punch_in_out_time(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subDays(2)->setTime(9, 0, 0),
            'punch_out_time' => now()->subDays(2)->setTime(17, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response->assertSee($attendanceRecord->punch_in_time->format('H:i'));
        $response->assertSee($attendanceRecord->punch_out_time->format('H:i'));
    }
    
    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function test_attendance_detail_shows_rest_time(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->subDays(1)->setTime(9, 0, 0),
            'punch_out_time' => now()->subDays(1)->setTime(18, 0, 0),
        ]);

        $restRecord = RestRecord::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'rest_in_time' => now()->subDays(1)->setTime(13, 0, 0),
            'rest_out_time' => now()->subDays(1)->setTime(14, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response->assertSee($restRecord->rest_in_time->format('H:i'));
        $response->assertSee($restRecord->rest_out_time->format('H:i'));
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_attendance_detail_shows_error_for_invalid_time(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(10, 0, 0),
            'punch_out_time' => now()->setTime(15, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response = $this->actingAs($this->user)->post(route('attendance-records.store'), [
            'user_id' => $this->user->id,
            'record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->setTime(10, 0, 0)->format('H:i'),
            'punch_out_time' => now()->setTime(9, 0, 0)->format('H:i'),
            'rest_records' => [],
            'reason' => 'テスト理由',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['punch_in_time' => '出勤時間もしくは退勤時間が不適切な値です']);
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function test_attendance_detail_shows_error_for_invalid_rest_time(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response = $this->actingAs($this->user)->post(route('attendance-records.store'), [
            'user_id' => $this->user->id,
            'record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->setTime(9, 0, 0)->format('H:i'),
            'punch_out_time' => now()->setTime(18, 0, 0)->format('H:i'),
            'rest_records' => [
                [
                    'rest_in_time' => now()->setTime(17, 0, 0)->format('H:i'),
                    'rest_out_time' => now()->setTime(20, 0, 0)->format('H:i'),
                ],
            ],
            'reason' => 'テスト理由',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['rest_out_time_error' => '休憩時間もしくは退勤時間が不適切な値です']);
    }
    
    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function test_attendance_detail_shows_error_for_empty_reason(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response = $this->actingAs($this->user)->post(route('attendance-records.store'), [
            'user_id' => $this->user->id,
            'record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->setTime(9, 0, 0)->format('H:i'),
            'punch_out_time' => now()->setTime(19, 0, 0)->format('H:i'),
            'rest_records' => [],
            'reason' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['reason' => '備考を記入してください'
        ]);
    }

    /**
     * 修正申請処理が実行される
     */
    public function test_attendance_corrective_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->post(route('attendance-records.store'),[
            'user_id' => $this->user->id,
            'record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->setTime(10, 0, 0)->format('H:i'),
            'punch_out_time' => now()->setTime(19, 0, 0)->format('H:i'),
            'rest_records' => [
                [
                    'rest_in_time' => now()->setTime(12, 0, 0)->format('H:i'),
                    'rest_out_time' => now()->setTime(13, 0, 0)->format('H:i'),
                ],
            ],
            'reason' => 'テスト理由',
        ]);

        $response->assertStatus(302);
        
        $this->actingAs($admin)->get(route('attendance-requests.index'))
            ->assertStatus(200)
            ->assertSee($this->user->name)
            ->assertSee('テスト理由');

        $this->actingAs($admin)->get(route('admin.attendance.approval',[
            'attendance_correct_request_id' => 1
        ]))
            ->assertStatus(200)
            ->assertSee($this->user->name)
            ->assertSee('テスト理由');
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     */
    public function test_attendance_detail_shows_all_pending_requests(): void
    {
        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $response = $this->actingAs($this->user)->post(route('attendance-records.store'),[
            'user_id' => $this->user->id,
            'record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->setTime(10, 0, 0)->format('H:i'),
            'punch_out_time' => now()->setTime(19, 0, 0)->format('H:i'),
            'rest_records' => [
                [
                    'rest_in_time' => now()->setTime(12, 0, 0)->format('H:i'),
                    'rest_out_time' => now()->setTime(13, 0, 0)->format('H:i'),
                ],
            ],
            'reason' => 'テスト理由',
        ]);

        $response->assertStatus(302);
        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('テスト理由');
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     */
    public function test_attendance_detail_shows_all_approved_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $this->actingAs($this->user)->post(route('attendance-records.store'),[
            'user_id' => $this->user->id,
            'record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->setTime(10, 0, 0)->format('H:i'),
            'punch_out_time' => now()->setTime(19, 0, 0)->format('H:i'),
            'rest_records' => [
                [
                    'rest_in_time' => now()->setTime(12, 0, 0)->format('H:i'),
                    'rest_out_time' => now()->setTime(13, 0, 0)->format('H:i'),
                ],
            ],
            'reason' => 'テスト理由',
        ]);
        $latestRequest = \App\Models\AttendanceRequest::latest()->first();

        $this->actingAs($admin)->post(route('admin.attendance.approval.update',[
            'attendance_correct_request_id' => $latestRequest->id
        ]), [
            'status' => 'approved', // または 'approved' （仕様に合わせてください）
        ])->assertStatus(200);

        // 💡 修正ポイント3: 詳細画面を取得する（クエリパラメータ 'status' => '承認済み' はルート定義になければ不要です）
        $response = $this->actingAs($this->user)->get(route('attendance-records.detail', ['id' => $attendanceRecord->id]));
        $response->assertStatus(200);
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('テスト理由');
    }

    /**
     * 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     */
    public function test_attendance_detail_link_from_request(): void
    {     $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'punch_in_time' => now()->setTime(9, 0, 0),
            'punch_out_time' => now()->setTime(18, 0, 0),
        ]);

        $this->actingAs($this->user)->post(route('attendance-records.store'),[
            'user_id' => $this->user->id,
            'record_id' => $attendanceRecord->id,
            'punch_in_time' => now()->setTime(10, 0, 0)->format('H:i'),
            'punch_out_time' => now()->setTime(19, 0, 0)->format('H:i'),
            'rest_records' => [
                [
                    'rest_in_time' => now()->setTime(12, 0, 0)->format('H:i'),
                    'rest_out_time' => now()->setTime(13, 0, 0)->format('H:i'),
                ],
            ],
            'reason' => 'テスト理由',
        ]);

        $indexResponse = $this->actingAs($this->user)->get(route('attendance-requests.index'));
        $indexResponse->assertStatus(200);

        // 一覧画面の「詳細」リンクURLが、正しい勤怠レコードID（$attendanceRecord->id）で作られているか確認する
        $expectedDetailUrl = route('attendance-records.detail', ['id' => $attendanceRecord->id]);
        $indexResponse->assertSee($expectedDetailUrl); // 👈 リンクのIDがズレていたらここでテストが落ちて気付けます！

        $detailResponse = $this->actingAs($this->user)->get($expectedDetailUrl);
        $detailResponse->assertStatus(200);
        
        $detailResponse->assertSee('10:00');
        $detailResponse->assertSee('19:00');
        $detailResponse->assertSee('12:00');
        $detailResponse->assertSee('13:00');
        $detailResponse->assertSee('テスト理由');
    }
}
