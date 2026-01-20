{{-- resources/views/layouts/admin.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - SPUP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f5f3ed; min-height: 100vh; color: #2d3436; }
        
        /* Sidebar */
        .sidebar { width: 256px; background: linear-gradient(180deg, #2d7a50 0%, #1a5336 50%, #0f3d26 100%); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.1); z-index: 1000; }
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
        .nav-item.active { background: rgba(255,255,255,0.15); color: white; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid rgba(255,255,255,0.1); }
        .user-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: rgba(255,255,255,0.1); border-radius: 0.5rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: #2d7a50; font-weight: 600; font-size: 0.875rem; }
        .user-info { flex: 1; }
        .user-name { font-size: 0.875rem; font-weight: 500; color: white; }
        .user-role { font-size: 0.75rem; color: rgba(255,255,255,0.7); }
        .copyright { font-size: 0.75rem; color: rgba(255,255,255,0.6); margin-bottom: 0.5rem; line-height: 1.4; }
        .logout-btn { background: transparent; border: none; padding: 0.5rem; cursor: pointer; color: rgba(255,255,255,0.7); transition: all 0.2s; }
        .logout-btn:hover { color: white; }

        /* Main Content */
        .main-content { margin-left: 256px; padding: 2rem; background: #f5f3ed; min-height: 100vh; }
        
        /* Header */
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .search-btn { padding: 0.5rem; background: transparent; border: none; cursor: pointer; color: #7a7a6e; border-radius: 0.5rem; transition: all 0.2s; }
        .search-btn:hover { background: #f5f3ed; color: #2d3436; }
        .page-title { font-size: 1.875rem; font-weight: 700; color: #2d3436; }
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .header-btn { padding: 0.5rem 1rem; background: transparent; border: none; cursor: pointer; color: #7a7a6e; border-radius: 0.5rem; transition: all 0.2s; font-weight: 500; }
        .header-btn:hover { background: #f5f3ed; color: #2d3436; }
        .notification-dot { position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); transition: all 0.2s; }
        .stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); transform: translateY(-2px); border-color: #2d7a50; }
        .stat-value { font-size: 2rem; font-weight: 700; color: #2d7a50; margin-bottom: 0.5rem; }
        .stat-label { font-size: 0.875rem; color: #7a7a6e; font-weight: 500; }
        .stat-change { font-size: 0.75rem; color: #2d7a50; margin-top: 0.5rem; }

        /* Tabs */
        .tab-group { margin-bottom: 2rem; }
        .tab-btns { display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e8dcc8; flex-wrap: wrap; }
        .tab-btn { padding: 0.75rem 1.25rem; background: transparent; border: none; cursor: pointer; color: #7a7a6e; font-size: 0.875rem; font-weight: 600; border-bottom: 3px solid transparent; transition: all 0.2s; margin-bottom: -2px; white-space: nowrap; }
        .tab-btn:hover { color: #2d3436; border-color: #d4cfc4; }
        .tab-btn.active { color: #2d7a50; border-color: #2d7a50; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Table Card */
        .table-card { background: white; padding: 2rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .table-card h3 { font-size: 1.125rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f5f3ed; padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: #2d3436; border-bottom: 1px solid #e8dcc8; }
        td { padding: 1rem; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; color: #5a5a52; }
        tr:hover { background: #fafaf8; }
        tr:last-child td { border-bottom: none; }

        /* Badges */
        .badge { display: inline-block; padding: 0.375rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #b8860b; }
        .badge-active { background: #dcfce7; color: #22863a; }
        .badge-completed { background: #dbeafe; color: #0c4a6e; }
        .badge-rejected { background: #fee2e2; color: #c83232; }

        /* Action Buttons */
        .action-btn { padding: 0.5rem 1rem; border: 1px solid #e8dcc8; background: white; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #2d3436; transition: all 0.2s; }
        .action-btn:hover { border-color: #2d7a50; color: #2d7a50; background: #f9fdf8; }
        .action-btn-primary { border-color: #2d7a50; background: #2d7a50; color: white; }
        .action-btn-primary:hover { background: #1a5336; border-color: #1a5336; }
        .action-btn-danger { border-color: #c83232; background: #c83232; color: white; }
        .action-btn-danger:hover { background: #a01e1e; border-color: #a01e1e; }

        /* Pagination */
        .pagination { display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e8dcc8; }
        .page-info { font-size: 0.875rem; color: #7a7a6e; }
        .page-nav { display: flex; gap: 0.5rem; }
        .page-link { padding: 0.5rem 0.75rem; background: white; border: 1px solid #e8dcc8; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; transition: all 0.2s; }
        .page-link:hover { border-color: #2d7a50; color: #2d7a50; }
        .page-link.active { background: #2d7a50; color: white; border-color: #2d7a50; }
        .page-link.disabled { color: #a8a8a0; cursor: not-allowed; background: #f5f3ed; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 0; transition: all 0.3s; }
            .sidebar.open { width: 256px; }
            .main-content { margin-left: 0; }
            .stats-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .header-right { flex-direction: column; width: 100%; gap: 1rem; }
            .tab-btns { flex-wrap: wrap; }
            table { font-size: 0.75rem; }
            th, td { padding: 0.75rem; }
        }
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
                <div class="sidebar-subtitle">Loading and Scheduling System</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>
            <a href="{{ url('/admin/class-schedule') }}" class="nav-item {{ request()->routeIs('admin.class-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Class Schedule</span>
            </a>
            <a href="{{ route('admin.schedule.create') }}" class="nav-item {{ request()->routeIs('admin.schedule.create') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Create Schedule</span>
            </a>
            <a href="{{ url('/admin/faculty-loading') }}" class="nav-item {{ request()->routeIs('admin.faculty-loading') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                <span>Faculty Loading</span>
            </a>
            <a href="{{ url('/admin/print-export') }}" class="nav-item {{ request()->routeIs('admin.print-export') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Print / Export</span>
            </a>
        </nav>
        <div class="sidebar-footer">
            <p class="copyright">© 2024 SPUP. All rights reserved.</p>
            <div class="user-card">
                <div class="user-avatar">{{ strtoupper(substr(Auth::user()->first_name, 0, 1) . substr(Auth::user()->last_name, 0, 1)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</div>
                    <div class="user-role">{{ Auth::user()->role?->display_name ?? 'Administrator' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
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
