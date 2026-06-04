@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'ログイン')

@section('header')
    @include('layouts.header')
@endsection

@section('content')
    <form method="POST" action="{{ route('login') }}" class="auth-form">
        @csrf
        <h1 class="auth-form-title">ログイン</h1>
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
        <button type="submit" class="auth-form-button">ログイン</button>
        <a href="{{ route('register') }}" class="auth-form-link">会員登録はこちら</a>
    </form>
@endsection