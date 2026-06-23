@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('title', 'メールアドレス認証')

@section('content')
<div class="container">
    <p class="message">登録していただいたメールアドレスに認証メールを送付しました。</p>
    <p class="message">メール認証を完了してください。</p>
    <div class="verify-button">
        <a href="https://mailtrap.io">認証はこちらから</a> 
    </div>
    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="button resend-button">認証メールを再送する</button>
    </form>
</div>
@endsection