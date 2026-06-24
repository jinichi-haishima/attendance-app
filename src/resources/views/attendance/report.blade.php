@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/report.css') }}">
@endsection

@section('title', 'マイ勤怠レポート')

@section('content')
<div class="wrapper">
    <div class="container">
        <h1>マイ勤怠レポート</h1>
        <p class="description">過去６ヶ月の勤怠データから集計しています。</p>
        <div class="summary-container">
            <h2>基本サマリー</h2>
            <div class="summary-list">
                <dl class="summary-item">
                    <dt class="summary-title">総労働時間</dt>
                    <dd class="summary-value">{{ $summary['total_working_hours'] }}</dd>
                </dl>
                <dl class="summary-item">
                    <dt class="summary-title">総残業時間</dt>
                    <dd class="summary-value">{{ $summary['total_overtime_hours'] }}</dd>
                </dl>
                <dl class="summary-item">
                    <dt class="summary-title">平均労働時間/日</dt>
                    <dd class="summary-value">{{ $summary['average_working_hours'] }}/日</dd>
                </dl>
            </div>

            <div class="monthly-container">
                <h2>月次推移（過去６ヶ月）</h2>
                <table class="monthly-table">
                    <thead>
                        <tr class="monthly-table-header">
                            <th>月</th>
                            <th>労働時間</th>
                            <th>残業時間</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($graphData as $data)
                        <tr class="monthly-table-row">
                            <td>{{ $data['month'] }}</td>
                            <td>{{ $data['working_hours'] }}</td>
                            <td>{{ $data['overtime_hours'] }}</td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>

            <div class="monthly-alert">
                <h2>今月の異常検知</h2>
                <p>基準：始業09:00/終業18:00/長時間労働は1日10時間超</p>
                <div class="summary-list">
                    <dl class="summary-item">
                        <dt class="summary-title">遅刻回数</dt>
                        <dd class="summary-value">{{ $summary['late_count'] }}回</dd>
                    </dl>
                    <dl class="summary-item">
                        <dt class="summary-title">早退回数</dt>
                        <dd class="summary-value">{{ $summary['early_leave_count'] }}回</dd>
                    </dl>
                    <dl class="summary-item">
                        <dt class="summary-title">長時間労働日数</dt>
                        <dd class="summary-value">{{ $summary['long_work_count'] }}日</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection