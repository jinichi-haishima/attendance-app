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
        $users = User::all();
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

        $attendanceRecords = AttendanceRecord::where('user_id', $user->id)
            ->whereBetween('punch_in_time',[$startOfMonth, $endOfMonth])
            ->with('rest_records')
            ->get()
            ->keyBy(function($record) {
                return Carbon::parse($record->punch_in_time)->format('Y-m-d');
            });

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
}
