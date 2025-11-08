@extends('layouts.app')
@section('title', '申請承認')
@push('styles')
  @vite('resources/css/show.css')
@endpush
@section('content')
  <h1>申請承認</h1>

  <div id="approval-container">
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
          <td>{{ $request->user_name ?? '' }}</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <th>日付</th>
          @php $d = isset($request->target_date) ? \Carbon\Carbon::parse($request->target_date) : now(); @endphp
          <td>{{ $d->year }}年</td>
          <td></td>
          <td>{{ $d->format('n月j日') }}</td>
        </tr>
        <tr>
          <th>出勤時間</th>
          <td>
            {{ $request->requested_clock_in ? \Carbon\Carbon::parse($request->requested_clock_in)->format('H:i') : '-' }}
          </td>
          <td>～</td>
          <td>
            {{ $request->requested_clock_out ? \Carbon\Carbon::parse($request->requested_clock_out)->format('H:i') : '-' }}
          </td>
        </tr>
        @if(isset($request->break_times) && is_array($request->break_times) && count($request->break_times) > 0)
          @foreach($request->break_times as $index => $break)
            @php $label = $index === 0 ? '休憩' : '休憩'.($index+1); @endphp
            <tr>
              <th>{{ $label }}</th>
              <td>{{ $break['start'] ?? '00:00' }}</td>
              <td>～</td>
              <td>{{ $break['end'] ?? '00:00' }}</td>
            </tr>
          @endforeach
        @elseif($request->requested_break_time)
          <tr>
            <th>休憩</th>
            <td colspan="3">{{ $request->requested_break_time }}分</td>
          </tr>
        @else
          <tr>
            <th>休憩</th>
            <td>-</td>
            <td>～</td>
            <td>-</td>
          </tr>
        @endif
        <tr>
          <th>申請理由</th>
          <td colspan="3">
            {{ $request->reason ?? '' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  
  <div class="form-actions">
    <button 
      type="button" 
      id="approve-btn" 
      data-request-id="{{ $request->id }}"
      @if($request->status === 'approved') disabled class="btn-approved" @endif
    >
      {{ $request->status === 'approved' ? '承認済み' : '承認する' }}
    </button>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const approveBtn = document.getElementById('approve-btn');
    if (!approveBtn) return;

    approveBtn.addEventListener('click', async function() {
      if (this.disabled) return;
      
      const requestId = this.dataset.requestId;
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      if (!confirm('この申請を承認しますか？')) return;
      
      try {
        this.disabled = true;
        this.textContent = '処理中...';
        
        const response = await fetch(`/stamp_correction_request/approve/${requestId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: JSON.stringify({})
        });
        
        let data;
        try {
          data = await response.json();
        } catch (jsonError) {
          const text = await response.text();
          throw new Error('サーバーエラー: ' + (text || response.statusText));
        }
        
        if (response.ok) {
          this.textContent = '承認済み';
          this.classList.add('btn-approved');
          this.disabled = true;
          if (data.message) {
            alert(data.message);
          } else {
            alert('申請を承認しました');
          }
        } else {
          const errorMessage = data.error || data.message || '承認処理に失敗しました';
          throw new Error(errorMessage);
        }
      } catch (error) {
        console.error('Error:', error);
        alert(error.message || '承認処理に失敗しました。もう一度お試しください。');
        this.disabled = false;
        this.textContent = '承認する';
      }
    });
  });
  </script>
@endsection