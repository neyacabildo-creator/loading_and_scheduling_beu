{{-- resources/views/layouts/teacher.blade.php --}}
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
    <title>@yield('title', 'Dashboard') - SPUP Teacher Portal</title>
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
        .sidebar-nav { flex: 1; padding: 1rem; display: flex; flex-direction: column; gap: 0.25rem; overflow-y: auto; overflow-x: hidden; }
        .sidebar-nav::-webkit-scrollbar { width: 6px; }
        .sidebar-nav::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; font-size: 0.875rem; }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: rgba(255,255,255,0.2); color: white; font-weight: 500; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; margin-bottom: 0.75rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-weight: 600; flex-shrink: 0; }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 0.875rem; font-weight: 500; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 0.75rem; color: #f0c040; }
        .logout-btn { background: transparent; border: none; padding: 0.5rem; cursor: pointer; color: rgba(255,255,255,0.7); transition: color 0.2s; display: flex; align-items: center; justify-content: center; }
        .logout-btn:hover { color: white; }
        .logout-form { display: flex; }

        /* Main Content */
        .main-content { margin-left: 256px; padding: 2rem; background: var(--bg-primary); min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; background: var(--bg-secondary); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border-color); }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .search-btn { padding: 0.5rem; background: transparent; border: none; cursor: pointer; color: var(--text-secondary); border-radius: 0.5rem; transition: all 0.2s; }
        .search-btn:hover { background: var(--bg-primary); color: var(--text-primary); }
        .page-title { font-size: 1.875rem; font-weight: 700; color: var(--text-primary); }
        .header-right { display: flex; align-items: center; gap: 0.75rem; }
        .header-btn { padding: 0.5rem; background: transparent; border: none; cursor: pointer; color: #6b7280; border-radius: 0.5rem; transition: all 0.2s; }
        .header-btn:hover { background: white; color: #1f2937; }
        .notification-dot { position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }
        .stat-card { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); padding: 1.25rem; border-radius: 0.75rem; border: 1px solid rgba(234,179,8,0.2); box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.3s; }
        .stat-card:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .stat-card.success { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); border-color: rgba(234,179,8,0.2); }
        .stat-card.warning { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); border-color: rgba(234,179,8,0.2); }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #7a7a6e; margin-bottom: 0.5rem; }
        .stat-value { font-size: 1.875rem; font-weight: bold; color: #2d3436; }
        .stat-change { font-size: 0.875rem; color: #2d7a50; margin-top: 0.25rem; font-weight: 500; }
        .stat-icon { padding: 0.75rem; border-radius: 0.75rem; }
        .stat-icon.success { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .stat-icon.warning { background: rgba(234,179,8,0.2); color: #b8860b; }
        .stat-icon.default { background: #f3f4f6; color: #6b7280; }

        /* Content Grid */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
        @media (max-width: 1200px) { .content-grid { grid-template-columns: 1fr; } }

        /* Schedule Table */
        .table-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
        .table-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; }
        .table-title { font-size: 1.25rem; font-weight: 600; color: #2d3436; }
        .table-tabs { display: flex; border-bottom: 1px solid #e8dcc8; }
        .tab-btn { flex: 1; padding: 0.75rem 1rem; font-size: 0.875rem; font-weight: 500; border: none; background: transparent; cursor: pointer; color: #7a7a6e; transition: all 0.2s; }
        .tab-btn:hover { color: #2d3436; background: rgba(45,122,80,0.05); }
        .tab-btn.active { background: rgba(45,122,80,0.1); color: #2d7a50; border-bottom: 2px solid #2d7a50; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem 1rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #7a7a6e; background: #fafaf8; border-bottom: 1px solid #e8dcc8; }
        td { padding: 1rem; border-bottom: 1px solid #e8dcc8; color: #2d3436; }
        tr:hover { background: #fafaf8; }
        tr:last-child td { border-bottom: none; }
        .id-badge { font-size: 0.875rem; font-weight: 500; color: #2d7a50; }
        .grade-cell { display: flex; align-items: center; gap: 0.75rem; }
        .grade-avatar { width: 32px; height: 32px; border-radius: 50%; background: rgba(45,122,80,0.15); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600; color: #2d7a50; }
        .grade-info p { margin: 0; }
        .grade-name { font-size: 0.875rem; font-weight: 500; color: #2d3436; }
        .grade-section { font-size: 0.75rem; color: #7a7a6e; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .badge-pending { background: rgba(234,179,8,0.2); color: #b8860b; }
        .badge-active { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .badge-completed { background: rgba(150,150,150,0.15); color: #6b6b6b; }
        .action-btn { background: transparent; border: none; color: #2d7a50; font-size: 1rem; cursor: pointer; padding: 0.5rem; transition: all 0.2s; border-radius: 0.375rem; }
        .action-btn:hover { background: rgba(45,122,80,0.1); color: #2d7a50; }
        .pagination { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1rem; border-top: 1px solid #e8dcc8; }
        .page-numbers { display: flex; gap: 0.25rem; }
        .page-btn { width: 32px; height: 32px; border: none; border-radius: 0.375rem; font-size: 0.875rem; font-weight: 500; cursor: pointer; background: transparent; color: #7a7a6e; transition: all 0.2s; }
        .page-btn:hover { background: rgba(45,122,80,0.1); }
        .page-btn.active { background: rgba(45,122,80,0.2); color: #2d7a50; }
        
        /* Hide scrollbar */
        .scrollable-content::-webkit-scrollbar { display: none; }
        .scrollable-content { -ms-overflow-style: none; scrollbar-width: none; }

        /* Recent Activity */
        .activity-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
        .activity-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; margin-bottom: 1rem; }
        .activity-item { display: flex; align-items: flex-start; gap: 1rem; padding: 0.75rem; border-radius: 0.5rem; cursor: pointer; transition: background 0.2s; }
        .activity-item:hover { background: rgba(45,122,80,0.05); }
        .activity-icon { padding: 0.5rem; background: rgba(45,122,80,0.1); border-radius: 0.5rem; color: #2d7a50; }
        .activity-content { flex: 1; }
        .activity-name { font-size: 0.875rem; font-weight: 500; color: #2d3436; }
        .activity-desc { font-size: 0.75rem; color: #7a7a6e; margin-top: 0.125rem; }
        .activity-meta { text-align: right; }
        .activity-time { font-size: 0.75rem; color: #7a7a6e; }
        .copyright { font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-bottom: 1rem; }

        /* Theme Toggle Button */
        .theme-toggle-btn { padding: 0.5rem; background: var(--bg-tertiary); border: 2px solid var(--green-primary); color: var(--text-primary); border-radius: 0.5rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; min-width: 40px; }
        .theme-toggle-btn:hover { color: white; background: var(--green-primary); transform: scale(1.1); }
        .theme-toggle-btn svg { width: 22px; height: 22px; fill: var(--green-primary); }
        .theme-toggle-btn:hover svg { fill: white; }
        html[data-theme="dark"] .theme-toggle-btn { background: #3a3a3a; border-color: #4a9d6f; }
        html[data-theme="dark"] .theme-toggle-btn svg { fill: #f0d060; }
        html[data-theme="dark"] .theme-toggle-btn:hover { background: #4a9d6f; }
        html[data-theme="dark"] .theme-toggle-btn:hover svg { fill: white; }

        /* Dark Mode Overrides */
        html[data-theme="dark"] .table-card,
        html[data-theme="dark"] .activity-card,
        html[data-theme="dark"] .section-card,
        html[data-theme="dark"] .rec-card,
        html[data-theme="dark"] .export-card,
        html[data-theme="dark"] .form-card { background: var(--bg-secondary) !important; border-color: var(--border-color) !important; color: var(--text-primary) !important; }
        html[data-theme="dark"] th { background: var(--bg-primary) !important; color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] td { color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] tr:hover { background: var(--bg-tertiary) !important; }
        html[data-theme="dark"] .stat-card,
        html[data-theme="dark"] .stat-card.success,
        html[data-theme="dark"] .stat-card.warning { background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%); border-color: var(--border-color); }
        html[data-theme="dark"] .badge-pending  { background: rgba(234,179,8,0.2);   color: #f0c040; }
        html[data-theme="dark"] .badge-active   { background: rgba(34,197,94,0.2);   color: #4ade80; }
        html[data-theme="dark"] .badge-completed{ background: rgba(96,165,250,0.2);  color: #93c5fd; }
        html[data-theme="dark"] .badge-rejected { background: rgba(239,68,68,0.2);   color: #f87171; }
        html[data-theme="dark"] .stat-value { color: var(--text-primary) !important; }
        html[data-theme="dark"] .stat-label { color: var(--text-secondary) !important; }
        html[data-theme="dark"] .table-title { color: var(--text-primary) !important; }
        html[data-theme="dark"] .activity-name { color: var(--text-primary) !important; }
        html[data-theme="dark"] .activity-desc,
        html[data-theme="dark"] .activity-time { color: var(--text-secondary) !important; }
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
                <div class="sidebar-subtitle">Teacher Portal</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Overview</div>
            <a href="{{ route('teacher.dashboard') }}" class="nav-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>

            <div class="nav-section">My Schedule</div>
            <a href="{{ route('teacher.class-schedule') }}" class="nav-item {{ request()->routeIs('teacher.class-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>View Assigned Schedule</span>
            </a>
            <a href="{{ route('teacher.review-schedule') }}" class="nav-item {{ request()->routeIs('teacher.review-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span>Review Schedule</span>
            </a>

            <div class="nav-section">My Workload</div>
            <a href="{{ route('teacher.faculty-loading') }}" class="nav-item {{ request()->routeIs('teacher.faculty-loading') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span>Workload Summary</span>
            </a>
            <a href="{{ route('teacher.workload-history-stl') }}" class="nav-item {{ request()->routeIs('teacher.workload-history-stl') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Workload History</span>
            </a>
            <a href="{{ route('teacher.loading-schedule') }}" class="nav-item {{ request()->routeIs('teacher.loading-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>Loading Schedule</span>
            </a>



            <div class="nav-section">Actions</div>
            <a href="{{ route('teacher.request-adjustments') }}" class="nav-item {{ request()->routeIs('teacher.request-adjustments', 'teacher.request-adjustments-stl') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                <span>Request Adjustments</span>
            </a>
            <a href="{{ route('teacher.feedback') }}" class="nav-item {{ request()->routeIs('teacher.feedback') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <span>Provide Feedback</span>
            </a>

            <div class="nav-section">Reports</div>
            <a href="{{ route('teacher.print-export') }}" class="nav-item {{ request()->routeIs('teacher.print-export') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Export / Print Schedule</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <p class="copyright">© 2025 SPUP. All rights reserved.</p>
            <div class="user-card">
                <a href="{{ route('teacher.settings') }}" title="My Profile" style="text-decoration:none;flex-shrink:0;">
                    <div class="user-avatar" style="cursor:pointer;overflow:hidden;">
                        @if(Auth::user()->profile_photo_path)
                            <img src="{{ Storage::url(Auth::user()->profile_photo_path) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Profile Photo">
                        @else
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        @endif
                    </div>
                </a>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-role">Teacher</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="logout-form" id="logout-form">
                    @csrf
                    <button type="button" class="logout-btn" title="Logout" onclick="document.getElementById('logout-form').querySelector('[name=_token]').value = document.querySelector('meta[name=csrf-token]').content; document.getElementById('logout-form').submit();">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div style="position:fixed;top:1rem;right:1.25rem;z-index:1100;">
            @include('partials.teacher-portal-notifications', ['notificationsApi' => '/api/teacher/notifications'])
        </div>
        @yield('content')
    </main>


    <script>
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);

        function initThemeToggle() {
            const headerRight = document.querySelector('.header-right');
            if (headerRight) {
                const themeBtn = document.createElement('button');
                themeBtn.className = 'theme-toggle-btn';
                themeBtn.id = 'themeToggle';
                const currentTheme = html.getAttribute('data-theme');
                if (currentTheme === 'dark') {
                    themeBtn.innerHTML = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.364l.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707m12.02-2.02a7 7 0 11-9.9 9.9 7 7 0 019.9-9.9z"></path></svg>`;
                    themeBtn.title = 'Light Mode';
                } else {
                    themeBtn.innerHTML = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
                    themeBtn.title = 'Dark Mode';
                }
                themeBtn.addEventListener('click', toggleTheme);
                headerRight.insertBefore(themeBtn, headerRight.firstChild);
            }
        }

        function updateThemeButton() {
            const currentTheme = html.getAttribute('data-theme');
            const themeBtn = document.getElementById('themeToggle');
            if (themeBtn) {
                if (currentTheme === 'dark') {
                    themeBtn.innerHTML = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.364l.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707m12.02-2.02a7 7 0 11-9.9 9.9 7 7 0 019.9-9.9z"></path></svg>`;
                    themeBtn.title = 'Light Mode';
                } else {
                    themeBtn.innerHTML = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
                    themeBtn.title = 'Dark Mode';
                }
            }
            // Banner button (dashboard)
            const bannerIcon = document.getElementById('bannerThemeIcon');
            const bannerLabel = document.getElementById('bannerThemeLabel');
            if (bannerIcon) {
                if (currentTheme === 'dark') {
                    bannerIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`;
                } else {
                    bannerIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;
                }
            }
            if (bannerLabel) {
                bannerLabel.textContent = currentTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
            }
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
            var saved = sessionStorage.getItem('jh_sidebar_scroll');
            if (saved) nav.scrollTop = parseInt(saved, 10);
            nav.querySelectorAll('a').forEach(function(a) {
                a.addEventListener('click', function() {
                    sessionStorage.setItem('jh_sidebar_scroll', nav.scrollTop);
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => { initThemeToggle(); updateThemeButton(); initSidebarScroll(); });
        } else {
            initThemeToggle();
            updateThemeButton();
            initSidebarScroll();
        }
    </script>
    @include('partials.spup-toast')
    @include('partials.spup-responsive-script')
    @stack('scripts')
</body>
</html>
