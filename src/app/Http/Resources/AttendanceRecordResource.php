<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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
            'id' => $this->id,
            'user_id' => $this->user_id,
            'date' => $this->punch_in_time ? Carbon::parse($this->punch_in_time)->format('Y-m-d') : null,
            'clock_in' => $this->punch_in_time,
            'clock_out' => $this->punch_out_time,
            'comment' => $this->reason,
            'user' => new UserResource($this->whenLoaded('user')),
            'rest_records' => RestRecordResource::collection($this->whenLoaded('rest_records')),
            
        ];
    }
}
