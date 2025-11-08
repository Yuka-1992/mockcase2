@extends('layouts.app')
@section('title', '勤怠一覧（管理者）')
@section('content')
  <h1>{{ $day->format('Y年n月j日') }}の退勤</h1>

  <div style="display:flex; align-items:center; gap:12px; margin:8px 0 16px;">
    <form method="GET" action="{{ url('/admin/attendance/list') }}">
      <input type="hidden" name="date" value="{{ $prevDate }}">
      <button type="submit">← 前日</button>
    </form>
    <div style="font-size:1.1rem;">
      <span aria-hidden="true">📅</span>
      <span>{{ $day->format('Y/m/d') }}</span>
    </div>
    <form method="GET" action="{{ url('/admin/attendance/list') }}">
      <input type="hidden" name="date" value="{{ $nextDate }}">
      <button type="submit">翌日 →</button>
    </form>
  </div>

  <div style="height:8px;"></div>

  <table border="1" cellpadding="6" cellspacing="0">
    <thead>
      <tr>
        <th>名前</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
      @forelse($list as $row)
        <tr>
          <td>{{ $row->name ?? '（未設定）' }}</td>
          <td>{{ $row->clock_in ? \Carbon\Carbon::parse($row->clock_in)->format('H:i') : '-' }}</td>
          <td>{{ $row->clock_out ? \Carbon\Carbon::parse($row->clock_out)->format('H:i') : '-' }}</td>
          <td>{{ (int)($row->break_time ?? 0) }} 分</td>
          <td>{{ (int)($row->work_time ?? 0) }} 分</td>
          <td>
            <a href="{{ route('admin.staff.show', ['id' => $row->user_id]) }}?date={{ $day->toDateString() }}">詳細</a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6">該当日の勤怠はありません。</td>
        </tr>
      @endforelse
    </tbody>
  </table>
@endsection
