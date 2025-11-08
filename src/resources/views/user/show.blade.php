@extends('layouts.app')
@section('title', '勤怠詳細')
@push('styles')
  @vite('resources/css/show.css')
@endpush
@section('content')
  <h1>勤怠詳細</h1>

  <form id="user-attendance-correction-form">
    @csrf
    <input type="hidden" name="work_date" value="{{ optional($attendance)->work_date ?? request('date') }}">
    <input type="hidden" name="attendance_id" value="{{ $attendance->id ?? '' }}">

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
          <td>{{ $userName ?? '-' }}</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <th>日付</th>
          @php
            $dateObj = $workDate ? \Carbon\Carbon::parse($workDate) : null;
          @endphp
          <td>{{ $dateObj ? $dateObj->year . '年' : '-' }}</td>
          <td></td>
          <td>{{ $dateObj ? $dateObj->format('n月j日') : '-' }}</td>
        </tr>
        <tr>
          <th>出勤・退勤</th>
          <td>
            <input type="time" name="clock_in" id="clock_in_input" value="{{ $attendance && $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}">
          </td>
          <td>～</td>
          <td>
            <input type="time" name="clock_out" id="clock_out_input" value="{{ $attendance && $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
          </td>
        </tr>
        @php
          // 休憩時間の配列を準備
          $breakStarts = old('break_start', []);
          $breakEnds = old('break_end', []);
          
          // 既存の休憩データがある場合、それを表示用に準備
          // 現在はbreak_timeが分単位の合計しかないため、フォーム入力値のみを使用
          // 値がある休憩の数をカウント
          $breakCount = 0;
          for ($i = 0; $i < max(count($breakStarts), count($breakEnds)); $i++) {
            if (!empty($breakStarts[$i]) || !empty($breakEnds[$i])) {
              $breakCount = $i + 1;
            }
          }
          // 最低1行は表示
          if ($breakCount < 1) {
            $breakCount = 1;
          }
        @endphp
        @for ($i = 0; $i < $breakCount; $i++)
          <tr class="break-row" data-break-index="{{ $i }}">
            <th>休憩{{ $i === 0 ? '' : ($i + 1) }}</th>
            <td>
              <input type="time" name="break_start[]" class="break_start_input" value="{{ $breakStarts[$i] ?? '' }}">
            </td>
            <td>～</td>
            <td>
              <input type="time" name="break_end[]" class="break_end_input" value="{{ $breakEnds[$i] ?? '' }}">
            </td>
          </tr>
        @endfor
        <tr>
          <th>備考</th>
          <td colspan="3">
            <textarea name="note" id="note_input" rows="3">{{ old('note', $attendance->note ?? '') }}</textarea>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- 修正案表示エリア（初期は非表示） -->
    <div id="correction-display" style="display: none;">
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
            <td colspan="3" id="correction_name">{{ $userName ?? '-' }}</td>
          </tr>
          <tr>
            <th>日付</th>
            @php
              $dateObj = $workDate ? \Carbon\Carbon::parse($workDate) : null;
            @endphp
            <td id="correction_date_year">{{ $dateObj ? $dateObj->year . '年' : '-' }}</td>
            <td></td>
            <td id="correction_date_monthday">{{ $dateObj ? $dateObj->format('n月j日') : '-' }}</td>
          </tr>
          <tr>
            <th>出勤・退勤</th>
            <td id="correction_clock_in">-</td>
            <td>～</td>
            <td id="correction_clock_out">-</td>
          </tr>
          <tbody id="correction_breaks">
            <!-- 休憩時間は動的に追加 -->
          </tbody>
          <tr>
            <th>備考</th>
            <td colspan="3" id="correction_note">-</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="form-actions">
      <button type="button" id="correction-btn"
        data-attendance-id="{{ $attendance->id ?? '' }}"
        data-work-date="{{ $attendance->work_date ?? request('date') }}"
        @if(!empty($correctionRequest)) class="hidden" @endif>
        修正
      </button>
      <p id="pending-message" class="pending-message @if(empty($correctionRequest)) hidden @endif">
        @if(!empty($approvedRequest))
          ＊申請済みのため申請できません。
        @else
          ＊承認待ちのため修正はできません。
        @endif
      </p>
      <p id="error-message" class="error-message hidden"></p>
    </div>
  </form>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('user-attendance-correction-form');
    const correctionBtn = document.getElementById('correction-btn');
    const correctionDisplay = document.getElementById('correction-display');
    const clockInInput = document.getElementById('clock_in_input');
    const clockOutInput = document.getElementById('clock_out_input');
    const noteInput = document.getElementById('note_input');
    const breakRows = document.querySelectorAll('.break-row');

    // 休憩時間の動的追加
    function addBreakRowIfNeeded() {
      const breakRows = document.querySelectorAll('.break-row');
      let lastRowHasValue = false;
      
      // 最後の行に値があるかチェック
      if (breakRows.length > 0) {
        const lastRow = breakRows[breakRows.length - 1];
        const startInput = lastRow.querySelector('.break_start_input');
        const endInput = lastRow.querySelector('.break_end_input');
        if ((startInput && startInput.value) || (endInput && endInput.value)) {
          lastRowHasValue = true;
        }
      }

      // 最後の行に値がある場合、新しい行を追加
      if (lastRowHasValue) {
        const tbody = form.querySelector('tbody');
        const newIndex = breakRows.length;
        
        const newRow = document.createElement('tr');
        newRow.className = 'break-row';
        newRow.setAttribute('data-break-index', newIndex);
        newRow.innerHTML = `
          <th>休憩${newIndex + 1}</th>
          <td><input type="time" name="break_start[]" class="break_start_input" value=""></td>
          <td>～</td>
          <td><input type="time" name="break_end[]" class="break_end_input" value=""></td>
        `;
        
        // 備考行の前に挿入
        const noteRow = tbody.querySelector('tr:last-child');
        tbody.insertBefore(newRow, noteRow);
        
        // 新しい行の入力イベントを追加
        const newStartInput = newRow.querySelector('.break_start_input');
        const newEndInput = newRow.querySelector('.break_end_input');
        newStartInput.addEventListener('input', addBreakRowIfNeeded);
        newEndInput.addEventListener('input', addBreakRowIfNeeded);
      }
    }

    // 休憩時間入力のイベントリスナー
    breakRows.forEach(row => {
      const startInput = row.querySelector('.break_start_input');
      const endInput = row.querySelector('.break_end_input');
      if (startInput) startInput.addEventListener('input', addBreakRowIfNeeded);
      if (endInput) endInput.addEventListener('input', addBreakRowIfNeeded);
    });

    // 修正ボタンのクリックイベント
    if (correctionBtn && !correctionBtn.disabled) {
      correctionBtn.addEventListener('click', async function() {
        const formData = new FormData(form);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        // 修正案を表示エリアに設定
        document.getElementById('correction_clock_in').textContent = clockInInput.value || '-';
        document.getElementById('correction_clock_out').textContent = clockOutInput.value || '-';
        document.getElementById('correction_note').textContent = noteInput.value || '-';
        
        // 休憩時間を表示
        const breaksContainer = document.getElementById('correction_breaks');
        breaksContainer.innerHTML = '';
        const breakStarts = formData.getAll('break_start[]');
        const breakEnds = formData.getAll('break_end[]');
        
        for (let i = 0; i < Math.max(breakStarts.length, breakEnds.length); i++) {
          if (breakStarts[i] || breakEnds[i]) {
            const breakRow = document.createElement('tr');
            const startTime = breakStarts[i] || '00:00';
            const endTime = breakEnds[i] || '00:00';
            breakRow.innerHTML = `
              <th>休憩${i === 0 ? '' : (i + 1)}</th>
              <td>${startTime}</td>
              <td>～</td>
              <td>${endTime}</td>
            `;
            breaksContainer.appendChild(breakRow);
          }
        }

        try {
          this.disabled = true;
          this.textContent = '送信中...';
          
          const response = await fetch('/stamp_correction_request/list', {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            body: formData
          });
          
          let data;
          try {
            data = await response.json();
          } catch (jsonError) {
            // JSONパースエラーの場合、レスポンステキストを取得
            const text = await response.text();
            throw new Error('サーバーエラー: ' + (text || response.statusText));
          }
          
          if (response.ok) {
            // フォームを非表示、修正案を表示
            form.querySelector('table').style.display = 'none';
            correctionDisplay.style.display = 'block';
            
            // ボタンを非表示にして、メッセージを表示
            this.classList.add('hidden');
            const pendingMessage = document.getElementById('pending-message');
            if (pendingMessage) {
              pendingMessage.classList.remove('hidden');
            }
            
            if (data.message) {
              alert(data.message);
            }
          } else {
            // バリデーションエラーなどの場合
            const errorMessage = data.error || data.message || '修正申請に失敗しました';
            if (data.errors) {
              // バリデーションエラーの詳細を表示
              const errorDetails = Object.values(data.errors).flat().join('\n');
              throw new Error(errorMessage + '\n' + errorDetails);
            }
            throw new Error(errorMessage);
          }
        } catch (error) {
          console.error('Error:', error);
          const errorMessageEl = document.getElementById('error-message');
          errorMessageEl.textContent = error.message;
          errorMessageEl.classList.remove('hidden');
          this.disabled = false;
          this.textContent = '修正';
        }
      });
    }

    // 既に申請済みの場合は修正案を表示
    @if(!empty($correctionRequest))
      form.querySelector('table').style.display = 'none';
      correctionDisplay.style.display = 'block';
      
      @if(!empty($approvedRequest) && !empty($attendance))
        // 承認済みの場合は、attendancesテーブルの値を表示
        document.getElementById('correction_clock_in').textContent = '{{ $attendance && $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format("H:i") : "-" }}';
        document.getElementById('correction_clock_out').textContent = '{{ $attendance && $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format("H:i") : "-" }}';
        document.getElementById('correction_note').textContent = '{{ addslashes($attendance->note ?? "-") }}';
        
        // 休憩時間を表示（attendancesテーブルのbreak_timesを使用）
        const breaksContainer = document.getElementById('correction_breaks');
        @php
          $breakTimes = null;
          if (!empty($attendance->break_times)) {
            $breakTimes = json_decode($attendance->break_times, true);
          }
          $breakTimesHtml = '';
          if (!empty($breakTimes) && is_array($breakTimes)) {
            foreach ($breakTimes as $index => $breakTime) {
              $breakLabel = $index === 0 ? '休憩' : '休憩' . ($index + 1);
              $startTime = htmlspecialchars($breakTime['start'] ?? '00:00', ENT_QUOTES, 'UTF-8');
              $endTime = htmlspecialchars($breakTime['end'] ?? '00:00', ENT_QUOTES, 'UTF-8');
              $breakTimesHtml .= "<tr><th>{$breakLabel}</th><td>{$startTime}</td><td>～</td><td>{$endTime}</td></tr>";
            }
          } elseif ($attendance && $attendance->break_time) {
            $breakTimeMinutes = htmlspecialchars($attendance->break_time, ENT_QUOTES, 'UTF-8');
            $breakTimesHtml = "<tr><th>休憩</th><td colspan=\"3\">{$breakTimeMinutes}分</td></tr>";
          }
        @endphp
        breaksContainer.innerHTML = {!! json_encode($breakTimesHtml) !!};
      @else
        // 承認待ちの場合は、correctionRequestの値を表示
        document.getElementById('correction_clock_in').textContent = '{{ $correctionRequest->requested_clock_in ? \Carbon\Carbon::parse($correctionRequest->requested_clock_in)->format("H:i") : "-" }}';
        document.getElementById('correction_clock_out').textContent = '{{ $correctionRequest->requested_clock_out ? \Carbon\Carbon::parse($correctionRequest->requested_clock_out)->format("H:i") : "-" }}';
        document.getElementById('correction_note').textContent = '{{ addslashes($correctionRequest->reason ?? "-") }}';
        
        // 休憩時間を表示（個別の休憩時間があればそれを使用、なければ分単位の合計を表示）
        const breaksContainer = document.getElementById('correction_breaks');
        @php
          $breakTimes = null;
          if (!empty($correctionRequest->requested_break_times)) {
            $breakTimes = json_decode($correctionRequest->requested_break_times, true);
          }
          $breakTimesHtml = '';
          if (!empty($breakTimes) && is_array($breakTimes)) {
            foreach ($breakTimes as $index => $breakTime) {
              $breakLabel = $index === 0 ? '休憩' : '休憩' . ($index + 1);
              $startTime = htmlspecialchars($breakTime['start'] ?? '00:00', ENT_QUOTES, 'UTF-8');
              $endTime = htmlspecialchars($breakTime['end'] ?? '00:00', ENT_QUOTES, 'UTF-8');
              $breakTimesHtml .= "<tr><th>{$breakLabel}</th><td>{$startTime}</td><td>～</td><td>{$endTime}</td></tr>";
            }
          } elseif ($correctionRequest->requested_break_time) {
            $breakTimeMinutes = htmlspecialchars($correctionRequest->requested_break_time, ENT_QUOTES, 'UTF-8');
            $breakTimesHtml = "<tr><th>休憩</th><td colspan=\"3\">{$breakTimeMinutes}分</td></tr>";
          }
        @endphp
        breaksContainer.innerHTML = {!! json_encode($breakTimesHtml) !!};
      @endif
    @endif
  });
  </script>
@endsection
