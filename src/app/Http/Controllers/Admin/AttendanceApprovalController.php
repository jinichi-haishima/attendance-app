<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRecord;
use App\Models\RestRecord;



class AttendanceApprovalController extends Controller
{
    public function show($id, Request $request)
    {
        /**
         * 勤怠申請の詳細表示
         * 💡 URLの {id}（勤怠申請のプライマ
         * リキー）から直接データを取得
         * @param int $id 勤怠申請のプライマリキー
         * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
         * @return \Illuminate\View\View 勤怠申請詳細画面のビュー
         */
        $attendanceRequest = AttendanceRequest::with('rest_requests')->findOrFail($id);
        $date = $request->query('date');
        return view('admin.approval', compact('attendanceRequest', 'date'));
    }

    public function approve($id, Request $request)
    {
        /**
         * 勤怠申請の承認処理
         * 💡 URLの {id}（勤怠申請のプライマリキー）から直接データを取得
         * @param int $id 勤怠申請のプライマリキー
         * @param Request $request リクエストオブジェクト（クエリパラメータ取得用）
         * @return \Illuminate\Http\JsonResponse 承認結果のJSONレスポンス
         */
        // トランザクション開始
        DB::beginTransaction();
        try {

            $attendanceRequest = AttendanceRequest::with('rest_requests')->findOrFail($id);
            $attendanceRecord = AttendanceRecord::findOrFail($attendanceRequest->attendance_record_id);

            // 勤怠申請の承認処理
            $attendanceRecord->update([
                'punch_in_time' => $attendanceRequest->punch_in_time,
                'punch_out_time' => $attendanceRequest->punch_out_time,
            ]);

            // 休憩申請の承認処理
            RestRecord::where('attendance_record_id',$attendanceRecord->id)->delete(); // 既存の休憩時間を削除

            foreach ($attendanceRequest->rest_requests as $requestRest){

                if(empty($requestRest->rest_in_time)){
                    continue; // 休憩開始時間が空の場合はスキップ
                }
                // 休憩時間を新規作成
                RestRecord::create([
                    'attendance_record_id' => $attendanceRecord->id,
                    'rest_in_time' => $requestRest->rest_in_time,
                    'rest_out_time' =>$requestRest->rest_out_time,
                ]);
            }
        // 申請のステータスを「承認」に更新
        $attendanceRequest->update(['status' => 'approved', 'approved_by' => auth()->id()]);

        DB::commit();
        return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
