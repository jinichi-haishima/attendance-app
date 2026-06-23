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
            'punch_in_time.required'    => '出勤時間は必須です',
            'punch_in_time.date_format' => '出勤時間は HH:MM 形式で指定してください。',
            'punch_in_time.before'      => '出勤時間もしくは退勤時間が不適切な値です',
            'punch_out_time.required'   => '退勤時間は必須です',
            'punch_out_time.date_format'=> '退勤時間は HH:MM 形式で指定してください。',
            'reason.required'           => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $punchIn = $this->input('punch_in_time');
            $punchOut = $this->input('punch_out_time');
            $restRecords = $this->input('rest_records', []);

            // 💡 Carbonを使って「H:i」形式かどうかを安全にチェック
            $isValidTime = function ($time) {
                if (empty($time) || !is_string($time)) {
                    return false;
                }
                return Carbon::hasFormat($time, 'H:i');
            };

            // 💡 出退勤が両方とも正しい「時間形式」の時だけ処理を進める
            if ($isValidTime($punchIn) && $isValidTime($punchOut)) {
                $punchInTime = Carbon::parse($punchIn);
                $punchOutTime = Carbon::parse($punchOut);

                foreach ($restRecords as $restData) {
                    $restIn = $restData['rest_in_time'] ?? null;
                    $restOut = $restData['rest_out_time'] ?? null;

                    // 休憩時間も同様に正しい形式のときだけチェック
                    if ($isValidTime($restIn) && $isValidTime($restOut)) {
                        $restInTime = Carbon::parse($restIn);
                        $restOutTime = Carbon::parse($restOut);

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
