@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'ユーザー登録')

@section('header')
    @include('layouts.header')
@endsection

@section('content')
    <form method="POST" action="{{ route('register') }}" class="auth-form">
        @csrf
        <h1 class="auth-form-title">会員登録</h1>
        <div class="auth-form-group">
            <label for="name" class="auth-form-label">名前</label>
            <input type="text" id="name" name="name" class="auth-form-input" value="{{ old('name') }}">
            @error('name')
                <div class="auth-form-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="auth-form-group">
            <label for="email" class="auth-form-label">メールアドレス</label>
            <input type="email" id="email" name="email" class="auth-form-input" value="{{ old('email') }}">
            @error('email')
                <div class="auth-form-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="auth-form-group">
            <label for="password" class="auth-form-label">パスワード</label>
            <input type="password" id="password" name="password" class="auth-form-input">
            @error('password')
                <div class="auth-form-error">{{ $message }}</div>
            @enderror
        </div>
        <div class="auth-form-group">
            <label for="password_confirmation" class="auth-form-label">パスワード確認</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="auth-form-input">
        </div>
        <button type="submit" class="auth-form-button">登録する</button>
        <a href="{{ route('login') }}" class="auth-form-link">ログインはこちら</a>
    </form>

@endsection