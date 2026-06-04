@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="wrapper">
    <div class="container">
        <h1 class="page-title">勤怠詳細</h1>
        <form method="POST" action="">
            @csrf
            @method('PUT')
            <input type="hidden" name="record_id" value="{{ $record->id }}">
            <table class="attendance-detail-table">
                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">名前</th>
                    <td class="attendance-detail-td">{{ $record->user?->name }}</td>
                </tr>

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">日付</th>
                    <td class="attendance-detail-td">
                        <span class="date-year">
                            {{ $record->punch_in_time ? \Carbon\Carbon::parse($record->punch_in_time)->format('Y年') : '' }}
                        </span>
                        <span class="date-month-day">
                            {{ $record->punch_in_time ? \Carbon\Carbon::parse($record->punch_in_time)->isoFormat('M月D日(ddd)') : '' }}
                        </span>
                    </td>
                </tr>

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">出勤・退勤</th>
                    <td class="attendance-detail-td flex-td">
                        <input type="text" name="punch_in_time" class="punch-time-input" value="{{ $record->punch_in_time ? \Carbon\Carbon::parse($record->punch_in_time)->isoFormat('HH:mm') : '' }}">
                        <span class="time-separator">〜</span>
                        <input type="text" name="punch_out_time" class="punch-time-input" value="{{ $record->punch_out_time ? \Carbon\Carbon::parse($record->punch_out_time)->isoFormat('HH:mm') : '' }}">
                    </td>
                </tr>

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">休憩</th>
                    <td class="attendance-detail-td flex-td">
                        <input type="text" name="rest_in_time" class="rest-time-input" value="{{ $record->rest_in_time ? \Carbon\Carbon::parse($record->rest_in_time)->isoFormat('HH:mm') : '' }}">
                        <span class="time-separator">〜</span>
                        <input type="text" name="rest_out_time" class="rest-time-input" value="{{ $record->rest_out_time ? \Carbon\Carbon::parse($record->rest_out_time)->isoFormat('HH:mm') : '' }}">
                    </td>
                </tr>

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">休憩２</th>
                    <td class="attendance-detail-td flex-td">
                        <input type="text" name="rest_in_time_2" class="rest-time-input" value="{{ $record->rest_in_time_2 ? \Carbon\Carbon::parse($record->rest_in_time_2)->isoFormat('HH:mm') : '' }}">
                        <span class="time-separator">〜</span>
                        <input type="text" name="rest_out_time_2" class="rest-time-input" value="{{ $record->rest_out_time_2 ? \Carbon\Carbon::parse($record->rest_out_time_2)->isoFormat('HH:mm') : '' }}">
                    </td>
                </tr>

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">備考</th>
                    <td class="attendance-detail-td" colspan="2">
                        <input type="text" name="remarks" class="remarks-input" value="{{ $record->remarks }}">
                    </td>
                </tr>
            </table>
            <div class="button-container">
                <button class="back-button">修正</button>
            </div>
        </form>
    </div>
</div>
@endsection
