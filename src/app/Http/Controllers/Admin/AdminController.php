<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\CorrectionRequest;
use Illuminate\Support\Facades\DB;
use App\HTTP\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;
use App\Models\AttendanceRequest;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function login()
    {
        return view('admin.login');
    }

    public function index(Request $request)
    {
        $dataInput = $request->query('date', Carbon::now()->format('Y-m-d'));

        try {
            $currentDate = Carbon::parse($dataInput);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
        }

        $attendanceRecords = AttendanceRecord::whereDate('punch_in_time', $currentDate->toDateString())
            ->with('user', 'rest_records')
            ->get();
        
        return view('admin.index', [
            'attendanceRecords' => $attendanceRecords,
            'displayDate' => $currentDate->format('Y年m月d日'),
            'previousDate' => $currentDate->copy()->subDay()->format('Y-m-d'),
            'nextDate' => $currentDate->copy()->addDay()->format('Y-m-d'),
            'dateInput' => $currentDate->format('Y-m-d'),
        ]);
    }

    public function detail(Request $request)
    {
        $dateInput = $request->query('date', Carbon::now()->format('Y-m-d'));
        $userId = $request->query('user_id');

        try {
            $currentDate = Carbon::parse($dateInput);
        } catch (\Exception $e) {
            $currentDate = Carbon::now();
        }
        //* 指定されたユーザーIDと日付に基づいて勤怠記録を取得する
        $attendanceRecord = AttendanceRecord::whereDate('punch_in_time', $currentDate->toDateString())
            ->where('user_id', $userId) 
            ->with('user', 'rest_records')
            ->first();
        
        $latestRequest = null;
        
        if (!$attendanceRecord) {
        return redirect()->route('admin.index')->withErrors('該当する勤怠データが見つかりませんでした。');
        }
        
        $latestRequest = AttendanceRequest::where('attendance_record_id', $attendanceRecord->id)
                        ->whereIn('status', ['pending', 'approved'])
                        ->latest()
                        ->first();

        if ($latestRequest && $latestRequest->status === 'pending') {
            // 承認待ちの申請がある場合は、その申請に紐づく休憩申請も取得しておく
            $latestRequest->load('rest_requests');
            $attendanceRecord->rest_records = $latestRequest->rest_requests; // 画面では、勤怠記録の休憩データの代わりに申請中の休憩データを表示するために上書きする
            $attendanceRecord->punch_in_time = $latestRequest->punch_in_time; // 画面では、勤怠記録の出勤時間の代わりに申請中の出勤時間を表示するために上書きする
            $attendanceRecord->punch_out_time = $latestRequest->punch_out_time; //
        }

        return view('admin.detail', [
            'attendanceRecord' => $attendanceRecord,
            'displayDate' => $currentDate->format('Y年m月d日'),
            'latestRequest' => $latestRequest,
        ]);
    }

    public function update(CorrectionRequest $request)
    {
        //* 勤怠修正申請の保存処理
        // 画面から送られてくるデータを受け取る (例: 出勤時間、退勤時間、休憩時間の開始と終了、修正理由など)
        $recordId = $request->input('record_id');
        $punchInTime = $request->input('punch_in_time');
        $punchOutTime = $request->input('punch_out_time');
        $restRecords = $request->input('rest_records', []);
        $reason = $request->input('reason'); // 画面の備考欄を「申請理由」として扱う

        //  元になる本番の勤怠記録が存在するか一応チェック
    $record = AttendanceRecord::find($recordId);
    if (!$record) {
        return redirect()->route('admin.index')->with('error', '勤怠記録が見つかりませんでした。');
    }

    $targetDate = $record->punch_in_time ? Carbon::parse($record->punch_in_time)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        // 安全のためにトランザクションを開始（親か子のどちらかでエラーが起きたら全部白紙に戻す）
        DB::transaction(function () use ($record, $punchInTime, $punchOutTime, $reason, $restRecords, $targetDate) {
        
        // 本番の勤怠レコード（AttendanceRecord）を直接更新
        $record->update([
            'punch_in_time' => $punchInTime ? Carbon::parse($targetDate . ' ' . $punchInTime) : null,
            'punch_out_time' => $punchOutTime ? Carbon::parse($targetDate . ' ' . $punchOutTime) : null,
            'reason' => $reason,   // 修正理由をここに1つだけ保存！
        ]);

        $record->rest_records()->delete();

        foreach ($restRecords as $restData) {
            // 開始時間と終了時間の両方が入力されている場合のみ申請を受け付ける
            if (!empty($restData['rest_in_time']) && !empty($restData['rest_out_time'])) {
                
                $record->rest_records()->create([
                    'rest_in_time' => Carbon::parse($targetDate . ' ' . $restData['rest_in_time']),
                    'rest_out_time' => Carbon::parse($targetDate . ' ' . $restData['rest_out_time']),
                ]);
            }
        }
    });

    return redirect()->route('admin.index')->with('success', '勤怠データが修正されました。');
    }
}