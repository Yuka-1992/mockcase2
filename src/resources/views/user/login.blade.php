@extends('layouts.app')
@section('title', 'ログイン')
@section('content')
  <h1>ログイン</h1>

  <form method="POST" action="{{ url('/login') }}">
    @csrf
    <div>
      <label for="email">メールアドレス</label>
      <input id="email" name="email" type="email" value="{{ old('email') }}" required>
    </div>
    <div>
      <label for="password">パスワード</label>
      <input id="password" name="password" type="password" required>
    </div>
    <div>
      <button type="submit">ログインする</button>
    </div>
  </form>

  <div>
    <a href="{{ url('/register') }}">会員登録はこちら</a>
  </div>
@endsection