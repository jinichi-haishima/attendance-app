<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AttendanceReportController extends Controller
{   
    public function index()
    {   
        /**
         * 勤怠レコードから、過去6ヶ月分の勤務時間と残業時間のサマリーと月ごとの集計データを作成してビューに渡す
         * URL: /attendance/report
         * @return \Illuminate\View\View 勤怠レポート画面のビュー 
         */

        // 1. 過去6ヶ月のデータを一括取得（休憩レコードも同時に取得してN+1を防止！）
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();

        $attendanceRecords = AttendanceRecord::where('user_id', auth()->id())
            ->where('punch_in_time', '>=', $sixMonthsAgo)
            ->with('rest_records') 
            ->get();

        // 2. Collection メソッドを使って foreach なしでデータ加工
        $processedRecords = $attendanceRecords->map(function ($record) {
            // 上で作ったアクセサから「実労働（分）」を取得
            $workingMinutes = $record->actual_work_minutes; 

            // 8時間（480分）を超えた分を残業時間とする
            $overtimeMinutes = max(0, $workingMinutes - 480);

            return [
                'working_minutes'  => $workingMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ];
        });

        // 3. 各サマリーの算出
        $totalWorkingMinutes  = $processedRecords->sum('working_minutes');
        $totalOvertimeMinutes = $processedRecords->sum('overtime_minutes');
        $averageWorkingMinutes = round($processedRecords->avg('working_minutes'));

        $summary = [
            'total_working_hours' => $this->formatMinutesToHours($totalWorkingMinutes),
            'total_overtime_hours' => $this->formatMinutesToHours($totalOvertimeMinutes),
            'average_working_hours' => $this->formatMinutesToHours($averageWorkingMinutes), 
        ];
        // 4. 月ごとの集計データを作成
        $monthsBase = collect(range(5,0,-1))->mapWithKeys(function($i) {
            $monthStr = now()->subMonths($i)->format('Y-m');
            return [$monthStr => [
                'month' => now()->subMonths($i)->format('Y-m'),
                'working_minutes' => 0,
                'overtime_minutes' => 0,
            ]];
        });
        // attendanceRecordsを月ごとにグループ化して、月ごとの集計を計算
        $monthlyData = $attendanceRecords->groupBy(function($record) {
            return Carbon::parse($record->punch_in_time)->format('Y-m');
        })->map(function($recordInMonth) {           
            $workingMinutes = $recordInMonth->sum('actual_work_minutes');
            $overtimeMinutes = $recordInMonth->sum(function($record) {
                return max(0, $record->actual_work_minutes- 480);
            });

            return [
                'working_minutes' => $workingMinutes,
                'overtime_minutes' => $overtimeMinutes,
            ];
        });

        // 月のベースデータと実際のデータをマージして、過去6ヶ月分の月ごとの集計データを完成させる
        $graphData = $monthsBase->map(function($baseData, $key) use ($monthlyData) {
            // 実際のデータ（$monthlyData）から、該当する月のデータを取得する
            $actualData = $monthlyData->get($key);

            // 実際のデータがあればその値を、なければベースの 0 を使う（これで配列になるのを防ぎます）
            $workingMinutes  = $actualData ? $actualData['working_minutes'] : 0;
            $overtimeMinutes = $actualData ? $actualData['overtime_minutes'] : 0;

            return [
                'month'          => $baseData['month'],
                'working_hours'  => $this->formatMinutesToHours($workingMinutes),
                'overtime_hours' => $this->formatMinutesToHours($overtimeMinutes),
            ];
        });
        // 5. 異常検知表示機能（当月内のカウント）
        $currentMonth = now()->format('Y-m');

        $currentMonthRecords = $attendanceRecords->filter(function($record) use ($currentMonth) {
            return Carbon::parse($record->punch_in_time)->format('Y-m') === $currentMonth;
        });
        // 遅刻のカウント（例: 9:00以降の出勤を遅刻とする）
        $lateCount = $currentMonthRecords->filter(function($record) {
            return Carbon::parse($record->punch_in_time)->format('H:i:s') > '09:00';
        })->count();

        // 早退のカウント（例: 17:00以前の退勤を早退とする）
        $earlyLeaveCount = $currentMonthRecords->filter(function($record) {
            return Carbon::parse($record->punch_out_time)->format('H:i:s') < '18:00';
        })->count();

        //長時間労働のカウント（例: 10時間以上の勤務を長時間労働とする）
        $longWorkCount = $currentMonthRecords->filter(function($record) {
            return $record->actual_work_minutes >= 600; // 10時間 = 600分
        })->count();

        // 異常検知のサマリーに追加
        $summary['late_count'] = $lateCount;
        $summary['early_leave_count'] = $earlyLeaveCount;
        $summary['long_work_count'] = $longWorkCount;

            return view('attendance.report', compact('summary', 'graphData'));

    }

    private function formatMinutesToHours($totalMinutes)
    {
        //h:mm の形式で時間を返すヘルパーメソッド
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        return "{$hours}h {$minutes}m";
    }


}
