<header class="header">
    <nav class="header-nav">
        <div class="nav-title">
            <img src="{{ asset('images/logo.png') }}" alt="ロゴ" class="logo">
        </div>
        <ul class="nav-list-group">
            @can('admin-only')
                <li class="nav-list-item">
                    <a href="{{ route('admin.index') }}" class="nav-link">勤怠一覧（管理者）</a>
                </li>
                <li class="nav-list-item">
                    <a href="{{ route('admin.staff.list') }}" class="nav-link">スタッフ一覧</a>
                </li>
                <li class="nav-list-item">
                    <a href="{{ route('attendance-requests.index') }}" class="nav-link">申請履歴</a>
                </li>
                
            @else
                <li class="nav-list-item">
                    <a href="{{ route('attendance.index') }}" class="nav-link">勤怠</a>
                </li>
                <li class="nav-list-item">
                    <a href="{{ route('attendance-records.index') }}" class="nav-link">今月の出勤一覧</a>
                </li>
                <li class="nav-list-item">
                    <a href="{{ route('attendance-records.index') }}" class="nav-link">勤怠一覧</a>
                </li>
                <li class="nav-list-item">
                    <a href="{{ route('attendance-requests.index') }}" class="nav-link">申請</a>
                </li>
                <li class="nav-list-item">
                    <a href="{{ route('attendance.report') }}" class="nav-link">レポート</a>
                </li>
            @endcan
            <li class="nav-list-item">
                @can('admin-only')
                    {{-- 管理者用ログアウトForm --}}
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="logout-button">ログアウト</button>
                    </form>
                @else
                    {{-- 一般ユーザー用ログアウトForm --}}
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="logout-button">ログアウト</button>
                    </form>
                @endcan
            </li>
        </ul>
    </nav>
</header>
