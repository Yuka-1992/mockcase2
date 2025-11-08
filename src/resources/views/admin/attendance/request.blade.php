@extends('layouts.app')

@push('styles')
  @vite(['resources/css/list.css'])
@endpush

@section('content')
<div class="request-container">
  <h1 class="page-title">申請一覧</h1>
  
  <div class="tab-container">
    <button class="tab-btn active" data-tab="pending">承認待ち</button>
    <button class="tab-btn" data-tab="approved">承認済み</button>
  </div>
  
  <hr class="divider">
  
  <div class="tab-content" id="pending-tab">
    <table class="list-table">
      <thead>
        <tr>
          <th>状態</th>
          <th>名前</th>
          <th>対象日時</th>
          <th>申請理由</th>
          <th>申請日時</th>
          <th>詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pendingRequests ?? [] as $request)
        <tr>
          <td>承認待ち</td>
          <td>{{ $request->user_name }}</td>
          <td>{{ $request->target_date }}</td>
          <td>{{ $request->reason }}</td>
          <td>{{ $request->created_at }}</td>
          <td>
            <a href="{{ route('admin.attendance.request.approve', ['id' => $request->id]) }}" class="detail-link">詳細</a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="empty-message">承認待ちの申請はありません</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  
  <div class="tab-content hidden" id="approved-tab">
    <table class="list-table">
      <thead>
        <tr>
          <th>状態</th>
          <th>名前</th>
          <th>対象日時</th>
          <th>申請理由</th>
          <th>申請日時</th>
          <th>詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse($approvedRequests ?? [] as $request)
        <tr>
          <td>承認済み</td>
          <td>{{ $request->user_name }}</td>
          <td>{{ $request->target_date }}</td>
          <td>{{ $request->reason }}</td>
          <td>{{ $request->created_at }}</td>
          <td>
            @if($request->user_id && $request->target_date)
              <a href="{{ route('admin.staff.show', ['id' => $request->user_id]) }}?date={{ $request->target_date }}" class="detail-link">詳細</a>
            @else
              -
            @endif
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="empty-message">承認済みの申請はありません</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    // Add active class to clicked button
    btn.classList.add('active');
    
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
    // Show selected tab content
    const tabId = btn.dataset.tab + '-tab';
    document.getElementById(tabId).classList.remove('hidden');
  });
});
</script>
@endsection