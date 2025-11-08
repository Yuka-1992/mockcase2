@extends('layouts.app')

@section('title', '会員登録')

@section('content')
  <h1>会員登録</h1>

  <form method="POST" action="{{ url('/register') }}">
    @csrf

    <input type="hidden" name="role" value="user">

    <div>
      <label for="name">お名前</label>
      <input id="name" name="name" type="text" value="{{ old('name') }}" required>
      @error('name')<div>{{ $message }}</div>@enderror
    </div>

    <div>
      <label for="email">メールアドレス</label>
      <input id="email" name="email" type="email" value="{{ old('email') }}" required>
      @error('email')<div>{{ $message }}</div>@enderror
    </div>

    <div>
      <label for="password">パスワード</label>
      <input id="password" name="password" type="password" required>
      @error('password')<div>{{ $message }}</div>@enderror
    </div>

    <div>
      <label for="password_confirmation">パスワード（確認）</label>
      <input id="password_confirmation" name="password_confirmation" type="password" required>
    </div>

    <div>
      <button type="submit">登録する</button>
    </div>
  </form>

  <div>
    <a href="{{ url('/login') }}">ログインはこちら</a>
  </div>
@endsection
