@extends('layouts.app')
@section('title', '勤怠打刻')
@section('content')
  @php
    $status = $status ?? 'off'; // 'off'|'working'|'break'|'done'
    $statusLabel = [
      'off' => '勤務外',
      'working' => '出勤中',
      'break' => '休憩中',
      'done' => '退勤済み',
    ][$status] ?? '勤務外';
  @endphp

  <div class="status-badge-container">
    <div class="status-badge">{{ $statusLabel }}</div>
  </div>

  <div class="clock-display">
    <div id="date-display">{{ now()->format('Y年n月j日') }}（{{ ['日', '月', '火', '水', '木', '金', '土'][now()->dayOfWeek] }}）</div>
    <div id="time-display">{{ now()->format('H:i') }}</div>
  </div>

  @if ($errors->any())
    <div>
      @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
      @endforeach
    </div>
  @endif

  <div class="clock-buttons">
    @if(in_array($status, ['off','done']))
      <form method="POST" action="{{ route('attendance.store') }}">
        @csrf
        <input type="hidden" name="action" value="clock_in">
        <button type="submit" class="btn-clock">出勤</button>
      </form>
    @elseif($status === 'working')
      <form method="POST" action="{{ route('attendance.store') }}">
        @csrf
        <input type="hidden" name="action" value="clock_out">
        <button type="submit" class="btn-clock">退勤</button>
      </form>
      <form method="POST" action="{{ route('attendance.store') }}">
        @csrf
        <input type="hidden" name="action" value="break_in">
        <button type="submit" class="btn-break">休憩入</button>
      </form>
    @elseif($status === 'break')
      <form method="POST" action="{{ route('attendance.store') }}">
        @csrf
        <input type="hidden" name="action" value="break_out">
        <button type="submit" class="btn-break">休憩戻</button>
      </form>
    @endif
  </div>

  <script>
    (function tick(){
      const dateEl = document.getElementById('date-display');
      const timeEl = document.getElementById('time-display');
      if (dateEl && timeEl) {
        const pad = n => String(n).padStart(2,'0');
        const d = new Date();
        const weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        const dateStr = `${d.getFullYear()}年${d.getMonth()+1}月${d.getDate()}日（${weekdays[d.getDay()]}）`;
        const timeStr = `${pad(d.getHours())}:${pad(d.getMinutes())}`;
        dateEl.textContent = dateStr;
        timeEl.textContent = timeStr;
      }
      setTimeout(tick, 1000);
    })();
  </script>
@endsection