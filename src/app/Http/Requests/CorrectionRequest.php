<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class CorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // 認証ロジックを必要に応じて実装してください
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'punch_in_time' => 'required|date_format:H:i|before:punch_out_time',
            'punch_out_time' => 'required|date_format:H:i',
            'reason' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'punch_in_time.required' => '出勤時間は必須です。',
            'punch_in_time.before' => '出勤時間もしくは退勤時間が不適切な値です',
            'punch_out_time.required' => '退勤時間は必須です。',
            'reason.required' => '修正理由を記入してください',
        ];
    }

    public function withValidator($validator)
    {
    $validator->after(function ($validator) {
        $punchIn = $this->input('punch_in_time');
        $punchOut = $this->input('punch_out_time');
        $restRecords = $this->input('rest_records', []);

        $punchInTime = $punchIn ? Carbon::parse($punchIn) : null;
        $punchOutTime = $punchOut ? Carbon::parse($punchOut) : null;

        // ★出退勤のチェックが消えて、休憩のチェックだけに集中できる！
        if ($punchInTime && $punchOutTime) {
            foreach ($restRecords as $restData) {
                if (!empty($restData['rest_in_time']) && !empty($restData['rest_out_time'])) {
                    $restInTime = Carbon::parse($restData['rest_in_time']);
                    $restOutTime = Carbon::parse($restData['rest_out_time']);

                    // 2. 休憩開始時間が出勤より前、または退勤より後
                    if ($restInTime->lt($punchInTime) || $restInTime->gt($punchOutTime)) {
                        if (!$validator->errors()->has('rest_time')) {
                            $validator->errors()->add('rest_time', '休憩時間が不適切な値です');
                        }
                    }

                    // 3. 休憩終了時間が退勤より後
                    if ($restOutTime->gt($punchOutTime)) {
                        if (!$validator->errors()->has('rest_out_time_error')) {
                            $validator->errors()->add('rest_out_time_error', '休憩時間もしくは退勤時間が不適切な値です');
                        }
                    }
                }
            }
        }
    });
    }
}
