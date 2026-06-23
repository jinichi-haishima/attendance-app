<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        /**
         * 💡 ここで、勤怠レコードの更新に必要なバリデーションルールを定義します。
         * 例えば、以下のようなルールが考えられます。
         * - date: 勤怠日（YYYY-MM-DD形式）で、同一ユーザーの同一日付の勤怠レコードが存在しないこと（ただし、更新対象のレコードは除外）
         * - clock_in: 出勤時刻（HH:MM:SS形式）で必須
         * - clock_out: 退勤時刻（HH:MM:SS形式）で、出勤時刻より後の時刻であれば任意
         * - comment: 備考（255文字以内で任意）
         * これらのルールは、実際の要件に応じて適宜調整してください。
         * また、ルールの中で、更新対象の勤怠レコードを識別するために、ルートパラメータからレコードIDを取得し、重複チェックの際に除外するようにします。
         */
        $attendanceRecord = $this->route('attendanceRecord');
        $recordId = $attendanceRecord ? $attendanceRecord->id : null;

        // 💡 この勤怠レコードを所有しているユーザーのID（他人のデータを書き換えないようにするため）
        $userId = $attendanceRecord ? $attendanceRecord->user_id : auth()->id();
        $inputDate = $this->input('date');

        return [
            'date' => [
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records', 'punch_in_time')
                    ->ignore($recordId) // 👈 自分のIDのデータなら、重複していてもスルーしてOK！
                    ->where(function ($query) use ($userId, $inputDate) {
                        return $query->where('user_id', $userId)
                                     ->whereDate('punch_in_time', $inputDate);
                    }),
            ],
            'clock_in' => [
                'required',
                'date_format:H:i:s',
            ],
            'clock_out' => [
                'nullable',
                'date_format:H:i:s',
                'after:clock_in'
            ],
            'comment' => [
                'nullable',
                'string',
                'max:255'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'         => '勤怠日は必須です。',
            'date.date_format'      => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique'           => 'この日付の勤怠は既に登録されています。',
            'clock_in.required'     => '出勤時刻は必須です。',
            'clock_in.date_format'  => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.after'       => '退勤時刻は出勤時刻より後の時刻を指定してください。',
            'comment.max'           => '備考は 255 文字以内で入力してください。',
        ];
    }
}
