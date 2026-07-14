@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('title', '勤怠一覧')

@section('content')
<div class="wrapper">
    <div class="container">
        <h1 class="page-title">勤怠一覧</h1>
        <div class="monthly-display">
            <a href="{{ route('attendance-records.index', ['date' => $prevMonth]) }}" class="monthly-link"><i class="fa-solid fa-arrow-left"></i>前月</a>
            <div class="monthly-title">
                <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
                <span class="monthly-text">{{ $displayMonth }}</span>
            </div>
            <a href="{{ route('attendance-records.index', ['date' => $nextMonth]) }}" class="monthly-link">翌月<i class="fa-solid fa-arrow-right"></i></a>
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
                    @foreach($calendarDates as $item)
                    <tr class="attendance-table-row">
                        <td class="attendance-table-td">
                            {{ $item['date']->isoFormat('MM/DD(ddd)') }}
                        </td>
                        @if($item['record'])
                        <td class="attendance-table-td">
                            {{ $item['record'] && $item['record']->punch_in_time && \Carbon\Carbon::parse($item['record']->punch_in_time)->format('H:i:s') !== '00:00:00' ? \Carbon\Carbon::parse($item['record']->punch_in_time)->isoFormat('HH:mm') : '' }}

                        </td>
                        <td class="attendance-table-td">
                            {{ $item['record']->punch_out_time ? \Carbon\Carbon::parse($item['record']->punch_out_time)->isoFormat('HH:mm') : '' }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $item['record']->punch_in_time && \Carbon\Carbon::parse($item['record']->punch_in_time)->format('H:i:s') !== '0:00:00' ? $item['record']->formatted_rest_time : '' }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $item['record']->punch_in_time && \Carbon\Carbon::parse($item['record']->punch_in_time)->format('H:i:s') !== '00:00:00' ? $item['record']->formatted_work_time : '' }}
                        </td>
                        @else
                        <td class="attendance-table-td"></td>
                        <td class="attendance-table-td"></td>
                        <td class="attendance-table-td"></td>
                        <td class="attendance-table-td"></td>
                        @endif
                        <td class="attendance-table-td">
                            <a href="{{ route('attendance-records.detail', ['id' => auth()->id()]) }}?date={{ $item['date']->format('Y-m-d') }}" class="detail-link">詳細</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection