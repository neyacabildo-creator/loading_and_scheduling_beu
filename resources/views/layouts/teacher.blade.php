{{-- resources/views/layouts/teacher.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <title>@yield('title', 'Dashboard') - SPUP Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f3ed; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar { width: 256px; background: linear-gradient(180deg, #2d7a50 0%, #1a5336 50%, #0f3d26 100%); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.1); }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-logo { width: 45px; height: 45px; flex-shrink: 0; }
        .sidebar-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-title { font-size: 1.25rem; font-weight: bold; color: white; }
        .sidebar-subtitle { font-size: 0.75rem; color: rgba(255,255,255,0.7); margin-top: 0.125rem; }
        .sidebar-nav { flex: 1; padding: 1.5rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; overflow-y: auto; overflow-x: hidden; }
        .sidebar-nav::-webkit-scrollbar { width: 6px; }
        .sidebar-nav::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 3px; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; font-size: 0.875rem; }
        .nav-item:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-item.active { background: rgba(255,255,255,0.2); color: white; font-weight: 500; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; margin-bottom: 0.75rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: #0d9488; font-weight: 600; flex-shrink: 0; }
        .user-info { flex: 1; min-width: 0; }
        .user-name { font-size: 0.875rem; font-weight: 500; color: white; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-role { font-size: 0.75rem; color: rgba(255,255,255,0.7); }
        .logout-btn { background: transparent; border: none; padding: 0.5rem; cursor: pointer; color: rgba(255,255,255,0.7); transition: color 0.2s; display: flex; align-items: center; justify-content: center; }
        .logout-btn:hover { color: white; }
        .logout-form { display: flex; }

        /* Main Content */
        .main-content { margin-left: 256px; padding: 2rem; background: #f5f3ed; min-height: 100vh; }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .search-btn { padding: 0.75rem; background: white; border: 1px solid #e5e7eb; border-radius: 0.75rem; cursor: pointer; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s; }
        .search-btn:hover { border-color: #0d9488; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
        .page-title { font-size: 1.875rem; font-weight: bold; color: #1f2937; }
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
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
            <!-- Main Menu Items -->
            <a href="{{ route('teacher.dashboard') }}" class="nav-item {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>
            
            <!-- Classes Menu Items -->
            <a href="{{ route('teacher.class-schedule') }}" class="nav-item {{ request()->routeIs('teacher.class-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>My Schedule</span>
            </a>
            <a href="{{ route('teacher.my-classes') }}" class="nav-item {{ request()->routeIs('teacher.my-classes') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M11 10h.01M7 10h.01M4 20h16a2 2 0 002-2V4a2 2 0 00-2-2H4a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>My Classes</span>
            </a>
            <a href="{{ route('teacher.my-students') }}" class="nav-item {{ request()->routeIs('teacher.my-students') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.856-1.487M15 10h.01M11 10h.01M7 10h.01M4 20h16a2 2 0 002-2V4a2 2 0 00-2-2H4a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>My Students</span>
            </a>
            <a href="{{ route('teacher.class-performance') }}" class="nav-item {{ request()->routeIs('teacher.class-performance') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Class Performance</span>
            </a>
            
            <!-- Teaching Menu Items -->
            <a href="{{ route('teacher.faculty-loading') }}" class="nav-item {{ request()->routeIs('teacher.faculty-loading') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                <span>Teaching Load</span>
            </a>
            <a href="{{ route('teacher.print-export') }}" class="nav-item {{ request()->routeIs('teacher.print-export') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Print / Export</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <p class="copyright">© 2025 SPUP. All rights reserved.</p>
            <div class="user-card">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-role">Teacher</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button type="submit" class="logout-btn" title="Logout">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>


</body>
</html>
