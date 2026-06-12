<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRequest;

class AttendanceRequestController extends Controller
{
    //* 申請一覧の表示
    // 管理者は全ての申請を、一般ユーザーは自分の申請のみを表示するロジックを実装
    // タブ切り替えのクエリパラメータ（例: ?tab=approved）を受け取り、表示する申請のステータスを切り替えるロジックも実装
    public function index(Request $request)
    {
        $user = auth()->user();
        $currentTab = $request->query('tab', 'pending');

        $statusCondition = $currentTab === 'approved' ? 'approved' : 'pending';

        if($user->is_admin) {
            $attendanceRequests = AttendanceRequest::with('user')
                ->where('status', $statusCondition)
                ->latest()
                ->get();

            return view('attendance.request', compact('attendanceRequests', 'currentTab'));

        }else {
        // 一般ユーザーは自分の申請のみを表示
            $attendanceRequests = $user->attendanceRequests()
            ->where('status', $statusCondition)
            ->latest()
            ->get();

        return view('attendance.request', compact('attendanceRequests', 'currentTab'));
        }
    }
}
