<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRecordRequest extends FormRequest
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
            $userId = auth()->id();
            $inputDate = $this->input('date');

            return [
                'date' => [
                    'required',
                    'date_format:Y-m-d',
                    // 💡 punch_in_time の日付部分と user_id の複合ユニーク
                    Rule::unique('attendance_records', 'punch_in_time')->where(function ($query) use ($userId, $inputDate) {
                        return $query->where('user_id', $userId)
                                    ->whereDate('punch_in_time', $inputDate);
                    }),
                ],
            'clock_in' => [
                'required',
                'date_format:H:i:s'
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

    public function messages():array
    {
        return [
            'date.required'     => '勤怠日は必須です。',
            'date.date_format'  => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique'       => 'この日付の勤怠は既に登録されています。',
            'clock_in.required'    => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.after'       => '退勤時刻は出勤時刻より後の時刻を指定してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}
