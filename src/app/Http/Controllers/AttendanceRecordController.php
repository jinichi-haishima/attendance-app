<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRequest;
use App\Models\RestRequest;
use Carbon\Carbon;

class AttendanceRecordController extends Controller
{
    public function index(Request $request)
    /**
     * 勤怠記録の一覧表示
     * URL例: /attendance-records?date=2026-06
     * クエリパラメータの「date」は「YYYY-MM」の形式で、表示したい年月を指定します。
     * 例: ?date=2026-06 → 2026年6月の勤怠記録を表示
     * 例: ?date=2026-07 → 2026年7月の勤怠記録を表示
     * クエリパラメータがない場合は、現在の年月を表示します。
     *
     * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
     * @return \Illuminate\View\View 勤怠一覧画面のビュー
     */
    {
    $user = Auth::user();
    $dateInput = $request->query('date', Carbon::now()->format('Y-m'));

    try {
        $currentMonth = Carbon::parse($dateInput . '-01');
    } catch (\Exception $e) {
        $currentMonth = Carbon::now()->startOfMonth();
    }

    $startOfMonth = $currentMonth->copy()->startOfMonth();
    $endOfMonth = $currentMonth->copy()->endOfMonth();

    // 1. データベースから今月の打刻データを取得（ここはそのまま）
    $attendanceRecords = AttendanceRecord::where('user_id', $user->id)
        ->whereBetween('punch_in_time', [$startOfMonth, $endOfMonth])
        ->with('rest_records')
        ->get()

        ->keyBy(function ($record) {
            return Carbon::parse($record->punch_in_time)->format('Y-m-d');
        });

    // 2. 💡 その月の「1日から月末まで」の日付リストをループで作成
    $calendarDates = [];
    $daysInMonth = $currentMonth->daysInMonth; // その月が何日あるか（28〜31）

    for ($day = 1; $day <= $daysInMonth; $day++) {
        // 1日ずつのCarbonオブジェクトを作る（例: 2026-06-01, 2026-06-02...）
        $date = $currentMonth->copy()->day($day);

        // その日の打刻データがあるか、先ほどの連想配列から探す
        $dateString = $date->format('Y-m-d');
        $record = $attendanceRecords->get($dateString);

        // 日付情報と打刻データをセットにして配列に入れる
        $calendarDates[] = [
            'date' => $date,
            'record' => $record
        ];
    }

    return view('attendance.index', [
        'calendarDates' => $calendarDates,
        'displayMonth'  => $currentMonth->format('Y年m月'),
        'prevMonth'     => $currentMonth->copy()->subMonth()->format('Y-m'),
        'nextMonth'     => $currentMonth->copy()->addMonth()->format('Y-m'),
    ]);
    }

    public function detail($id, Request $request)
    {
        /**
         * 勤怠記録の詳細表示
         * URL例: /attendance-records/1?date=2026-06-24
         * クエリパラメータの「date」は「YYYY-MM-DD」の形式で、表示したい日付を指定します。
         * 例: ?date=2026-06-24 → 2026年6月24日の勤怠記録
         * クエリパラメータがない場合は、現在の日付を表示します。
         * @param int $id 勤怠記録のID（ユーザーIDとして扱う）
         * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
         * @return \Illuminate\View\View 勤怠詳細画面のビュー
         */
        $user = Auth::user();

        if ((int)$id !== $user->id) {
            return redirect()->route('attendance-records.index')->with('error', '不正なアクセスです。');
        }

        $dateInput = $request->query('date', Carbon::now()->format('Y-m-d'));
        try {
            $currentDate = Carbon::parse($dateInput);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
        }

        // 1. まずは指定された日付のレコードがあるか探す
        $record = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('punch_in_time', $currentDate->toDateString())
            ->with('rest_records')
            ->first();

        // 💡 【ここを修正】データがなかったら、その場でデータベースに枠だけ作成
        if (!$record) {
            $record = AttendanceRecord::create([
                'user_id' => $user->id,
                'punch_in_time' => $currentDate->copy()->startOfDay(),
                'punch_out_time' => null,
            ]);
            // 休憩はまだ無いので空のコレクションを入れておく
            $record->setRelation('rest_records', collect());
        }

        $latestRequest = null;
        if ($record->id) {
            $latestRequest = AttendanceRequest::where('attendance_record_id', $record->id)
                ->whereIn('status', ['pending', 'approved'])
                ->latest()
                ->first();
        }

        if ($latestRequest) {
            $record->rest_records = $latestRequest->rest_requests;
            $record->punch_in_time = $latestRequest->punch_in_time;
            $record->punch_out_time = $latestRequest->punch_out_time;
        }

        $displayDate = $currentDate->format('Y年m月d日');

        return view('attendance.detail', [
            'record' => $record,
            'displayDate' => $displayDate,
            'latestRequest' => $latestRequest,
        ]);
    }

    public function store(CorrectionRequest $request)
    {
        /**
         * 勤怠修正申請の保存処理
         * @param CorrectionRequest $request バリデーション済みのリクエスト
         * @return \Illuminate\Http\RedirectResponse リダイレクトレスポンス
         */
        $user = Auth::user();
        $recordId = $request->input('record_id');
        $punchInTime = $request->input('punch_in_time');
        $punchOutTime = $request->input('punch_out_time');
        $restRecords = $request->input('rest_records', []);
        $reason = $request->input('reason');

        // 1. 元になる本番の勤怠記録が存在するかチェック
        $record = AttendanceRecord::where('id', $recordId)->where('user_id', $user->id)->first();
        if (!$record) {
            return redirect()->route('attendance-records.index')->with('error', '勤怠記録が見つかりませんでした。');
        }

        // 💡 【ここを修正】画面のhiddenフィールドから送られてきた日付（Y-m-d）を最優先で使います
        // もし送られてきていなければ、本番レコードの時間、それもなければ今日にします。
        if ($request->has('date')) {
            $targetDate = $request->input('date');
        } else {
            $targetDate = $record->punch_in_time ? $record->punch_in_time->format('Y-m-d') : now()->format('Y-m-d');
        }

        // ★安全のためにトランザクションを開始
        DB::transaction(function () use ($user, $record, $punchInTime, $punchOutTime, $reason, $restRecords, $targetDate) {

            // 2. 親テーブル（attendance_requests）に申請データを「新規作成」
            $attendanceRequest = AttendanceRequest::create([
                'user_id' => $user->id,
                'attendance_record_id' => $record->id,
                // 💡 選択した正しい日付（$targetDate）と入力された時刻をガッチャンコします
                'punch_in_time' => $punchInTime ? Carbon::parse($targetDate . ' ' . $punchInTime) : null,
                'punch_out_time' => $punchOutTime ? Carbon::parse($targetDate . ' ' . $punchOutTime) : null,
                'status' => 'pending',
                'reason' => $reason,
            ]);

            // 3. 子テーブル（rest_requests）に休憩の申請データを保存
            foreach ($restRecords as $key => $restData) {
                if (!empty($restData['rest_in_time']) && !empty($restData['rest_out_time'])) {
                    RestRequest::create([
                        'attendance_request_id' => $attendanceRequest->id,
                        'rest_in_time' => Carbon::parse($targetDate . ' ' . $restData['rest_in_time']),
                        'rest_out_time' => Carbon::parse($targetDate . ' ' . $restData['rest_out_time']),
                    ]);
                }
            }
        });

        return redirect()->route('attendance-records.index')->with('success', '修正申請を送信しました。管理者の承認をお待ちください。');
    }
}