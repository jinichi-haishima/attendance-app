<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_request_id',
        'rest_in_time',
        'rest_out_time', 
    ];

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
