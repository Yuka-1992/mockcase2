@extends('layouts.app')
@section('title', '管理者ログイン')
@section('content')
  <h1>管理者ログイン</h1>

  <form method="POST" action="{{ url('/admin/login') }}">
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
      <button type="submit">管理者ログインする</button>
    </div>
  </form>
@endsection