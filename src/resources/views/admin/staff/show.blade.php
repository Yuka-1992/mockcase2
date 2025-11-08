@extends('layouts.app')
@section('title', '勤怠詳細')
@push('styles')
  @vite('resources/css/show.css')
@endpush
@section('content')
  <h1>勤怠詳細</h1>

  <form id="attendance-update-form" method="POST" action="{{ route('admin.staff.update', ['id' => $user->id]) }}">
    @csrf

    <input type="hidden" name="work_date" value="{{ optional($attendance)->work_date ?? request('date') }}">

    <table class="attendance-table">
      <colgroup>
        <col>
        <col>
        <col>
        <col>
      </colgroup>
      <tbody>
        <tr>
          <th>名前</th>
          <td>{{ $user->name ?? '' }}</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <th>日付</th>
          @php $d = isset($attendance->work_date) ? \Carbon\Carbon::parse($attendance->work_date) : (request('date') ? \Carbon\Carbon::parse(request('date')) : now()); @endphp
          <td>{{ $d->year }}年</td>
          <td></td>
          <td>{{ $d->format('n月j日') }}</td>
        </tr>
        <tr>
          <th>出勤時間</th>
          <td>
            <input type="time" name="clock_in" value="{{ old('clock_in', optional($attendance && $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in) : null)->format('H:i')) }}">
          </td>
          <td>〜</td>
          <td>
            <input type="time" name="clock_out" value="{{ old('clock_out', optional($attendance && $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out) : null)->format('H:i')) }}">
          </td>
        </tr>
        @php
          // 休憩時間の初期値を取得
          $startArr = old('break_start', []);
          $endArr = old('break_end', []);
          
          // old()が空の場合は、データベースから取得した休憩時間を使用
          if (empty($startArr) && empty($endArr) && isset($breakTimes) && is_array($breakTimes)) {
            foreach ($breakTimes as $breakTime) {
              $startArr[] = $breakTime['start'] ?? '';
              $endArr[] = $breakTime['end'] ?? '';
            }
          }
          
          $rows = max(count($startArr), count($endArr));
          if ($rows < 1) { $rows = 1; }
          $renderRows = $rows + 1; // 既存＋1行の空行
        @endphp
        @for ($i = 0; $i < $renderRows; $i++)
          @php $label = $i === 0 ? '休憩' : '休憩'.($i+1); @endphp
          <tr>
            <th>{{ $label }}</th>
            <td>
              <input type="time" name="break_start[]" value="{{ old('break_start.'.$i, $startArr[$i] ?? '') }}">
            </td>
            <td>〜</td>
            <td>
              <input type="time" name="break_end[]" value="{{ old('break_end.'.$i, $endArr[$i] ?? '') }}">
            </td>
          </tr>
        @endfor
        <tr>
          <th>備考</th>
          <td colspan="3">
            <textarea name="note" rows="3">{{ old('note', $attendance->note ?? '') }}</textarea>
          </td>
        </tr>
      </tbody>
    </table>
  </form>
  <div class="form-actions">
    <button type="submit" form="attendance-update-form">修正</button>
  </div>
@endsection
