<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\HTTP\Controllers\Controller;
use App\Models\User;
use App\Models\AttendanceRecord;
use Carbon\Carbon;

class StaffController extends Controller
{
    /** スタッフ一覧の表示
     * URL: /admin/staff
     * @return \Illuminate\View\View スタッフ一覧画面のビュー
     */
    public function index()
    {
        $users = User::onlyGeneral()->get();
        return view('admin.staff', compact('users'));
    }

    public function show(Request $request, $id)
    {
        /* スタッフの勤怠記録を表示するメソッド
         * URL例: /admin/staff/1?date=2026-06
         * クエリパラメータの「date」は「YYYY-MM」の形式で、表示したい年月を指定します。
         * 例: ?date=2026-06 → 2026年6月の勤怠記録を表示
         * クエリパラメータがない場合は、現在の年月を表示します。
         * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
         * @param int $id スタッフのユーザーID
         * @return \Illuminate\View\View スタッフの勤怠記録画面のビュー
         */
        $user = User::findOrFail($id);

        $dateInput = $request->query('date', now()->format('Y-m'));

        try {
            $currentMonth = Carbon::parse($dateInput . '-01');
        } catch (\Exception $e) {
            $currentMonth = Carbon::now()->startOfMonth();
        }
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        // 指定された年月の勤怠記録を取得
        $attendanceRecords = AttendanceRecord::where('user_id', $user->id)
            ->whereBetween('punch_in_time',[$startOfMonth, $endOfMonth])
            ->with('rest_records')
            ->get()
            ->keyBy(function($record) {
                return Carbon::parse($record->punch_in_time)->format('Y-m-d');
            });
            // カレンダー表示用のデータを作成
            $calendarDates = [];
            $daysInMonth = $currentMonth->daysInMonth;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = $currentMonth->copy()->day($day);
                $dateString = $date->format('Y-m-d');
                $record = $attendanceRecords->get($dateString);

                $calendarDates[] = [
                    'date' => $date,
                    'attendance' => $record
                ];

            }

        return view('admin.staff_attendance', [
            'user' => $user,
            'calendarDates' => $calendarDates,
            'displayMonth' => $currentMonth->format('Y年m月'),
            'prevMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function downloadCsv(Request $request, $id)
    {
        /**
     * 特定のスタッフの選択された月の勤怠データをCSV出力する
     * URL: /admin/staff/{id}/csv
     * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
     * @param int $id スタッフのユーザーID
     * @return \Symfony\Component\HttpFoundation\StreamedResponse CSVファイルのストリームレスポンス
     */
        $user = User::findOrFail($id);

        // 「選択された月」を取得
        $dateInput = $request->query('date', now()->format('Y-m'));

        try {
            $currentMonth = Carbon::parse($dateInput . '-01');
        } catch (\Exception $e) {
            $currentMonth = Carbon::now()->startOfMonth();
        }
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // 💡 対象スタッフかつ指定年月の勤怠記録を取得（日付順）
        $attendanceRecords = AttendanceRecord::where('user_id', $user->id)
            ->whereBetween('punch_in_time', [$startOfMonth, $endOfMonth])
            ->orderBy('punch_in_time', 'asc')
            ->get();

        // CSVストリームの生成
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($attendanceRecords, $user) {
            $stream = fopen('php://output', 'w');

            // Excel文字化け防止用BOM
            fwrite($stream, pack('C*', 0xEF, 0xBB, 0xBF));

            // CSVのヘッダー
            fputcsv($stream, ['ユーザー名', $user->name]);
            fputcsv($stream, []); // 1行空ける

            fputcsv($stream, ['日付', '出勤時間', '退勤時間', '休憩時間', '合計時間']);

            // データの書き込み
            foreach ($attendanceRecords as $record) {
                $date = $record->punch_in_time ? Carbon::parse($record->punch_in_time)->format('Y-m-d') : '';
                $punchIn = $record->punch_in_time ? Carbon::parse($record->punch_in_time)->format('H:i') : '';
                $punchOut = $record->punch_out_time ? Carbon::parse($record->punch_out_time)->format('H:i') : '';
                $restTime = $record->formatted_rest_time ?? '';
                $workTime = $record->formatted_work_time ?? '';

                // 💡 配列に値を追加
                fputcsv($stream, [
                    $date,
                    $punchIn,
                    $punchOut,
                    $restTime,
                    $workTime,
                ]);
            }
            fclose($stream);
        });

        // ファイル名を「スタッフ名_年月.csv」にする（例: 山田太郎_2026-06.csv）
        $fileName = $user->name . '_' . $currentMonth->format('Y-m') . '.csv';

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
