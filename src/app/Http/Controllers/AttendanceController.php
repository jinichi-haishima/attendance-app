<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 勤怠画面の表示
     * @return \Illuminate\View\View 勤怠画面のビュー
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 現在の状態表示用
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('punch_in_time', $today)
            ->first();

        if (!$attendance) {
            $status = '勤務外';
        } elseif ($attendance->punch_out_time) {
            $status = '退勤済み';
        } else {
            // 休憩中か出勤中かを判定
            $latestRest = $attendance->rest_records()->latest()->first();

            if ($latestRest && is_null($latestRest->rest_out_time)) {
                $status = '休憩中';
            } else {
                $status = '出勤中';
            }
        }

        return view('attendance', compact('status'));
    }

    /**
     * 出勤打刻処理
     * @return \Illuminate\Http\RedirectResponse リダイレクトレスポンス
     */
    public function punchIn()
    {
        $user = Auth::user();
        $today = Carbon::today();

        // 既に出勤しているか確認
        $existingAttendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('punch_in_time', $today)
            ->first();
        //
        if ($existingAttendance) {
            return redirect()->back()->with('error', '既に出勤しています。');
        }

        AttendanceRecord::create([
            'user_id' => $user->id,
            'punch_in_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '出勤しました。');
    }

    /**
     * 退勤打刻処理
     * @return \Illuminate\Http\RedirectResponse リダイレクトレスポンス
     */
    public function punchOut()
    {
        $user = Auth::user();
        $today = Carbon::today();
        // 出勤しているか確認
        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('punch_in_time', $today)
            ->first();
        // 退勤していないか確認
        if (!$attendance || $attendance->punch_out_time) {
            return redirect()->back()->with('error', '出勤していないか、既に退勤しています。');
        }

        $attendance->update([
            'punch_out_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '退勤しました。');
    }

    /**
     * 休憩開始打刻処理
     * @return \Illuminate\Http\RedirectResponse リダイレクトレスポンス
     */
    public function restIn()
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('punch_in_time', $today)
            ->first();

        if (!$attendance || $attendance->punch_out_time) {
            return redirect()->back()->with('error', '出勤していないか、既に退勤しています。');
        }

        // 既に休憩中か確認
        $latestRest = $attendance->rest_records()->latest()->first();
        if ($latestRest && is_null($latestRest->rest_out_time)) {
            return redirect()->back()->with('error', '既に休憩中です。');
        }

        RestRecord::create([
            'attendance_record_id' => $attendance->id,
            'rest_in_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '休憩開始しました。');
    }

    /**
     * 休憩終了打刻処理
     * @return \Illuminate\Http\RedirectResponse リダイレクトレスポンス
     */
    public function restOut()
    {
        $user = Auth::user();
        $today = Carbon::today();

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('punch_in_time', $today)
            ->first();

        if (!$attendance || $attendance->punch_out_time) {
            return redirect()->back()->with('error', '出勤していないか、既に退勤しています。');
        }

        // 休憩中か確認
        $latestRest = $attendance->rest_records()->latest()->first();
        if (!$latestRest || $latestRest->rest_out_time) {
            return redirect()->back()->with('error', '休憩中ではありません。');
        }

        $latestRest->update([
            'rest_out_time' => Carbon::now(),
        ]);

        return redirect()->back()->with('success', '休憩終了しました。');
    }
}
