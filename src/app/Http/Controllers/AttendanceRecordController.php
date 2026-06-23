<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\AttendanceRequest;
use App\Models\RestRecord;
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

    public function detail(Request $request)
    {
        $user = Auth::user();
        $attendanceRecordId = $request->query('id');

        if (!$attendanceRecordId) {
            return redirect()->route('attendance-records.index')->with('error', '勤怠データが指定されていません。');
        }

        $record = AttendanceRecord::find($attendanceRecordId);

        if (!$record || $record->user_id !== $user->id) {
            return redirect()->route('attendance-records.index')->with('error', '勤怠記録が見つかりませんでした。');
        }

        // この勤怠レコードに紐づく「承認待ち」の申請がないか探す
        $latestRequest = AttendanceRequest::where('attendance_record_id', $record->id)
            ->whereIn('status', ['pending', 'approved']) // 承認待ちおよび承認済みの申請を対象
            ->latest()
            ->first();

        // 申請（承認待ち）がある場合は、その内容を優先表示する。ない場合は、元の勤怠データを表示する。
        if ($latestRequest) {
            $record->rest_records = $latestRequest->rest_requests; 

            $record->punch_in_time = $latestRequest->punch_in_time ? $latestRequest->punch_in_time : null; 
            $record->punch_out_time = $latestRequest->punch_out_time ? $latestRequest->punch_out_time: null;
        } else {
            $record->punch_in_time = $record->punch_in_time ? $record->punch_in_time : null;
            $record->punch_out_time = $record->punch_out_time ? $record->punch_out_time : null;
            $record->rest_records = $record->rest_records ?? collect();
        }

        // 表示用の日付
        $displayDate = $record->punch_in_time 
            ? $record->punch_in_time->format('Y年m月d日')
            : now()->format('Y年m月d日');

        return view('attendance.detail', [
            'record' => $record,
            'displayDate' => $displayDate,
            'latestRequest' => $latestRequest,
        ]);
    }

    public function store(CorrectionRequest $request)
    {
        //* 勤怠修正申請の保存処理
        // 画面から送られてくるデータを受け取る (例: 出勤時間、退勤時間、休憩時間の開始と終了、修正理由など)
        $user = Auth::user();
        $recordId = $request->input('record_id');
        $punchInTime = $request->input('punch_in_time');
        $punchOutTime = $request->input('punch_out_time');
        $restRecords = $request->input('rest_records', []);
        $reason = $request->input('reason'); // 画面の備考欄を「申請理由」として扱う

        // 1. 元になる本番の勤怠記録が存在するか一応チェック
        $record = AttendanceRecord::where('id', $recordId)->where('user_id', $user->id)->first();
        if (!$record) {
            return redirect()->route('attendance-records.index')->with('error', '勤怠記録が見つかりませんでした。');
        }
        //対象の日にちを特定するための変数。打刻時間があればその日付、なければ今日の日付を使う
        $targetDate = $record->punch_in_time ? $record->punch_in_time->format('Y-m-d') : now()->format('Y-m-d');

        // ★安全のためにトランザクションを開始（親か子のどちらかでエラーが起きたら全部白紙に戻す）
        DB::transaction(function () use ($user, $record, $punchInTime, $punchOutTime, $reason, $restRecords, $targetDate) {
        
        // 2. 親テーブル（attendance_requests）に申請データを「新規作成」する
        $attendanceRequest = AttendanceRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $record->id, 
            'punch_in_time' => $punchInTime ? Carbon::parse($targetDate . ' ' . $punchInTime) : null,
            'punch_out_time' => $punchOutTime ? Carbon::parse($targetDate . ' ' . $punchOutTime) : null,
            'status' => 'pending', // 最初は必ず「承認待ち」
            'reason' => $reason,   
        ]);

        // 3. 子テーブル（rest_requests）に休憩の申請データを保存していく
        foreach ($restRecords as $key => $restData) {
            // 開始時間と終了時間の両方が入力されている場合のみ申請を受け付ける
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