<header class="header">
    <nav class="header-nav">
        <div class="nav-title">
            <img src="{{ asset('images/logo.png') }}" alt="ロゴ" class="logo">
        </div>
        <ul class="nav-list-group">
            <li class="nav-list-item">勤怠</li>
            <li class="nav-list-item">勤怠一覧</li>
            <li class="nav-list-item">申請</li>
            <li class="nav-list-item"><a href="{{ route('register') }}">ユーザー登録</a></li>
            <li class="nav-list-item">
                <a href="{{ route('attendance-records.index') }}">出勤一覧</a>
            </li>
            <li class="nav-list-item">申請一覧</li>
            <li class="nav-list-item">レポート</li>
            <li class="nav-list-item">
                <form id="logout-form" action="{{ route('logout') }}" method="POST" >
                    @csrf
                    <button type="submit" class="logout-button">ログアウト</button>
                </form>
            </a></li>
        </ul>
    </nav>
</header>
