<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_record_id',
        'punch_in_time',
        'punch_out_time', 
        'requested_time',
        'reason',
        'status', // 例: 'pending', 'approved', 'rejected'
        'approved_by',
    ];
    public function getStatusAttribute($value)
    {
        return match ($value) {
            'pending'  => '承認待ち',
            'approved' => '承認済み',
            'rejected' => '却下',
            default    => $value,
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function attendance_record()
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function rest_requests()
    {
        return $this->hasMany(RestRequest::class);
    }
}
