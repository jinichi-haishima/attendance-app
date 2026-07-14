<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttendanceRecordResource;
use App\Http\Resources\Api\V1\AttendanceRecordDetailResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use Carbon\Carbon;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠レコードのAPIエンドポイント
     * URL例: /api/v1/attendance-records?user_id=1&date=2023-08-01&month=2023-08&per_page=20
     * @param \Illuminate\Http\Request $request リクエストオブジェクト
     * @return \Illuminate\Http\Response 指定された条件に基づく勤怠レコードの一覧を返す
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        $query = AttendanceRecord::with(['user', 'rest_records'])
            ->latest('punch_in_time');

        $query->when($request->input('user_id'), function ($q, $userId) {
            return $q->where('user_id', $userId);
        });

        $query->when($request->input('date'), function ($q, $date) {
            return $q->whereDate('punch_in_time', $date);
        });

        $query->when($request->input('month'), function ($q, $monthString) {
            $parts = explode('-', $monthString);
            if (count($parts) === 2) {
                return $q->whereYear('punch_in_time', $parts[0])
                        ->whereMonth('punch_in_time', $parts[1]);
            }
        });

        //パラメータが何もない場合は、今月分にするフォールバック（必要に応じて）
        if (!$request->hasAny(['user_id', 'date', 'month'])) {
            $query->whereYear('punch_in_time', now()->year)
                ->whereMonth('punch_in_time', now()->month);
        }

        // per_page の最大値はバリデーション側で保証されている。
        $perPage = $request->input('per_page', 20);
        $records = $query->paginate($perPage);

        return AttendanceRecordResource::collection($records);
    }

    /**
     * 勤怠レコードのAPIエンドポイント
     * URL例: /api/v1/attendance-records
     * @param \Illuminate\Http\Request $request リクエストオブジェクト
     * @return \Illuminate\Http\Response 新しく作成された勤怠レコードの詳細を返す
     */
    public function store(StoreAttendanceRecordRequest $request)
    {
        $validated = $request->validated();
        $date = $validated['date'];

        $userId = $request->user()->id;
        $exists = AttendanceRecord::where('user_id', $userId)
            ->whereDate('punch_in_time', $date)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'この日付の勤怠は既に登録されています。'], 422);
        }

        // 💡 送られてきた日付と時刻を結合
        $punchInTime = "{$date} {$validated['clock_in']}";
        $punchOutTime = !empty($validated['clock_out']) ? "{$date} {$validated['clock_out']}" : null;

        $attendanceRecord = $request->user()->attendanceRecords()->create([
            'punch_in_time'  => $punchInTime,
            'punch_out_time' => $punchOutTime,
            'reason'         => $validated['comment'] ?? null,
        ]);

        // 💡 関連データを eager load
        $attendanceRecord->load(['user', 'rest_records']);

        // 💡 201 Created で返却
        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠詳細取得 (GET)
     * * GET /api/v1/attendance-records/{attendanceRecord}
     */
    public function show(AttendanceRecord $attendanceRecord)
    {
        // 💡 Eager Load で関連データを取得
        $attendanceRecord->load(['user', 'rest_records', 'applications']);

        // 共用化されたリソースを返却
        return new AttendanceRecordResource($attendanceRecord);
    }

    public function update(UpdateAttendanceRecordRequest $request,AttendanceRecord $attendanceRecord)
    {
        // 🔒 認可チェックを実行（現在のログインユーザーが、このデータを編集できるか）
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validated();
        $date = $validated['date'];

        // 💡 登録時と同様に、日時型に合わせて結合
        $punchInTime = "{$date} {$validated['clock_in']}";
        $punchOutTime = !empty($validated['clock_out']) ? "{$date} {$validated['clock_out']}" : null;

        // 💡 補足の指示通り、レコードを更新
        $attendanceRecord->update([
            'punch_in_time'  => $punchInTime,
            'punch_out_time' => $punchOutTime,
            'reason'         => $validated['comment'] ?? null,
        ]);

        // 💡 補足の指示通り、更新後に eager load
        $attendanceRecord->load(['user', 'rest_records']);

        // 💡 200 OK で AttendanceRecordResource を返す（通常のリターンでOK）
        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 指定された勤怠レコードを削除する
     * * DELETE /api/v1/attendance-records/{attendanceRecord}
     */
    public function destroy(AttendanceRecord $attendanceRecord)
    {
        // 🔒 認可チェックを実行（現在のログインユーザーが、このデータを削除できるか）
        $this->authorize('delete', [$attendanceRecord, 'この操作を実行する権限がありません。']);

        // 💡 レコードを削除
        $attendanceRecord->delete();

        // ⭕ 204 No Content を返却
        return response()->noContent();
    }
}
