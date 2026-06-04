<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;
use Carbon\Carbon;

class AttendanceRecordController extends Controller
{   /** 勤怠記録の一覧表示
    * URL例: /attendance-records?date=2026-06
    * クエリパラメータの「date」は「YYYY-MM」の形式で、表示したい年月を指定します。
    * 例: ?date=2026-06 → 2026年6月の勤怠記録を表示
    * 例: ?date=2026-07 → 2026年7月の勤怠記録を表示
    * クエリパラメータがない場合は、現在の年月を表示します。
    *
    * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
    * @return \Illuminate\View\View 勤怠一覧画面のビュー
    */
    public function index(Request $request)
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
        // 💡 検索しやすいように「日付（Y-m-d）」をキーにした連想配列に変換します
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
        $record = $attendanceRecords->get($dateString); // なければ null が入る

        // 日付情報と打刻データをセットにして配列に入れる
        $calendarDates[] = [
            'date' => $date,       // Carbonオブジェクト（曜日や日付の表示用）
            'record' => $record    // 打刻データ（なければnull）
        ];
    }

    return view('attendance.index', [
        'calendarDates' => $calendarDates, // 💡 これをビューに渡す
        'displayMonth'  => $currentMonth->format('Y年m月'),
        'prevMonth'     => $currentMonth->copy()->subMonth()->format('Y-m'),
        'nextMonth'     => $currentMonth->copy()->addMonth()->format('Y-m'),
    ]);
    }

    public function detail(Request $request)
    {
        /** 勤怠記録の詳細表示
         * URL例: /attendance/detail?date=2026-06-15
         * クエリパラメータの「date」は「YYYY-MM-DD」の形式で、表示したい日付を指定します。
         * 例: ?date=2026-06-15 → 2026年6月15日の勤怠記録を表示
         *
         * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
         * @return \Illuminate\View\View 勤怠詳細画面のビュー
         */
        $user = Auth::user();
        $dateInput = $request->query('date');

        if (!$dateInput) {
            return redirect()->route('attendance-records.index')->with('error', '日付が指定されていません。');
        }

        try {
            $date = Carbon::parse($dateInput);
        } catch (\Exception $e) {
            return redirect()->route('attendance-records.index')->with('error', '無効な日付形式です。');
        }

        $record = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('punch_in_time', $date->format('Y-m-d'))
            ->with('rest_records','user')
            ->first();

        if (!$record) {
            return redirect()->route('attendance-records.index')->with('error', '指定された日の勤怠記録が見つかりませんでした。');
        }

        return view('attendance.detail', [
            'record' => $record,
            'displayDate' => $date->format('Y年m月d日'),
        ]);
    }
}