<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
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
            // 仕様書の指定キー => マイグレーションのカラム名
            'id'             => $this->id,
            'status'         => $this->status, 
            'punch_in_time'  => $this->punch_in_time,  
            'punch_out_time' => $this->punch_out_time, 
            'comment'        => $this->reason,         // ⭕ 仕様書は comment、DBは reason で綺麗にマッピング！
            'created_at'     => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
