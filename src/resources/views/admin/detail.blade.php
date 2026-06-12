@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('title', '勤怠詳細')

@section('content')
<div class="wrapper">
    <div class="container">
        <h1 class="page-title">勤怠詳細</h1>
        @if (session('success'))
            <p class="alert alert-success">
                {{ session('success') }}
            </p>
        @endif

        <form method="POST" action="{{ route('admin.attendance.update') }}" class="attendance-detail-form">
            @csrf
            <input type="hidden" name="record_id" value="{{ $attendanceRecord->id }}">
            <table class="attendance-detail-table">
                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">名前</th>
                    <td class="attendance-detail-td">{{ $attendanceRecord->user?->name }}</td>
                </tr>

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">日付</th>
                    <td class="attendance-detail-td">
                        <span class="text-display">
                            {{ $attendanceRecord->punch_in_time ? \Carbon\Carbon::parse($attendanceRecord->punch_in_time)->format('Y年') : '' }}
                        </span>
                        <span class="text-display">
                            {{ $attendanceRecord->punch_in_time ? \Carbon\Carbon::parse($attendanceRecord->punch_in_time)->isoFormat('M月D日(ddd)') : '' }}
                        </span>
                    </td>
                </tr>

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">出勤・退勤</th>
                    <td class="attendance-detail-td flex-td">
                        @if($latestRequest && ($latestRequest->status === '承認待ち' || $latestRequest->status === '承認済み'))
                            {{-- 💡 承認待ち、承認済みの時は、ただの文字（spanなど）で表示する --}}
                            <span class="text-display">
                                {{ \Carbon\Carbon::parse($latestRequest->punch_in_time)->format('H:i') }}
                            </span>
                            <span class="time-separator">〜</span>
                            <span class="text-display">
                                {{ $latestRequest->punch_out_time ? \Carbon\Carbon::parse($latestRequest->punch_out_time)->format('H:i') : '未打刻' }}
                            </span>
                        @else
                            {{-- 通常時は、こないだ作ったインプット欄を表示する --}}
                            <input type="text" name="punch_in_time" class="punch-time-input"
                                value="{{ old('punch_in_time', $attendanceRecord->punch_in_time ? \Carbon\Carbon::parse($attendanceRecord->punch_in_time)->format('H:i') : '') }}">
                            <span class="time-separator">〜</span>
                            <input type="text" name="punch_out_time" class="punch-time-input"
                                value="{{ old('punch_out_time', $attendanceRecord->punch_out_time ? \Carbon\Carbon::parse($attendanceRecord->punch_out_time)->format('H:i') : '') }}">
                        @endif
                    </td>
                </tr>

                @foreach($attendanceRecord->rest_records as $rest)
                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">休憩 {{ $loop->index + 1 }}</th>
                    <td class="attendance-detail-td flex-td">
                        @if($latestRequest && ($latestRequest->status === '承認待ち' || $latestRequest->status === '承認済み'))
                            {{-- 💡 承認待ち、承認済みの時は、ただの文字（spanなど）で表示する --}}
                            <span class="text-display">
                                {{ $rest->rest_in_time ? \Carbon\Carbon::parse($rest->rest_in_time)->format('H:i') : '' }}
                            </span>
                            <span class="time-separator">〜</span>
                            <span class="text-display">
                                {{ $rest->rest_out_time ? \Carbon\Carbon::parse($rest->rest_out_time)->format('H:i') : '' }}
                            </span>
                        @else
                        <input type="hidden" name="rest_records[{{ $loop->index }}][id]" value="{{ $rest->id }}">
                        
                        <input type="text" name="rest_records[{{ $loop->index }}][rest_in_time]" class="rest-time-input" 
                            value="{{ $rest->rest_in_time ? \Carbon\Carbon::parse($rest->rest_in_time)->isoFormat('HH:mm') : '' }}">
                        <span class="time-separator">〜</span>
                        <input type="text" name="rest_records[{{ $loop->index }}][rest_out_time]" class="rest-time-input" 
                            value="{{ $rest->rest_out_time ? \Carbon\Carbon::parse($rest->rest_out_time)->isoFormat('HH:mm') : '' }}">
                        @endif
                        @error('rest_time')
                            <div class="error-message">{{ $message }}</div>
                        @enderror

                        @error('rest_out_time_error')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                @endforeach

                @if(!$latestRequest || $latestRequest->status !== '承認待ち')
                    {{-- 💡 承認待ち、承認済みの時は、追加の休憩時間の入力欄は表示しない --}}
                    <tr class="attendance-detail-row">
                        <th class="attendance-detail-th">休憩{{ $attendanceRecord->rest_records->count() + 1 }}（追加分）</th>
                        <td class="attendance-detail-td flex-td">
                            <input type="text" name="rest_records[new][rest_in_time]" class="rest-time-input" value="" placeholder="00:00">
                            <span class="time-separator">〜</span>
                            <input type="text" name="rest_records[new][rest_out_time]" class="rest-time-input" value="" placeholder="00:00">
                        </td>
                    </tr>
                @endif

                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">備考</th>
                    @if($latestRequest && ($latestRequest->status === '承認待ち' || $latestRequest->status === '承認済み'))
                        {{-- 💡 承認待ち、承認済みの時は、ただの文字（spanなど）で表示する --}}
                        <td class="attendance-detail-td" colspan="2">
                            <span class="text-display">{{ $latestRequest->reason }}</span>
                        </td>
                    @else
                        <td class="attendance-detail-td" colspan="2">
                            <input type="text" name="reason" class="reason-input" value="{{ $attendanceRecord->reason }}" placeholder="修正理由を入力してください">
                            @error('reason')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </td>
                    @endif
                </tr>
            </table>
            <div class="button-container">
                @if($latestRequest && $latestRequest->status === '承認待ち')
                    <div class="alert-warning"> *承認待ちのため修正はできません。</div>
                @elseif($latestRequest && $latestRequest->status === '承認済み')
                    <div class="alert-approved"> 
                        <button type="button" class="disabled-button" disabled>承認済み</button>
                    </div>
                @else
                    <button type="submit" class="submit-button">修正</button>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
