@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="wrapper">
    <div class="container">
        <div class="messages">
            @if (session('success'))
                <p class="alert alert-success">
                    {{ session('success') }}
                </p>
            @endif
            @if (session('error'))
                <p class="alert alert-error">
                    {{ session('error') }}
                </p>
            @endif
        </div>
        <div class="status">
            {{ $status }}
        </div>
        <div class="date">
            {{ \Carbon\Carbon::now()->isoFormat('Y年M月D日(ddd)') }}
        </div>
        <div class="time">
            {{ \Carbon\Carbon::now()->isoFormat('HH:mm') }}
        </div>
        <div class="button-group">
            @if ($status === '勤務外')
                <form method="POST" action="{{ route('attendance.punch-in') }}">
                    @csrf
                    <button type="submit" name="action" value="work_start" class="btn btn-main">出勤</button>
                </form>
            @elseif ($status === '出勤中')
                <div class="button-group">
                    <form method="POST" action="{{ route('attendance.punch-out') }}">
                        @csrf
                        <button type="submit" name="action" value="work_end" class="btn btn-main">退勤</button>
                    </form>
                    <form method="POST" action="{{ route('attendance.rest-in') }}">
                        @csrf
                            <button type="submit" name="action" value="rest_start" class="btn btn-sub">休憩入</button>
                    </form>
                </div>
            @elseif ($status === '休憩中')
                <form method="POST" action="{{ route('attendance.rest-out') }}">
                    @csrf
                    <button type="submit" name="action" value="rest_end" class="btn btn-sub">休憩戻</button>
                </form>
            @elseif ($status === '退勤済み')
                <p class="status-message">お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>
@endsection
