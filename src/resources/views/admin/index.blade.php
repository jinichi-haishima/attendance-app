@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('title', '勤怠管理')

@section('content')
<div class="wrapper">
    <div class="container">
        <h1 class="page-title">{{ \Carbon\Carbon::now()->format('Y年m月d日') }}</h1>
        <div class="monthly-display">
            <a href="{{ route('admin.index', ['date' => $previousDate]) }}" class="monthly-link"><i class="fa-solid fa-arrow-left"></i>前日</a>
            <div class="monthly-title">
                <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
                <span class="monthly-text">{{ $displayDate }}</span>
            </div>           
            <a href="{{ route('admin.index', ['date' => $nextDate]) }}" class="monthly-link">翌日<i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="attendance-table-container">
            <table class="attendance-table">
                <thead>
                    <tr class="attendance-table-header">
                        <th class="attendance-table-th">名前</th>
                        <th class="attendance-table-th">出勤</th>
                        <th class="attendance-table-th">退勤</th>
                        <th class="attendance-table-th">休憩</th>
                        <th class="attendance-table-th">合計</th>
                        <th class="attendance-table-th">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendanceRecords as $record)
                    <tr class="attendance-table-row">
                        <td class="attendance-table-td">
                            {{ $record->user->name }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $record->punch_in_time ? \Carbon\Carbon::parse($record->punch_in_time)->isoFormat('HH:mm') : '' }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $record->punch_out_time ? \Carbon\Carbon::parse($record->punch_out_time)->isoFormat('HH:mm') : '' }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $record->formatted_rest_time }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $record->formatted_work_time }}
                        </td>
                        <td class="attendance-table-td">
                            <a href="{{ route('admin.detail', ['date' => $dateInput, 'user_id' => $record->user_id]) }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection