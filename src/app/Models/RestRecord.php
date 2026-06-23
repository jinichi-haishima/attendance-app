<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_record_id',
        'rest_in_time',
        'rest_out_time',
    ];

    protected $casts = [
    'rest_in_time'  => 'datetime',
    'rest_out_time' => 'datetime',
    ];

    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }
}
