<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;

class AttendanceRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        /**
         * 勤怠レコードのAPIエンドポイント
         * URL: /api/v1/attendance-records
         * クエリパラメータで「date=YYYY-MM」を受け取り、その年月の勤怠レコードを返す
         * クエリパラメータがない場合は、現在の年月の勤怠レコードを返す
         */

        $query = AttendanceRecord::query();

        if($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if($request->has('date')) {
            $query->where('date', $request->input('date'));
        }

        if($request->has('month')) {
            $month = $request->input('month');
            $query->where('date', 'like', $month . '%');
        }

        $perPage = $request->input('per_page', 20);
        if ($perPage > 100) {
            $perPage = 100;
        }elseif ($perPage < 1) {
            $perPage = 20;
        }  

        $records = $query->paginate($perPage);

        return AttendanceRecordResource::collection($records);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAttendanceRecordRequest $request)
    {
        $validated = $request->validated();
        $date = $validated['date'];

        $punchInTime = "{$date} {$validated['clock_in']}";
        $punchOutTime = !empty($validated['clock_out'])?"{$date} {$validated['clock_out']}" : null;

        $record = AttendanceRecord::create([
            'user_id' => $validated['user_id'],
            'punch_in_time' => $punchInTime,
            'punch_out_time' => $punchOutTime,
            'reason' => $validated['comment'] ?? null,
        ]);

        return (new AttendanceRecordResource($record))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        /**
         * 勤怠レコードのAPIエンドポイント
         * URL例: /api/v1/attendance-records/1
         * @param int $id 勤怠レコードのID
         * @return \Illuminate\Http\Response 指定された勤怠レコードの詳細を返す
         */

        $record = AttendanceRecord::with('user', 'rest_records')->findOrFail($id);

        if(!$record) {
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。'
            ], 404);
        }
        return new AttendanceRecordResource($record);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAttendanceRecordRequest $request,AttendanceRecord $attendanceRecord)
    {

        // 🔒 認可チェックを実行（現在のログインユーザーが、このデータを編集できるか）
        Gate::authorize('update', $attendanceRecord);
        /**
         * 勤怠レコードのAPIエンドポイント
         * URL例: /api/v1/attendance-records/1
         * @param int $id 勤怠レコードのID
         * クエリパラメータで「date=YYYY-MM-DD」を受け取り、その日付の勤怠レコードを更新する
         * クエリパラメータがない場合は、勤怠レコードのdateを更新しない
         */
        $record = $attendanceRecord;

        $validated = $request->validated();
        $date = $validated['date'];

        $punchInTime = "{$date} {$validated['clock_in']}";
        $punchOutTime = !empty($validated['clock_out'])?"{$date} {$validated['clock_out']}" : null;

        $record->update([
            'user_id' => $validated['user_id'],
            'punch_in_time' => $punchInTime,
            'punch_out_time' => $punchOutTime,
            'reason' => $validated['comment'] ?? null,
        ]);

        return new AttendanceRecordResource($record);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(AttendanceRecord $attendanceRecord)
    {
        // 🔒 認可チェックを実行（現在のログインユーザーが、このデータを削除できるか）
        Gate::authorize('delete', $attendanceRecord);
        /**
         * 勤怠レコードのAPIエンドポイント
         * URL例: /api/v1/attendance-records/1
         * @param int $id 勤怠レコードのID
         * 指定された勤怠レコードを削除する
         */
        $record = $attendanceRecord;

        $record->delete();

        return response()->noContent();
    }
}
