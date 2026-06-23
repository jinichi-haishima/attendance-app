<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $casts = [
            'punch_in_time'  => 'datetime',
            'punch_out_time' => 'datetime',
    ];

    protected $fillable = [
            'user_id',
            'punch_in_time',
            'punch_out_time',
            'reason',
    ];

    public function rest_records()
    {
        return $this->hasMany(RestRecord::class, 'attendance_record_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function applications()
    {
        return $this->hasMany(AttendanceRequest::class, 'attendance_record_id');
    }

    public function getFormattedRestTimeAttribute()
    {
        // 総休憩分数（分）を計算
        $totalMinutes = $this->rest_records->sum(function($rest) 
        {
            if (!$rest->rest_in_time || !$rest->rest_out_time) return 0;
            
            return Carbon::parse($rest->rest_out_time)->diffInMinutes(Carbon::parse($rest->rest_in_time));
        });

        // 時間と分に分解して「H:i」の形にする
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%2d:%02d', $hours, $minutes);
    }

    // 勤務時間から休憩時間を引いた実働時間を「H:i」にする
    public function getFormattedWorkTimeAttribute()
    {
        if (!$this->punch_in_time || !$this->punch_out_time) {
            return '-';
        }

        //、$this->... から直接繋ぐ形に修正
        $totalWorkMinutes = $this->punch_in_time->diffInMinutes($this->punch_out_time);

        $totalRestMinutes = $this->rest_records->sum(function($rest) {
            if (!$rest->rest_in_time || !$rest->rest_out_time) return 0;
            return Carbon::parse($rest->rest_out_time)->diffInMinutes(Carbon::parse($rest->rest_in_time));
        });

        $actualMinutes = $totalWorkMinutes - $totalRestMinutes;
        if ($actualMinutes < 0) $actualMinutes = 0;

        $hours = floor($actualMinutes / 60);
        $minutes = $actualMinutes % 60;

        return sprintf('%2d:%02d', $hours, $minutes);
    }

    public function getActualWorkMinutesAttribute()
    {
        if (!$this->punch_in_time || !$this->punch_out_time) {
            return 0; // 未打刻などは0分とする
        }

        // 1. 総勤務分数（こちらも 
        $totalWorkMinutes = $this->punch_in_time->diffInMinutes($this->punch_out_time);

        // 2. 総休憩分数
        $totalRestMinutes = $this->rest_records->sum(function ($rest) {
            if (!$rest->rest_in_time || !$rest->rest_out_time) return 0;
            return Carbon::parse($rest->rest_out_time)->diffInMinutes(Carbon::parse($rest->rest_in_time));
        });

        $actualMinutes = $totalWorkMinutes - $totalRestMinutes;
        
        return $actualMinutes < 0 ? 0 : $actualMinutes;
    }
}
