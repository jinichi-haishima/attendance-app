@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection 

@section('title', 'スタッフ勤怠詳細')

@section('content')
<div class="wrapper">
    <div class="container">
        <h1 class="page-title">{{ $user->name }}さんの勤怠</h1>
        <div class="monthly-display">
            <a href="{{ route('admin.staff.show',['id' => $user->id, 'date' => $prevMonth]) }}" class="monthly-link"><i class="fa-solid fa-arrow-left"></i>前月</a>
            <div class="monthly-title">
                <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
                <span class="monthly-text">{{ $displayMonth }}</span>
            </div>
            <a href="{{ route('admin.staff.show', ['id' => $user->id, 'date' => $nextMonth]) }}" class="monthly-link">翌月<i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="attendance-table-container">
            <table class="attendance-table">
                <thead>
                    <tr class="attendance-table-header">
                        <th class="attendance-table-th">日付</th>
                        <th class="attendance-table-th">出勤</th>
                        <th class="attendance-table-th">退勤</th>
                        <th class="attendance-table-th">休憩</th>
                        <th class="attendance-table-th">合計</th>
                        <th class="attendance-table-th">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calendarDates as $attendance)
                    <tr class="attendance-table-row">
                        <td class="attendance-table-td">
                            {{ $attendance['date']->format('m/d') }}({{ $attendance['date']->isoFormat('ddd') }})
                        </td>
                        {{-- 出勤時間 --}}
                        <td class="attendance-table-td">
                            {{ $attendance['attendance']?->punch_in_time ? \Carbon\Carbon::parse($attendance['attendance']->punch_in_time)->format('H:i') : '' }}
                        </td>
                        
                        {{-- 退勤時間 --}}
                        <td class="attendance-table-td">
                            {{ $attendance['attendance']?->punch_out_time ? \Carbon\Carbon::parse($attendance['attendance']->punch_out_time)->format('H:i') : '' }}
                        </td>
                        
                        {{-- 休憩時間 --}}
                        <td class="attendance-table-td">
                            {{ $attendance['attendance'] ? $attendance['attendance']->formatted_rest_time : '' }}
                        </td>
                        
                        {{-- 労働時間 --}}
                        <td class="attendance-table-td">
                            {{ $attendance['attendance'] ? $attendance['attendance']->formatted_work_time : '' }}
                        </td>
    

                        <td class="attendance-table-td">
                            <a href="{{ route('admin.detail', ['date' => $attendance['date']->format('Y-m-d'), 'user_id' => $user->id]) }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection