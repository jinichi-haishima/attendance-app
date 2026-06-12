@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection 

@section('title', 'スタッフ管理')

@section('content')
<div class="wrapper">
    <div class="container">
        <h1 class="page-title">スタッフ管理</h1>
        <div class="attendance-table-container">
            <table class="attendance-table">
                <thead>
                    <tr class="attendance-table-header">
                        <th class="attendance-table-th">名前</th>
                        <th class="attendance-table-th">メールアドレス</th>
                        <th class="attendance-table-th">月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr class="attendance-table-row">
                        <td class="attendance-table-td">
                            {{ $user->name }}
                        </td>
                        <td class="attendance-table-td">
                            {{ $user->email }}
                        </td>
                        <td class="attendance-table-td">
                            <a href="{{ route('admin.staff.show', ['id' => $user->id]) }}" class="attendance-link">詳細</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection