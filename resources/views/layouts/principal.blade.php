{{-- resources/views/layouts/principal.blade.php --}}
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'Principal') - SPUP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #f5f3ed;
            --bg-secondary: white;
            --bg-tertiary: #fafaf8;
            --text-primary: #2d3436;
            --text-secondary: #7a7a6e;
            --text-tertiary: #5a5a52;
            --border-color: #e8dcc8;
            /* Principal uses a deeper navy-green to distinguish from regular admin */
            --sidebar-bg: linear-gradient(180deg, #1a3a5c 0%, #0e2540 50%, #071628 100%);
            --accent: #1a3a5c;
            --accent-dark: #0e2540;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
            --white-transparent-1: rgba(255,255,255,0.1);
            --white-transparent-2: rgba(255,255,255,0.15);
            --white-transparent-5: rgba(255,255,255,0.5);
            --white-transparent-7: rgba(255,255,255,0.7);
        }
        html[data-theme="dark"] {
            --bg-primary: #1a1a1a; --bg-secondary: #2d2d2d; --bg-tertiary: #3a3a3a;
            --text-primary: #e0e0e0; --text-secondary: #b0b0b0; --text-tertiary: #a0a0a0;
            --border-color: #404040;
            --sidebar-bg: linear-gradient(180deg, #0e2540 0%, #071628 50%, #030e1a 100%);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3); --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
            --white-transparent-1: rgba(255,255,255,0.05); --white-transparent-2: rgba(255,255,255,0.1);
            --white-transparent-5: rgba(255,255,255,0.4); --white-transparent-7: rgba(255,255,255,0.6);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); min-height: 100vh; color: var(--text-primary); }
        /* Sidebar */
        .sidebar { width: 256px; background: var(--sidebar-bg); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.15); z-index: 1000; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--white-transparent-1); display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-logo { width: 45px; height: 45px; flex-shrink: 0; }
        .sidebar-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-title { font-size: 1.1rem; font-weight: 800; color: white; }
        .sidebar-subtitle { font-size: 0.7rem; color: #f0c040; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; margin-top: 0.15rem; }
        .sidebar-nav { flex: 1; padding: 1.5rem 1rem; display: flex; flex-direction: column; gap: 0.35rem; overflow-y: auto; scrollbar-width: none; -ms-overflow-style: none; }
        .sidebar-nav::-webkit-scrollbar { display: none; }
        .nav-section-label { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.4); padding: 0.85rem 1rem 0.4rem; text-transform: uppercase; letter-spacing: 0.06em; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1rem; border-radius: 0.5rem; color: var(--white-transparent-7); text-decoration: none; transition: all 0.2s; font-size: 0.875rem; }
        .nav-item:hover { background: var(--white-transparent-1); color: white; }
        .nav-item.active { background: rgba(240,192,64,0.2); color: #f0c040; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .nav-badge { background: #ef4444; color: white; font-size: 0.65rem; font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 9999px; margin-left: auto; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid var(--white-transparent-1); }
        .user-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--white-transparent-1); border-radius: 0.5rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #f0c040; display: flex; align-items: center; justify-content: center; color: #1a3a5c; font-weight: 700; font-size: 0.875rem; }
        .user-info { flex: 1; overflow: hidden; }
        .user-name { font-size: 0.875rem; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 0.7rem; color: #f0c040; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }
        /* Main */
        .main-content { margin-left: 256px; padding: 2rem; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; background: var(--bg-secondary); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .page-title { font-size: 1.75rem; font-weight: 700; color: var(--text-primary); }
        .page-subtitle { font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.1rem; }
        .header-right { display: flex; align-items: center; gap: 0.75rem; }
        .header-btn { padding: 0.5rem 1rem; background: transparent; border: none; cursor: pointer; color: var(--text-secondary); border-radius: 0.5rem; transition: all 0.2s; font-weight: 500; font-size: 0.875rem; }
        .header-btn:hover { background: var(--bg-primary); color: var(--text-primary); }
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-secondary); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); transition: all 0.2s; }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .stat-card.accent { border-left: 4px solid #f0c040; }
        .stat-card.warning { border-left: 4px solid #ef4444; }
        .stat-card.info { border-left: 4px solid #3b82f6; }
        .stat-card.success { border-left: 4px solid #22c55e; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--text-primary); }
        .stat-label { font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; margin-top: 0.3rem; }
        .stat-sub { font-size: 0.72rem; color: var(--text-secondary); margin-top: 0.4rem; }
        /* Card */
        .card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 1.5rem; }
        .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; }
        .card-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); }
        .card-body { padding: 1.5rem; }
        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { background: var(--bg-primary); padding: 0.875rem 1rem; text-align: left; font-weight: 600; font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-color); }
        td { padding: 0.875rem 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; color: var(--text-primary); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--bg-tertiary); }
        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; gap: 0.25rem; }
        .badge-pending   { background: rgba(234,179,8,0.15);  color: #a16207; }
        .badge-approved  { background: rgba(34,197,94,0.15);  color: #166534; }
        .badge-rejected  { background: rgba(239,68,68,0.15);  color: #991b1b; }
        .badge-cancelled { background: rgba(107,114,128,0.15); color: #374151; }
        .badge-active    { background: rgba(34,197,94,0.12);  color: #15803d; }
        .badge-inactive  { background: rgba(156,163,175,0.2); color: #6b7280; }
        .badge-principal     { background: rgba(26,58,92,0.12);  color: #1a3a5c; }
        .badge-admin     { background: rgba(79,70,229,0.12);  color: #4338ca; }
        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.5rem 1rem; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; transition: all 0.2s; border: 1px solid transparent; text-decoration: none; }
        .btn-primary   { background: #1a3a5c; color: white; border-color: #1a3a5c; }
        .btn-primary:hover { background: #0e2540; border-color: #0e2540; }
        .btn-success   { background: #22c55e; color: white; border-color: #22c55e; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger    { background: #ef4444; color: white; border-color: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline   { background: transparent; color: var(--text-primary); border-color: var(--border-color); }
        .btn-outline:hover { border-color: #1a3a5c; color: #1a3a5c; }
        .btn-sm { padding: 0.3rem 0.7rem; font-size: 0.8rem; }
        /* Alert */
        .alert { padding: 0.875rem 1rem; border-radius: 0.5rem; margin-bottom: 1.25rem; font-size: 0.875rem; }
        .alert-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #166534; }
        .alert-error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  color: #991b1b; }
        /* Form */
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.03em; }
        .form-control { width: 100%; padding: 0.6rem 0.875rem; border: 1px solid var(--border-color); border-radius: 0.375rem; font-size: 0.875rem; background: var(--bg-tertiary); color: var(--text-primary); transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #1a3a5c; background: var(--bg-secondary); }
        /* Responsive */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 999; }
        .sidebar-overlay.active { display: block; }
        .hamburger-btn { display: none; position: fixed; top: 1rem; left: 1rem; z-index: 1100; background: var(--accent); color: white; border: none; border-radius: .5rem; width: 42px; height: 42px; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.25); flex-shrink: 0; }
        @media (max-width: 1024px) {
            .main-content { padding: 1.5rem; }
            table { font-size: 0.82rem; }
            th, td { padding: 0.75rem; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.28s ease; z-index: 1000; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 1rem; padding-top: 4rem; }
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 1rem; }
            .header { flex-direction: column; align-items: flex-start; gap: .75rem; padding: 1rem; }
            .header-right { width: 100%; flex-wrap: wrap; }
            .page-title { font-size: 1.4rem; }
            .hamburger-btn { display: flex; }
        }
        @media (max-width: 640px) {
            .main-content { padding: 0.75rem; padding-top: 3.75rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .header { padding: 0.75rem; }
            .page-title { font-size: 1.2rem; }
            .stat-card { padding: 1rem; }
            .stat-value { font-size: 1.5rem; }
            th, td { padding: 0.5rem 0.6rem; font-size: 0.75rem; }
        }
        @yield('extra-styles')
    </style>
    @include('partials.spup-responsive-head')
</head>
<body>
    <!-- Mobile sidebar toggle -->
    <button class="hamburger-btn" id="sidebarToggle" aria-label="Toggle sidebar">
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="{{ asset('images/spup-seal.png') }}" alt="SPUP">
            </div>
            <div>
                <div class="sidebar-title">BEU — SPUP</div>
                <div class="sidebar-subtitle">Principal Portal</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Overview</span>
            <a href="{{ route('principal.dashboard') }}" class="nav-item {{ request()->routeIs('principal.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                Dashboard
            </a>

            <span class="nav-section-label">Requests</span>
            <a href="{{ route('principal.permission-requests') }}" class="nav-item {{ request()->routeIs('principal.permission-requests') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                Admin Requests
                @php
                    try {
                        $pendingCount = \App\Models\PermissionRequest::where('status','pending')->count();
                    } catch (\Exception $e) {
                        $pendingCount = 0;
                    }
                @endphp
                @if($pendingCount > 0)
                    <span class="nav-badge">{{ $pendingCount }}</span>
                @endif
            </a>
            <a href="{{ route('principal.schedule-approvals') }}" class="nav-item {{ request()->routeIs('principal.schedule-approvals') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Schedule Approvals
                @php
                    $pendingSchedCount = 0;
                    try { $pendingSchedCount += (int) \Illuminate\Support\Facades\DB::connection('mysql_jh')->table('class_schedules')->where('admin_approved', true)->where('principal_approved', false)->whereIn('status', ['active','approved'])->count(); } catch (\Exception $e) {}
                    try { $pendingSchedCount += (int) \Illuminate\Support\Facades\DB::connection('mysql_gs')->table('class_schedules')->where('admin_approved', true)->where('principal_approved', false)->whereIn('status', ['active','approved'])->count(); } catch (\Exception $e) {}
                @endphp
                @if($pendingSchedCount > 0)
                    <span class="nav-badge">{{ $pendingSchedCount }}</span>
                @endif
            </a>

            <span class="nav-section-label">Management</span>
            <a href="{{ route('principal.users') }}" class="nav-item {{ request()->routeIs('principal.users*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                All Users
            </a>

            <span class="nav-section-label">System</span>
            <a href="{{ route('principal.system-logs') }}" class="nav-item {{ request()->routeIs('principal.system-logs') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                System Logs
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()->first_name ?? Auth::user()->name ?? 'S', 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->first_name ?? Auth::user()->name }}</div>
                    <div class="user-role">{{ Auth::user()->role?->display_name ?? 'Principal' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background:transparent;border:none;cursor:pointer;color:rgba(255,255,255,0.6);padding:0.25rem;" title="Logout">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main-content">
        @yield('content')
    </main>

    @yield('scripts')
    <script>
        // Sidebar scroll persistence
        document.addEventListener('DOMContentLoaded', function() {
            var nav = document.querySelector('.sidebar-nav');
            if (!nav) return;
            var saved = sessionStorage.getItem('sa_sidebar_scroll');
            if (saved) nav.scrollTop = parseInt(saved, 10);
            nav.querySelectorAll('a').forEach(function(a) {
                a.addEventListener('click', function() {
                    sessionStorage.setItem('sa_sidebar_scroll', nav.scrollTop);
                });
            });
        });
        // Mobile hamburger toggle
        (function() {
            var btn     = document.getElementById('sidebarToggle');
            var sidebar = document.getElementById('mainSidebar');
            var overlay = document.getElementById('sidebarOverlay');
            function open()  { sidebar.classList.add('open');  overlay.classList.add('active');  }
            function close() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }
            if (btn)     btn.addEventListener('click', function() { sidebar.classList.contains('open') ? close() : open(); });
            if (overlay) overlay.addEventListener('click', close);
            // Close on nav link click (mobile UX)
            document.querySelectorAll('.sidebar-nav .nav-item').forEach(function(a) {
                a.addEventListener('click', function() { if (window.innerWidth <= 768) close(); });
            });
        })();
    </script>
    @include('partials.spup-toast')
    @include('partials.spup-responsive-script')
</body>
</html>
