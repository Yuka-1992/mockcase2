<header class="site-header">
  <div class="header-left">
    <a href="{{ auth()->check() ? (auth()->user()->role === 'admin' ? route('admin.attendance.index') : route('attendance.index')) : route('login') }}">
      <img src="{{ asset('img/logo.png') }}" alt="Logo" class="header-logo">
    </a>
  </div>
  <nav class="header-nav">
    @auth
      @if(Auth::user()->role === 'admin')
        <a href="{{ route('admin.attendance.index') }}">勤怠一覧</a>
        <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
        <a href="{{ route('admin.attendance.request.index') }}">申請一覧</a>
        <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
          @csrf
          <button type="submit" class="btn-logout">ログアウト</button>
        </form>
      @elseif(Auth::user()->role === 'user')
        <a href="{{ route('attendance.index') }}">勤怠</a>
        <a href="{{ route('user.index') }}">勤怠一覧</a>
        <a href="{{ route('user.request.index') }}">申請</a>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
          @csrf
          <button type="submit" class="btn-logout">ログアウト</button>
        </form>
      @endif
    @endauth
  </nav>
</header>