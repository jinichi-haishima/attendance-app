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
        <input type="hidden" name="record_id" value="{{ $attendanceRequest->id }}">
        <table class="attendance-detail-table">
            <tr class="attendance-detail-row">
                <th class="attendance-detail-th">名前</th>
                <td class="attendance-detail-td">{{ $attendanceRequest->user?->name }}</td>
            </tr>

            <tr class="attendance-detail-row">
                <th class="attendance-detail-th">日付</th>
                <td class="attendance-detail-td">
                    <span class="text-display">
                        {{ $attendanceRequest->punch_in_time ? \Carbon\Carbon::parse($attendanceRequest->punch_in_time)->format('Y年') : '' }}
                    </span>
                    <span class="text-display">
                        {{ $attendanceRequest->punch_in_time ? \Carbon\Carbon::parse($attendanceRequest->punch_in_time)->isoFormat('M月D日(ddd)') : '' }}
                    </span>
                </td>
            </tr>

            <tr class="attendance-detail-row">
                <th class="attendance-detail-th">出勤・退勤</th>
                <td class="attendance-detail-td flex-td">
                    <span class="text-display">
                        {{ $attendanceRequest->punch_in_time ? \Carbon\Carbon::parse($attendanceRequest->punch_in_time)->format('H:i') : '' }}
                    </span>
                    <span class="time-separator">〜</span>
                    <span class="text-display">
                        {{ $attendanceRequest->punch_out_time ? \Carbon\Carbon::parse($attendanceRequest->punch_out_time)->format('H:i') : '' }}
                    </span>
                </td>
            </tr>

            @if(!empty($attendanceRequest->rest_requests) && count($attendanceRequest->rest_requests) > 0)
                @foreach($attendanceRequest->rest_requests as $rest)
                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">休憩 {{ $loop->index + 1 }}</th>
                    <td class="attendance-detail-td flex-td">
                        <span class="text-display">
                            {{ $rest->rest_in_time ? \Carbon\Carbon::parse($rest->rest_in_time)->format('H:i') : '' }}
                        </span>
                        <span class="time-separator">〜</span>
                        <span class="text-display">
                            {{ $rest->rest_out_time ? \Carbon\Carbon::parse($rest->rest_out_time)->format('H:i') : '' }}
                        </span>
                    </td>
                </tr>
                @endforeach
                {{-- 💡【追加】ループが終わった直後に、プラス1した連番で「空の行」を1つだけ強制表示する --}}
                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">休憩 {{ count($attendanceRequest->rest_requests) + 1 }}</th>
                    <td class="attendance-detail-td flex-td"></td>
                </tr>

            @else
                {{-- 2. 休憩データが「1件も存在しない」場合は、最初から「休憩1」の空欄を出す --}}
                <tr class="attendance-detail-row">
                    <th class="attendance-detail-th">休憩 1</th>
                    <td class="attendance-detail-td flex-td"></td>
                </tr>
            @endif

            <tr class="attendance-detail-row">
                <th class="attendance-detail-th">備考</th>
                <td class="attendance-detail-td">
                    {{ $attendanceRequest ? $attendanceRequest->reason : '' }}
                </td>
            </tr>
        </table>
        @if($attendanceRequest->status === '承認待ち')
            <div class="button-container">
                <button type="button" id="approval-btn" data-id="{{ $attendanceRequest->id }}" name="action"  class="approve-button">承認</button>
            </div>
        @else
            <div class="button-container">
                <button type="button" disabled class="disabled-button">承認済み</button>
            </div>
        @endif
        </div>
</div>
<script>
    document.getElementById('approval-btn').addEventListener('click', function() {
        const requestId = this.getAttribute('data-id');

        fetch("{{ route('admin.attendance.approval.update', $attendanceRequest->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ action: 'approve' })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.textContent = '承認済み';
                this.disabled = true;
                this.classList.add('disabled-button');
                alert('勤怠が承認されました。');
            }else {
                alert('勤怠の承認に失敗しました。');
            }   
        })
        .catch(error => {
            console.error('Error:', error);
            alert('エラーが発生しました。');
        });
    })
</script>
@endsection