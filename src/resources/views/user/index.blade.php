@extends('layouts.app')
@section('title', '勤怠一覧')

@push('styles')
  @vite('resources/css/list.css')
@endpush

@section('content')
  <h1>勤怠一覧</h1>

  <div class="month-nav">
    <a class="nav-btn" href="{{ route('user.index') }}?month={{ $prevMonth }}">←前月</a>
    <div class="month-title">
      <span aria-hidden="true">📅</span>
      <strong>{{ $month->format('Y/m') }}</strong>
    </div>
    <a class="nav-btn" href="{{ route('user.index') }}?month={{ $nextMonth }}">翌月→</a>
  </div>

  @php $weekdays = ['日','月','火','水','木','金','土']; @endphp

  <table class="list-table">
    <thead>
      <tr>
        <th>日付</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
      @foreach($rows as $row)
        @php
          $a = $row['attendance'] ?? null;
          $dateStr = $row['date']->format('Y-m-d');
          $dateDisp = $row['date']->format('m/d') . '（' . $weekdays[$row['date']->dayOfWeek] . '）';
          // 出勤がない日は空欄
          $in  = ($a && $a->clock_in)  ? \Carbon\Carbon::parse($a->clock_in)->format('H:i') : '';
          $out = ($a && $a->clock_out) ? \Carbon\Carbon::parse($a->clock_out)->format('H:i') : '';
          // 分→HH:MM 変換
          $toHm = function($m){ $m = (int)$m; return sprintf('%02d:%02d', intdiv($m,60), $m%60); };
          $break = ($a && $a->clock_in) ? $toHm($a->break_time ?? 0) : '';
          $work  = ($a && $a->clock_in) ? $toHm($a->work_time  ?? 0) : '';
        @endphp
        <tr>
          <td class="td-date">{{ $dateDisp }}</td>
          <td>{{ $in }}</td>
          <td>{{ $out }}</td>
          <td>{{ $break }}</td>
          <td>{{ $work }}</td>
          <td>
            @if($a && $a->id)
              <a href="{{ route('user.show', ['id' => $a->id]) }}">詳細</a>
            @else
              <a href="{{ route('user.show', ['id' => 0]) }}?date={{ $dateStr }}">詳細</a>
            @endif
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
@endsection