{{-- resources/views/layouts/grade-school-teacher.blade.php --}}
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>@yield('title', 'Dashboard') - SPUP GS Teacher Portal</title>
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
            --text-tertiary: #5a5a52;
            --border-color: #e8dcc8;
            --sidebar-bg: linear-gradient(180deg, #2d7a50 0%, #1a5336 50%, #0f3d26 100%);
            --green-primary: #2d7a50;
            --green-dark: #1a5336;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
            --white-transparent-1: rgba(255,255,255,0.1);
            --white-transparent-2: rgba(255,255,255,0.15);
            --white-transparent-5: rgba(255,255,255,0.5);
            --white-transparent-7: rgba(255,255,255,0.7);
        }

        html[data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --bg-tertiary: #3a3a3a;
            --text-primary: #e0e0e0;
            --text-secondary: #b0b0b0;
            --text-tertiary: #a0a0a0;
            --border-color: #404040;
            --sidebar-bg: linear-gradient(180deg, #1a5336 0%, #0f3d26 50%, #051f15 100%);
            --green-primary: #4a9d6f;
            --green-dark: #2d7a50;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.4);
            --white-transparent-1: rgba(255,255,255,0.05);
            --white-transparent-2: rgba(255,255,255,0.1);
            --white-transparent-5: rgba(255,255,255,0.4);
            --white-transparent-7: rgba(255,255,255,0.6);
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); min-height: 100vh; color: var(--text-primary); transition: background-color 0.3s, color 0.3s; }

        /* Sidebar */
        .sidebar { width: 256px; background: var(--sidebar-bg); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.1); z-index: 1000; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-logo { width: 45px; height: 45px; flex-shrink: 0; }
        .sidebar-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-title { font-size: 1.25rem; font-weight: bold; color: white; }
        .sidebar-subtitle { font-size: 0.75rem; color: #f0c040; margin-top: 0.125rem; }
        .nav-section { padding: 0.5rem 1rem 0.25rem; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #f0c040; }
        .sidebar-nav { flex: 1; padding: 1rem; display: flex; flex-direction: column; gap: 0.25rem; overflow-y: auto; }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; font-size: 0.875rem; }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: rgba(255,255,255,0.2); color: white; font-weight: 600; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; margin-bottom: 0.75rem; }
        .user-avatar { width: 38px; height: 38px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: #2d7a50; font-weight: 700; flex-shrink: 0; font-size: 0.875rem; }
        .user-name { font-size: 0.8rem; font-weight: 600; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 0.7rem; color: #f0c040; }
        .logout-btn { background: transparent; border: none; cursor: pointer; color: rgba(255,255,255,0.7); transition: color 0.2s; display: flex; align-items: center; justify-content: center; padding: 0.25rem; margin-left: auto; }
        .logout-btn:hover { color: white; }
        .copyright { font-size: 0.7rem; color: rgba(255,255,255,0.45); margin-bottom: 0.75rem; text-align: center; }

        /* Main Content */
        .main-content { margin-left: 256px; padding: 2rem; background: var(--bg-primary); min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; background: var(--bg-secondary); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border-color); }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .page-title { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }
        .header-right { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
        .search-btn { padding: 0.5rem; background: transparent; border: none; cursor: pointer; color: var(--text-secondary); border-radius: 0.5rem; transition: all 0.2s; }
        .search-btn:hover { background: var(--bg-primary); color: var(--text-primary); }
        .header-btn { padding: 0.5rem; background: transparent; border: none; cursor: pointer; color: #6b7280; border-radius: 0.5rem; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        .stat-card { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); padding: 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(234,179,8,0.2); box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.3s; }
        .stat-card:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .stat-card.success { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); border-color: rgba(234,179,8,0.2); }
        .stat-card.warning { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); border-color: rgba(234,179,8,0.2); }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.875rem; font-weight: bold; color: var(--text-primary); }
        .stat-change { font-size: 0.8rem; color: var(--green-primary); margin-top: 0.25rem; font-weight: 500; }
        .stat-icon { padding: 0.75rem; border-radius: 0.75rem; }
        .stat-icon.green { background: rgba(45,122,80,0.12); color: #2d7a50; }
        .stat-icon.yellow { background: rgba(234,179,8,0.15); color: #b8860b; }
        .stat-icon.blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .stat-icon.gray { background: #f3f4f6; color: #6b7280; }
        .stat-icon.success { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .stat-icon.warning { background: rgba(234,179,8,0.2); color: #b8860b; }
        .stat-icon.default { background: #f3f4f6; color: #6b7280; }

        /* Cards */
        .card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1.5rem; }
        .card-header { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); }
        .card-title { font-size: 1rem; font-weight: 600; color: var(--text-primary); }
        .card-body { padding: 1.5rem; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem 1rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); background: var(--bg-tertiary); border-bottom: 1px solid var(--border-color); }
        td { padding: 0.875rem 1rem; border-bottom: 1px solid var(--border-color); color: var(--text-primary); font-size: 0.875rem; vertical-align: middle; }
        tr:hover { background: var(--bg-tertiary); }
        tr:last-child td { border-bottom: none; }

        /* Badge */
        .badge { display: inline-block; padding: 0.2rem 0.65rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; }
        .badge-green { background: rgba(45,122,80,0.12); color: #2d7a50; }
        .badge-yellow { background: rgba(234,179,8,0.15); color: #b8860b; }
        .badge-red { background: rgba(239,68,68,0.1); color: #dc2626; }
        .badge-blue { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .badge-gray { background: #f3f4f6; color: #6b7280; }

        /* Buttons */
        .btn { padding: 0.5rem 1rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.8rem; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: #2d7a50; color: white; }
        .btn-primary:hover { background: #1a5c3a; }
        .btn-secondary { background: transparent; color: #2d7a50; border: 1px solid #2d7a50; }
        .btn-secondary:hover { background: #2d7a50; color: white; }
        .btn-danger { background: transparent; color: #dc2626; border: 1px solid #dc2626; }
        .btn-danger:hover { background: #dc2626; color: white; }

        /* Form */
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); margin-bottom: 0.4rem; }
        .form-control { width: 100%; padding: 0.65rem 0.875rem; border: 1px solid var(--border-color); border-radius: 0.375rem; font-size: 0.875rem; color: var(--text-primary); background: var(--bg-secondary); font-family: inherit; }
        .form-control:focus { outline: none; border-color: var(--green-primary); box-shadow: 0 0 0 3px rgba(45,122,80,0.1); }

        /* Theme Toggle Button */
        .theme-toggle-btn { padding: 0.5rem; background: var(--bg-tertiary); border: 2px solid var(--green-primary); color: var(--text-primary); border-radius: 0.5rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; min-width: 40px; }
        .theme-toggle-btn:hover { color: white; background: var(--green-primary); transform: scale(1.1); }
        .theme-toggle-btn svg,
        .theme-toggle-btn .teacher-theme-icon svg { width: 22px; height: 22px; fill: var(--green-primary); }
        .theme-toggle-btn:hover svg,
        .theme-toggle-btn:hover .teacher-theme-icon svg { fill: white; }
        .theme-toggle-btn .teacher-theme-icon { display: flex; align-items: center; justify-content: center; }
        html[data-theme="dark"] .theme-toggle-btn { background: #3a3a3a; border-color: #4a9d6f; }
        html[data-theme="dark"] .theme-toggle-btn svg { fill: #f0d060; }
        html[data-theme="dark"] .theme-toggle-btn:hover { background: #4a9d6f; }
        html[data-theme="dark"] .theme-toggle-btn:hover svg { fill: white; }

        /* Dark Mode Overrides */
        html[data-theme="dark"] .card,
        html[data-theme="dark"] .section-card,
        html[data-theme="dark"] .export-card,
        html[data-theme="dark"] .rec-card,
        html[data-theme="dark"] .form-card { background: var(--bg-secondary) !important; border-color: var(--border-color) !important; color: var(--text-primary) !important; }
        html[data-theme="dark"] .card-title { color: var(--text-primary) !important; }
        html[data-theme="dark"] th { background: var(--bg-primary) !important; color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] td { color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] tr:hover { background: var(--bg-tertiary) !important; }
        html[data-theme="dark"] .stat-card,
        html[data-theme="dark"] .stat-card.success,
        html[data-theme="dark"] .stat-card.warning { background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%); border-color: var(--border-color); }
        html[data-theme="dark"] .form-control { background: var(--bg-tertiary) !important; border-color: var(--border-color) !important; color: var(--text-primary) !important; }
        html[data-theme="dark"] .badge-green { background: rgba(74,157,111,0.2); color: #4a9d6f; }
        html[data-theme="dark"] .badge-yellow { background: rgba(234,179,8,0.2); color: #f0c040; }
        html[data-theme="dark"] .badge-red { background: rgba(239,68,68,0.2); color: #f87171; }
        html[data-theme="dark"] .badge-blue { background: rgba(96,165,250,0.2); color: #93c5fd; }
        html[data-theme="dark"] .badge-gray { background: var(--bg-tertiary); color: var(--text-secondary); }
        html[data-theme="dark"] .main-content [style*="background: white"],
        html[data-theme="dark"] .main-content [style*="background:white"] { background: var(--bg-secondary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] .main-content [style*="background: #f5f3ed"],
        html[data-theme="dark"] .main-content [style*="background:#f5f3ed"] { background: var(--bg-tertiary) !important; }
        html[data-theme="dark"] .main-content [style*="color: #2d3436"],
        html[data-theme="dark"] .main-content [style*="color:#2d3436"] { color: var(--text-primary) !important; }
        html[data-theme="dark"] .main-content [style*="color: #7a7a6e"],
        html[data-theme="dark"] .main-content [style*="color:#7a7a6e"] { color: var(--text-secondary) !important; }
        html[data-theme="dark"] .main-content [style*="border: 1px solid #e8dcc8"],
        html[data-theme="dark"] .main-content [style*="border:1px solid #e8dcc8"] { border-color: var(--border-color) !important; }
        html[data-theme="dark"] .main-content [style*="color: #1f2937"],
        html[data-theme="dark"] .main-content [style*="color:#1f2937"] { color: var(--text-primary) !important; }
        html[data-theme="dark"] .main-content [style*="color: #374151"],
        html[data-theme="dark"] .main-content [style*="color:#374151"] { color: var(--text-primary) !important; }
        html[data-theme="dark"] .main-content [style*="color: #6b7280"],
        html[data-theme="dark"] .main-content [style*="color:#6b7280"] { color: var(--text-secondary) !important; }
        html[data-theme="dark"] .main-content [style*="background: #f0f9ff"] { background: rgba(14,165,233,0.1) !important; }
        html[data-theme="dark"] .main-content [style*="background:#f0f9ff"] { background: rgba(14,165,233,0.1) !important; }
        html[data-theme="dark"] .main-content [style*="background: rgba(45,122,80"] { background: rgba(74,157,111,0.1) !important; }
        html[data-theme="dark"] .header-bar { background: linear-gradient(135deg, #1a5336 0%, #051f15 100%) !important; }
    </style>
    @include('partials.spup-responsive-head')
    @stack('styles')
</head>
<body>
    @include('partials.spup-responsive')
    <!-- Sidebar -->
    <aside class="sidebar" id="mainSidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="{{ asset('images/spup-seal.png') }}" alt="SPUP Logo">
            </div>
            <div>
                <div class="sidebar-title">BEU</div>
                <div class="sidebar-subtitle">Grade School Teacher</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Overview</div>
            <a href="{{ route('grade-school-teacher.dashboard') }}" class="nav-item {{ request()->routeIs('grade-school-teacher.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-section">My Schedule</div>
            <a href="{{ route('grade-school-teacher.class-schedule') }}" class="nav-item {{ request()->routeIs('grade-school-teacher.class-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>View Assigned Schedule</span>
            </a>
            <a href="{{ route('grade-school-teacher.review-schedule') }}" class="nav-item {{ request()->routeIs('grade-school-teacher.review-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span>Review Schedule</span>
            </a>

            <div class="nav-section">Actions</div>
            <a href="{{ route('grade-school-teacher.request-adjustments') }}" class="nav-item {{ request()->routeIs('grade-school-teacher.request-adjustments') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Request Adjustments</span>
            </a>
            <div class="nav-section">Reports</div>
            <a href="{{ route('grade-school-teacher.print-export') }}" class="nav-item {{ request()->routeIs('grade-school-teacher.print-export') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Export / Print Schedule</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <p class="copyright">© 2025 SPUP. All rights reserved.</p>
            <div class="user-card">
                <a href="{{ route('grade-school-teacher.settings') }}" title="My Profile" style="text-decoration:none;flex-shrink:0;">
                    <div class="user-avatar" style="cursor:pointer;overflow:hidden;">
                        @if(Auth::user()->profile_photo_path)
                            <img src="{{ Storage::url(Auth::user()->profile_photo_path) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Profile Photo">
                        @else
                            {{ strtoupper(substr(Auth::user()->name ?? Auth::user()->first_name ?? 'T', 0, 1)) }}
                        @endif
                    </div>
                </a>
                <div style="flex:1;min-width:0;">
                    <div class="user-name">{{ Auth::user()->name ?? (Auth::user()->first_name . ' ' . Auth::user()->last_name) }}</div>
                    <div class="user-role">GS Teacher</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" id="gs-logout-form">
                    @csrf
                    <button type="button" class="logout-btn" title="Logout" onclick="document.getElementById('gs-logout-form').querySelector('[name=_token]').value = document.querySelector('meta[name=csrf-token]').content; document.getElementById('gs-logout-form').submit();">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
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
            const currentTheme = html.getAttribute('data-theme');
            const isDark = currentTheme === 'dark';
            document.querySelectorAll('[data-theme-toggle]').forEach(function(btn) {
                btn.title = isDark ? 'Light mode' : 'Dark mode';
            });
            const bannerIcon = document.getElementById('bannerThemeIcon');
            if (bannerIcon) bannerIcon.innerHTML = isDark ? sunSvg : moonSvg;
            const toolbarIcon = document.getElementById('toolbarThemeIcon');
            if (toolbarIcon) toolbarIcon.innerHTML = isDark ? sunSvgFilled : moonSvgFilled;
        }

        function toggleTheme() {
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeButton();
        }

        function initSidebarScroll() {
            var nav = document.querySelector('.sidebar-nav');
            if (!nav) return;
            var saved = sessionStorage.getItem('gs_sidebar_scroll');
            if (saved) nav.scrollTop = parseInt(saved, 10);
            nav.querySelectorAll('a').forEach(function(a) {
                a.addEventListener('click', function() {
                    sessionStorage.setItem('gs_sidebar_scroll', nav.scrollTop);
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => { updateThemeButton(); initSidebarScroll(); });
        } else {
            updateThemeButton();
            initSidebarScroll();
        }
    </script>
    @include('partials.auth-session-heartbeat')
    @include('partials.spup-toast')
    @include('partials.spup-responsive-script')
    @stack('scripts')
</body>
</html>
