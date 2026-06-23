<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'user'             => new UserResource($this->whenLoaded('user')),
            'date'             => $this->punch_in_time ? $this->punch_in_time->format('Y-m-d') : null,
            'clock_in'         => $this->punch_in_time ? $this->punch_in_time->format('H:i:s') : null,
            'clock_out'        => $this->punch_out_time ? $this->punch_out_time->format('H:i:s') : null,
            'total_time'       => $this->formatted_work_time,
            'total_break_time' => $this->formatted_rest_time,
            'comment'          => $this->reason,
            'breaks'           => AttendanceBreakResource::collection($this->whenLoaded('rest_records')),
            'applications'     => ApplicationResource::collection($this->whenLoaded('applications')),
        ];
    }
}
