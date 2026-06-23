<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class AttendanceBreakResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // 💡 休憩モデル（RestRecordなど）の dateTime 型カラムが $casts されていれば、
        // そのまま format() を呼んで時刻（HH:mm:ss）だけを切り出せます。
        return [
            'id'        => $this->id,
            'break_in'  => $this->rest_in_time ? $this->rest_in_time->format('H:i:s') : null,
            'break_out' => $this->rest_out_time ? $this->rest_out_time->format('H:i:s') : null,
            // ※カラム名（rest_in_time等）は、ご自身の休憩テーブルに合わせて調整してください！
        ];
    }
}
