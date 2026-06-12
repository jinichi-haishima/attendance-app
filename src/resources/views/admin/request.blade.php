@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('title', '申請一覧')

@section('content')
<div class="wrapper">
    <div class="container">
        <h1 class="page-title">申請一覧</h1>
        <div class="attendance-table-container">
            <nav class="navigation">
                <div class="nav-item">
                    <a href="{{ route('attendance-requests.index', ['tab' => 'pending']) }}" class="{{ $currentTab === 'pending' ? 'current-link' : 'back-link' }}">承認待ち</i></a>
                </div>
                <div class="nav-item">
                    <a href="{{ route('attendance-requests.index', ['tab' => 'approved']) }}" class="{{ $currentTab === 'approved' ? 'current-link' : 'back-link' }}">承認済み</a>
                </div>
            </nav>
            <table class="attendance-table">
                <thead>
                    <tr class="attendance-table-header">
                        <th class="attendance-table-th">状態</th>
                        <th class="attendance-table-th">名前</th>
                        <th class="attendance-table-th">対象日時</th>
                        <th class="attendance-table-th">申請理由</th>
                        <th class="attendance-table-th">申請日時</th>
                        <th class="attendance-table-th">詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendanceRequests as $request)
                    <tr class="attendance-table-row">
                        <td class="attendance-table-td">
                            {{ $request->status }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $request->user->name }}
                        </td>
                        <td class="attendance-table-td">
                            {{ \Carbon\Carbon::parse($request->punch_in_time)->format('Y/m/d') }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $request->reason }}
                        </td>
                        <td class="attendance-table-td">
                            {{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}
                        </td>
                        <td class="attendance-table-td">
                            <a href="{{ route('attendance-records.detail', [
                                'id' => $request->id, 
                                'date' => $request->punch_in_time ? \Carbon\Carbon::parse($request->punch_in_time)->format('Y-m-d') : ''
                            ]) }}" class="detail-link" class="detail-link">詳細</a>
                        </td>
                        
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection