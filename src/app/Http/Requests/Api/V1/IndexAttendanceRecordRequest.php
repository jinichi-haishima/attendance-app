<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexAttendanceRecordRequest extends FormRequest
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
        return [
            'user_id'  => 'nullable|integer|exists:users,id',
            'date'     => 'nullable|date_format:Y-m-d',
            'month'    => 'nullable|date_format:Y-m',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page'     => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists'    => '指定されたユーザーが存在しません。',
            'date.date_format'  => '検索日付は YYYY-MM-DD 形式で指定してください。',
            'month.date_format' => '検索月は YYYY-MM 形式で指定してください。',
            'per_page.max'      => '1ページの最大表示件数は 100 件です。',
        ];
    }
}
