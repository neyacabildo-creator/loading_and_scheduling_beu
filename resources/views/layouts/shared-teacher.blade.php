{{-- resources/views/layouts/shared-teacher.blade.php --}}
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'Dashboard') - SPUP Shared Teacher Portal</title>
    @include('partials.spup-favicon')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg-primary: #f5f3ed;
            --bg-secondary: #ffffff;
            --bg-tertiary: #fafaf8;
            --text-primary: #2d3436;
            --text-secondary: #7a7a6e;
            --border-color: #e8dcc8;
            --sidebar-bg: linear-gradient(180deg, #1a5336 0%, #0f3d26 50%, #062114 100%);
            --green-primary: #2d7a50;
            --green-dark: #1a5336;
            --accent: #f0c040;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
        }
        html[data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-tertiary: #3a3a3a;
            --text-primary: #e0e0e0;
            --text-secondary: #b0b0b0;
            --border-color: #404040;
            --sidebar-bg: linear-gradient(180deg, #0f3d26 0%, #062114 100%);
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); min-height: 100vh; color: var(--text-primary); }
        /* Sidebar */
        .sidebar { width: 250px; background: var(--sidebar-bg); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.15); z-index: 1000; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-logo { width: 42px; height: 42px; flex-shrink: 0; }
        .sidebar-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-title { font-size: 1.1rem; font-weight: bold; color: white; }
        .sidebar-subtitle { font-size: 0.7rem; color: var(--accent); margin-top: 0.1rem; }
        .sidebar-nav { flex: 1; padding: 1.25rem 0.875rem; display: flex; flex-direction: column; gap: 0.3rem; overflow-y: auto; }
        .nav-section { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent); padding: 0.75rem 0.875rem 0.3rem; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.7rem 1rem; border-radius: 0.5rem; color: rgba(255,255,255,0.75); text-decoration: none; transition: all 0.2s; font-size: 0.875rem; }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: rgba(255,255,255,0.18); color: white; font-weight: 500; }
        .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; }
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-weight: 700; font-size: 0.8rem; flex-shrink: 0; }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 0.82rem; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 0.68rem; color: var(--accent); }
        .logout-btn { background: none; border: none; cursor: pointer; color: rgba(255,255,255,0.65); padding: 0.35rem; transition: color 0.2s; }
        .logout-btn:hover { color: white; }
        /* Badge */
        .nav-badge { margin-left: auto; background: #ef4444; color: white; font-size: 0.65rem; font-weight: 700; border-radius: 9999px; padding: 1px 6px; min-width: 18px; text-align: center; }
        /* Main */
        .main-content { margin-left: 250px; padding: 2rem; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        .header-left h1 { font-size: 1.6rem; font-weight: 700; color: var(--text-primary); }
        .header-right { display: flex; gap: 0.75rem; align-items: center; flex-shrink: 0; }
        .st-dash-hero-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        .st-dash-hero-text { flex: 1; min-width: 0; }
        .st-portal-header-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            flex-shrink: 0;
        }
        .theme-toggle-btn {
            padding: 0.5rem;
            background: var(--bg-secondary);
            border: 2px solid var(--green-primary);
            color: var(--text-primary);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-width: 40px;
            flex-shrink: 0;
        }
        .theme-toggle-btn:hover {
            color: white;
            background: var(--green-primary);
            transform: scale(1.05);
        }
        .theme-toggle-btn .teacher-theme-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
        }
        .theme-toggle-btn svg { width: 22px; height: 22px; }
        html[data-theme="dark"] .theme-toggle-btn {
            background: var(--bg-tertiary);
            border-color: #4a9d6f;
        }
        html[data-theme="dark"] .theme-toggle-btn:hover { background: #4a9d6f; }
        .st-portal-header-actions .tp-notif-wrap { vertical-align: middle; }
        .st-portal-header-actions .tp-notif-btn {
            width: 40px;
            height: 40px;
            min-width: 40px;
            padding: 0;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
        }
        .st-portal-header-actions .tp-notif-btn:hover {
            background: var(--bg-tertiary);
            border-color: var(--green-primary);
            color: var(--green-primary);
        }
        html[data-theme="dark"] .st-portal-header-actions .tp-notif-btn {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
        }
        html[data-theme="dark"] .card,
        html[data-theme="dark"] .stat-card,
        html[data-theme="dark"] .st-schedule-panel,
        html[data-theme="dark"] .st-dash-hero,
        html[data-theme="dark"] .st-req-hero { background: var(--bg-secondary) !important; border-color: var(--border-color) !important; color: var(--text-primary) !important; }
        html[data-theme="dark"] th { background: var(--bg-tertiary) !important; color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] td { color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] tr:hover td { background: var(--bg-tertiary) !important; }
        /* Cards */
        .card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; }
        .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); }
        .card-body { padding: 1.5rem; }
        /* Level badge */
        .badge-jh { background: rgba(59,130,246,0.1); color: #2563eb; border: 1px solid rgba(59,130,246,0.25); padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-gs { background: rgba(45,122,80,0.1); color: #2d7a50; border: 1px solid rgba(45,122,80,0.25); padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        /* Status */
        .status-pending  { background: rgba(245,158,11,0.1); color: #b45309; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; }
        .status-approved { background: rgba(45,122,80,0.1); color: #2d7a50; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; }
        .status-rejected { background: rgba(220,38,38,0.1); color: #dc2626; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; }
        /* Table */
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        th { padding: 0.75rem 1rem; background: var(--bg-tertiary); text-align: left; font-weight: 600; color: var(--text-secondary); border-bottom: 1px solid var(--border-color); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; }
        td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--bg-tertiary); }
        /* Flash */
        .flash-success { background: rgba(45,122,80,0.1); border: 1px solid rgba(45,122,80,0.3); color: #2d7a50; padding: 0.875rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; font-weight: 500; }
        .flash-error { background: rgba(220,38,38,0.08); border: 1px solid rgba(220,38,38,0.3); color: #dc2626; padding: 0.875rem 1.25rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        /* Stat cards */
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; margin-bottom: 2rem; }
        .stat-card { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; }
        .stat-label { font-size: 0.75rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--text-primary); line-height: 1; }
        .stat-sub { font-size: 0.75rem; color: var(--text-secondary); }
        /* Form */
        .form-field { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: var(--text-primary); margin-bottom: 0.4rem; }
        .form-input { width: 100%; padding: 0.65rem 0.875rem; border: 1px solid var(--border-color); border-radius: 0.5rem; font-size: 0.875rem; background: var(--bg-secondary); color: var(--text-primary); font-family: inherit; transition: border-color 0.2s; }
        .form-input:focus { outline: none; border-color: var(--green-primary); box-shadow: 0 0 0 3px rgba(45,122,80,0.1); }
        .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .btn-primary { padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; font-family: inherit; transition: opacity 0.2s; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-outline { padding: 0.65rem 1.5rem; background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; font-family: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: border-color 0.2s; }
        .btn-outline:hover { border-color: var(--green-primary); color: var(--green-primary); }
    </style>
    @include('partials.spup-responsive-head')
    @stack('styles')
</head>
<body>
    @include('partials.spup-responsive')
    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="{{ asset('images/spup-seal.png') }}" alt="SPUP Logo">
            </div>
            <div>
                <div class="sidebar-title">BEU</div>
                <div class="sidebar-subtitle">Shared Teacher Portal</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">My Portal</div>
            <a href="{{ route('shared-teacher.dashboard') }}" class="nav-item {{ request()->routeIs('shared-teacher.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-section">Schedules</div>
            <a href="{{ route('shared-teacher.requests') }}" class="nav-item {{ request()->routeIs('shared-teacher.requests') ? 'active' : '' }}" style="position:relative;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Schedule Requests</span>
                @php
                    try {
                        $stPending =
                            \Illuminate\Support\Facades\DB::connection('mysql_jh')
                                ->table('shared_teacher_requests')
                                ->where('faculty_id', Auth::id())->where('status','pending')->count()
                            + \Illuminate\Support\Facades\DB::connection('mysql_gs')
                                ->table('shared_teacher_requests')
                                ->where('faculty_id', Auth::id())->where('status','pending')->count();
                    } catch (\Exception $e) { $stPending = 0; }
                @endphp
                @if($stPending > 0)
                    <span class="nav-badge">{{ $stPending }}</span>
                @endif
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::user()->first_name ?? 'S', 0, 1) . substr(Auth::user()->last_name ?? 'T', 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                    <div class="user-role">Shared Teacher</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn" title="Logout">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main-content">
        @yield('content')
    </main>

    <script>
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);

        const moonSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
        const sunSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`;
        const sunSvgFilled = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.364l.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707m12.02-2.02a7 7 0 11-9.9 9.9 7 7 0 019.9-9.9z"></path></svg>`;
        const moonSvgFilled = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;

        function updateThemeButton() {
            const isDark = html.getAttribute('data-theme') === 'dark';
            document.querySelectorAll('[data-theme-toggle]').forEach(function(btn) {
                btn.title = isDark ? 'Light mode' : 'Dark mode';
                btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            });
            const toolbarIcon = document.getElementById('toolbarThemeIcon');
            if (toolbarIcon) toolbarIcon.innerHTML = isDark ? sunSvgFilled : moonSvgFilled;
            const bannerIcon = document.getElementById('bannerThemeIcon');
            if (bannerIcon) bannerIcon.innerHTML = isDark ? sunSvg : moonSvg;
        }

        function toggleTheme() {
            const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeButton();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', updateThemeButton);
        } else {
            updateThemeButton();
        }
    </script>
    @include('partials.auth-session-heartbeat')
    @include('partials.spup-toast')
    @include('partials.spup-responsive-script')
    @stack('scripts')
</body>
</html>
