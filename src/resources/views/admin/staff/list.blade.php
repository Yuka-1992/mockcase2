@extends('layouts.app')
@section('title', 'スタッフ一覧')

@section('content')
  <h1>スタッフ一覧</h1>

  <table border="1" cellspacing="0" cellpadding="8" style="width:100%; border-collapse:collapse; text-align:left;">
    <thead>
      <tr>
        <th>名前</th>
        <th>メールアドレス</th>
        <th style="text-align:center;">月次勤怠</th>
      </tr>
    </thead>
    <tbody>
      @forelse($users as $u)
        @if(($u->role ?? null) === 'user')
          <tr>
            <td>{{ $u->name ?? '(未設定)' }}</td>
            <td>{{ $u->email }}</td>
            <td style="text-align:center;">
              <a href="{{ route('admin.staff.attendances', ['id' => $u->id]) }}">詳細</a>
            </td>
          </tr>
        @endif
      @empty
        <tr>
          <td colspan="3" style="text-align:center;">該当ユーザーがいません。</td>
        </tr>
      @endforelse
    </tbody>
  </table>
@endsection