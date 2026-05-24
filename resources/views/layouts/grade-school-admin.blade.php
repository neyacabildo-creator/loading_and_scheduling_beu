{{-- resources/views/layouts/grade-school-admin.blade.php --}}
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'Dashboard') - SPUP Grade School</title>
    @include('partials.spup-favicon')
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

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); min-height: 100vh; color: var(--text-primary); transition: background-color 0.3s, color 0.3s; }
        
        /* Sidebar */
        .sidebar { width: 256px; background: var(--sidebar-bg); height: 100vh; position: fixed; left: 0; top: 0; display: flex; flex-direction: column; box-shadow: 2px 0 8px rgba(0,0,0,0.1); z-index: 1000; }
        .sidebar-header { padding: 1.5rem; border-bottom: 1px solid var(--white-transparent-1); display: flex; align-items: center; gap: 0.75rem; }
        .sidebar-logo { width: 45px; height: 45px; flex-shrink: 0; }
        .sidebar-logo img { width: 100%; height: 100%; object-fit: contain; }
        .sidebar-title { font-size: 1.25rem; font-weight: bold; color: white; }
        .sidebar-subtitle { font-size: 0.75rem; color: #f0c040; margin-top: 0.125rem; }
        .sidebar-nav { flex: 1; padding: 1.5rem 1rem; display: flex; flex-direction: column; gap: 0.5rem; overflow-y: auto; overflow-x: hidden; }
        .sidebar-nav::-webkit-scrollbar { width: 6px; }
        .sidebar-nav::-webkit-scrollbar-track { background: var(--white-transparent-1); }
        .sidebar-nav::-webkit-scrollbar-thumb { background: var(--white-transparent-2); border-radius: 3px; }
        .nav-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-radius: 0.5rem; color: var(--white-transparent-7); text-decoration: none; transition: all 0.2s; font-size: 0.875rem; }
        .nav-item:hover { background: var(--white-transparent-1); color: white; }
        .nav-item.active { background: var(--white-transparent-2); color: white; }
        .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid var(--white-transparent-1); }
        .user-card { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: var(--white-transparent-1); border-radius: 0.5rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; color: var(--green-primary); font-weight: 600; font-size: 0.875rem; }
        .user-info { flex: 1; }
        .user-name { font-size: 0.875rem; font-weight: 500; color: white; }
        .user-role { font-size: 0.75rem; color: #f0c040; }
        .copyright { font-size: 0.75rem; color: var(--white-transparent-5); margin-bottom: 0.5rem; line-height: 1.4; }
        .logout-btn { background: transparent; border: none; padding: 0.5rem; cursor: pointer; color: var(--white-transparent-7); transition: all 0.2s; }
        .logout-btn:hover { color: white; }

        /* Main Content */
        .main-content { margin-left: 256px; padding: 2rem; background: var(--bg-primary); min-height: 100vh; }
        
        /* Header */
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; background: var(--bg-secondary); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid var(--border-color); }
        .header-left { display: flex; align-items: center; gap: 1rem; }
        .search-btn { padding: 0.5rem; background: transparent; border: none; cursor: pointer; color: var(--text-secondary); border-radius: 0.5rem; transition: all 0.2s; }
        .search-btn:hover { background: var(--bg-primary); color: var(--text-primary); }
        .page-title { font-size: 1.875rem; font-weight: 700; color: var(--text-primary); }
        .header-right { display: flex; align-items: center; gap: 1rem; }
        .header-btn { padding: 0.5rem 1rem; background: transparent; border: none; cursor: pointer; color: var(--text-secondary); border-radius: 0.5rem; transition: all 0.2s; font-weight: 500; }
        .header-btn:hover { background: var(--bg-primary); color: var(--text-primary); }
        .theme-toggle-btn { padding: 0.5rem; background: var(--bg-tertiary); border: 2px solid var(--green-primary); color: var(--text-primary); border-radius: 0.5rem; cursor: pointer; transition: all 0.3s ease; font-weight: 500; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; width: 40px; height: 40px; min-width: 40px; }
        .theme-toggle-btn:hover { border-color: var(--green-primary); color: white; background: var(--green-primary); transform: scale(1.1); }
        .theme-toggle-btn svg { width: 22px; height: 22px; fill: var(--green-primary); }
        .theme-toggle-btn:hover svg { fill: white; }
        html[data-theme="dark"] .theme-toggle-btn { background: #3a3a3a; border-color: #4a9d6f; }
        html[data-theme="dark"] .theme-toggle-btn svg { fill: #f0d060; }
        html[data-theme="dark"] .theme-toggle-btn:hover { background: #4a9d6f; }
        html[data-theme="dark"] .theme-toggle-btn:hover svg { fill: white; }
        .notification-dot { position: absolute; top: 4px; right: 4px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); padding: 1.5rem; border-radius: 0.75rem; border: 1px solid rgba(234,179,8,0.2); box-shadow: var(--shadow-sm); transition: all 0.2s; }
        .stat-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: rgba(234,179,8,0.4); }
        .stat-card.success { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); border-color: rgba(234,179,8,0.2); }
        .stat-card.warning { background: linear-gradient(135deg, rgba(234,179,8,0.15) 0%, rgba(234,179,8,0.08) 100%); border-color: rgba(234,179,8,0.2); }
        .stat-value { font-size: 2rem; font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
        .stat-label { font-size: 0.875rem; color: var(--text-secondary); font-weight: 500; }
        .stat-change { font-size: 0.75rem; color: var(--green-primary); margin-top: 0.5rem; }
        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; }
        .stat-icon { padding: 0.75rem; border-radius: 0.75rem; }
        .stat-icon.success { background: rgba(45,122,80,0.15); color: var(--green-primary); }
        .stat-icon.warning { background: rgba(234,179,8,0.2); color: #b8860b; }
        .stat-icon.default { background: var(--bg-tertiary); color: var(--text-secondary); }

        /* Tabs */
        .tab-group { margin-bottom: 2rem; }
        .tab-btns { display: flex; gap: 1rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--border-color); flex-wrap: wrap; }
        .tab-btn { padding: 0.75rem 1.25rem; background: transparent; border: none; cursor: pointer; color: var(--text-secondary); font-size: 0.875rem; font-weight: 600; border-bottom: 3px solid transparent; transition: all 0.2s; margin-bottom: -2px; white-space: nowrap; }
        .tab-btn:hover { color: var(--text-primary); border-color: #d4cfc4; }
        .tab-btn.active { color: var(--green-primary); border-color: var(--green-primary); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Table Card */
        .table-card { background: var(--bg-secondary); padding: 2rem; border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); margin-bottom: 2rem; overflow-x: auto; }
        .table-card h3 { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th { background: var(--bg-primary); padding: 1rem; text-align: left; font-weight: 600; font-size: 0.875rem; color: var(--text-primary); border-bottom: 1px solid var(--border-color); }
        td { padding: 1rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; color: var(--text-tertiary); }
        tr:hover { background: var(--bg-tertiary); }
        tr:last-child td { border-bottom: none; }

        /* Badges */
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-pending  { background: rgba(234,179,8,0.15);  color: #b8860b; }
        .badge-active   { background: rgba(34,134,58,0.15);  color: #22863a; }
        .badge-completed{ background: rgba(59,130,246,0.15); color: #3b82f6; }
        .badge-rejected { background: rgba(200,50,50,0.15);  color: #c83232; }
        .badge-danger   { background: rgba(200,50,50,0.15);  color: #c83232; }
        html[data-theme="dark"] .badge-pending  { background: rgba(234,179,8,0.2);   color: #f0c040; }
        html[data-theme="dark"] .badge-active   { background: rgba(34,197,94,0.2);   color: #4ade80; }
        html[data-theme="dark"] .badge-completed{ background: rgba(96,165,250,0.2);  color: #93c5fd; }
        html[data-theme="dark"] .badge-rejected { background: rgba(239,68,68,0.2);   color: #f87171; }
        html[data-theme="dark"] .badge-danger   { background: rgba(239,68,68,0.2);   color: #f87171; }
        html[data-theme="dark"] .stat-card,
        html[data-theme="dark"] .stat-card.success,
        html[data-theme="dark"] .stat-card.warning { background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%); border-color: var(--border-color); }
        html[data-theme="dark"] .stat-card:hover { border-color: var(--green-primary); }

        /* ===== DARK MODE: Page-specific card / section overrides ===== */
        html[data-theme="dark"] .export-card,
        html[data-theme="dark"] .options-section,
        html[data-theme="dark"] .export-section,
        html[data-theme="dark"] .section-card,
        html[data-theme="dark"] .backup-info,
        html[data-theme="dark"] .table-card,
        html[data-theme="dark"] .report-card,
        html[data-theme="dark"] .simulation-section,
        html[data-theme="dark"] .results-section,
        html[data-theme="dark"] .result-card,
        html[data-theme="dark"] .controls-section,
        html[data-theme="dark"] .export-controls,
        html[data-theme="dark"] .settings-nav,
        html[data-theme="dark"] .settings-content,
        html[data-theme="dark"] .recommendation-card,
        html[data-theme="dark"] .rec-card,
        html[data-theme="dark"] .rec-solution,
        html[data-theme="dark"] .status-card,
        html[data-theme="dark"] .form-card,
        html[data-theme="dark"] .room-card {
            background: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        html[data-theme="dark"] .scenario-card,
        html[data-theme="dark"] .option-card {
            background: var(--bg-tertiary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        html[data-theme="dark"] .section-title,
        html[data-theme="dark"] .info-label,
        html[data-theme="dark"] .info-value,
        html[data-theme="dark"] .export-title,
        html[data-theme="dark"] .export-desc,
        html[data-theme="dark"] .options-title,
        html[data-theme="dark"] .option-label,
        html[data-theme="dark"] .form-label,
        html[data-theme="dark"] .rec-title,
        html[data-theme="dark"] .rec-issue,
        html[data-theme="dark"] .setting-label,
        html[data-theme="dark"] .settings-nav-item,
        html[data-theme="dark"] .scenario-title,
        html[data-theme="dark"] .scenario-desc { color: var(--text-primary) !important; }
        html[data-theme="dark"] .control-input,
        html[data-theme="dark"] .setting-input {
            background: var(--bg-tertiary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        html[data-theme="dark"] .btn-secondary,
        html[data-theme="dark"] .export-btn.secondary,
        html[data-theme="dark"] .submit-btns .secondary,
        html[data-theme="dark"] .rec-btn,
        html[data-theme="dark"] .filter-btn,
        html[data-theme="dark"] .action-btn-small {
            background: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        html[data-theme="dark"] th {
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }
        html[data-theme="dark"] .main-content [style*="background: white"],
        html[data-theme="dark"] .main-content [style*="background:white"] {
            background: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
        }
        html[data-theme="dark"] .main-content [style*="background: #f5f3ed"],
        html[data-theme="dark"] .main-content [style*="background:#f5f3ed"] { background: var(--bg-tertiary) !important; }
        html[data-theme="dark"] .main-content [style*="color: #2d3436"],
        html[data-theme="dark"] .main-content [style*="color:#2d3436"] { color: var(--text-primary) !important; }
        html[data-theme="dark"] .main-content [style*="color: #7a7a6e"],
        html[data-theme="dark"] .main-content [style*="color:#7a7a6e"] { color: var(--text-secondary) !important; }
        html[data-theme="dark"] .main-content [style*="border: 1px solid #e8dcc8"],
        html[data-theme="dark"] .main-content [style*="border:1px solid #e8dcc8"] { border-color: var(--border-color) !important; }
        /* ===================================================== */

        /* Action Buttons */
        .action-btn { padding: 0.5rem 1rem; border: 1px solid var(--border-color); background: var(--bg-secondary); border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 600; color: var(--text-primary); transition: all 0.2s; }
        .action-btn:hover { border-color: var(--green-primary); color: var(--green-primary); background: var(--bg-primary); }
        .action-btn-primary { border-color: var(--green-primary); background: var(--green-primary); color: white; }
        .action-btn-primary:hover { background: var(--green-dark); border-color: var(--green-dark); }
        .action-btn-danger { border-color: #c83232; background: #c83232; color: white; }
        .action-btn-danger:hover { background: #a01e1e; border-color: #a01e1e; }

        /* Pagination */
        .pagination { display: flex; align-items: center; justify-content: space-between; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color); }
        .page-info { font-size: 0.875rem; color: var(--text-secondary); }
        .page-nav { display: flex; gap: 0.5rem; }
        .page-link { padding: 0.5rem 0.75rem; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; transition: all 0.2s; color: var(--text-primary); }
        .page-link:hover { border-color: var(--green-primary); color: var(--green-primary); }
        .page-link.active { background: var(--green-primary); color: white; border-color: var(--green-primary); }
        .page-link.disabled { color: var(--text-secondary); cursor: not-allowed; background: var(--bg-primary); }

        /* Modal Styles */
        .modal { background: var(--bg-secondary); border: 1px solid var(--border-color); }
        .modal-header { background: var(--bg-primary); color: var(--text-primary); border-bottom: 1px solid var(--border-color); }
        .modal-footer button { background: var(--green-primary); color: white; }
        input, textarea, select { background: var(--bg-tertiary); color: var(--text-primary); border: 1px solid var(--border-color); }
        input:focus, textarea:focus, select:focus { background: var(--bg-secondary); color: var(--text-primary); border-color: var(--green-primary); }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-content { padding: 1.5rem; }
            table { font-size: 0.8rem; }
            th, td { padding: 0.75rem; }
        }

        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 1rem; }
            .stats-grid { grid-template-columns: 1fr; gap: 1rem; }
            .header { flex-direction: column; align-items: flex-start; gap: 1rem; padding: 1rem; }
            .header-right { flex-direction: column; width: 100%; gap: 0.5rem; }
            .page-title { font-size: 1.5rem; }
            .tab-btns { flex-wrap: wrap; }
            .tab-btn { padding: 0.5rem 1rem; font-size: 0.75rem; }
            table { font-size: 0.7rem; }
            th, td { padding: 0.5rem; }
            .stat-card { padding: 1rem; }
            .stat-value { font-size: 1.5rem; }
            .stat-label { font-size: 0.75rem; }
        }

        @media (max-width: 640px) {
            .main-content { padding: 0.75rem; }
            .header { padding: 0.75rem; gap: 0.5rem; }
            .page-title { font-size: 1.25rem; }
            table { font-size: 0.65rem; }
            th, td { padding: 0.4rem; }
            .stat-card { padding: 0.75rem; }
            .stat-value { font-size: 1.25rem; }
            .stat-label { font-size: 0.7rem; }
            .search-btn { padding: 0.25rem; }
            .header-btn { padding: 0.25rem; font-size: 0.75rem; }
        }
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
                <div class="sidebar-subtitle">Grade School Admin</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <!-- CORE FUNCTIONS -->
            <div style="font-size: 0.75rem; font-weight: 700; color: #f0c040; padding: 1rem 1rem 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">Core</div>
            
            <a href="{{ route('grade-school-admin.dashboard') }}" class="nav-item {{ request()->routeIs('grade-school-admin.dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span>Dashboard</span>
            </a>

            <!-- LOADING MANAGEMENT -->
            <div style="font-size: 0.75rem; font-weight: 700; color: #f0c040; padding: 1rem 1rem 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.5rem;">Loading</div>
            
            <a href="{{ route('grade-school-admin.faculty-loading') }}" class="nav-item {{ request()->routeIs('grade-school-admin.faculty-loading') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                <span>Faculty Loads</span>
            </a>



            <!-- SCHEDULING -->
            <div style="font-size: 0.75rem; font-weight: 700; color: #f0c040; padding: 1rem 1rem 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.5rem;">Scheduling</div>
            
            <a href="{{ route('grade-school-admin.schedule.create') }}" class="nav-item {{ request()->routeIs('grade-school-admin.schedule.create') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Create Schedules</span>
            </a>

            <a href="{{ route('grade-school-admin.class-schedule') }}" class="nav-item {{ request()->routeIs('grade-school-admin.class-schedule') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Class Schedule Management</span>
            </a>

            <a href="{{ route('grade-school-admin.monitoring-alerts') }}" class="nav-item {{ request()->routeIs('grade-school-admin.monitoring-alerts') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Monitoring &amp; Alerts</span>
            </a>

            <a href="{{ route('grade-school-admin.rooms-sections.index') }}" class="nav-item {{ request()->routeIs('grade-school-admin.rooms-sections*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span>Rooms &amp; Sections</span>
            </a>

            <a href="{{ route('grade-school-admin.shared-teacher-requests') }}" class="nav-item {{ request()->routeIs('grade-school-admin.shared-teacher-requests*', 'grade-school-admin.teacher-schedule-requests*', 'grade-school-admin.teacher-leave-requests*') ? 'active' : '' }}" style="position:relative;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                <span>All Requests</span>
                @php
                    try {
                        $stPendingGs = \Illuminate\Support\Facades\DB::connection('mysql_gs')
                            ->table('shared_teacher_requests')->where('status', 'pending')->count();
                        if (\Illuminate\Support\Facades\Schema::connection('mysql_gs')->hasTable('teacher_requests')) {
                            $stPendingGs += \Illuminate\Support\Facades\DB::connection('mysql_gs')
                                ->table('teacher_requests')->where('status', 'pending')->count();
                        }
                        if (\Illuminate\Support\Facades\Schema::connection('mysql_gs')->hasTable(\App\Support\TeacherLeaveRequestSupport::TABLE)) {
                            $stPendingGs += \Illuminate\Support\Facades\DB::connection('mysql_gs')
                                ->table(\App\Support\TeacherLeaveRequestSupport::TABLE)->where('status', 'pending')->count();
                        }
                    } catch (\Exception $e) {
                        $stPendingGs = 0;
                    }
                @endphp
                @if($stPendingGs > 0)
                    <span style="margin-left:auto;background:#ef4444;color:white;font-size:0.7rem;font-weight:700;border-radius:9999px;padding:1px 7px;line-height:1.5;">{{ $stPendingGs }}</span>
                @endif
            </a>

            <!-- EXPORT -->
            <div style="font-size: 0.75rem; font-weight: 700; color: #f0c040; padding: 1rem 1rem 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.5rem;">Export</div>

            <a href="{{ route('grade-school-admin.print-export') }}" class="nav-item {{ request()->routeIs('grade-school-admin.print-export') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Export Reports</span>
            </a>

            <!-- SYSTEM ADMINISTRATION -->
            <div style="font-size: 0.75rem; font-weight: 700; color: #f0c040; padding: 1rem 1rem 0.5rem; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.5rem;">Administration</div>
            
            <a href="{{ route('grade-school-admin.users.index') }}" class="nav-item {{ request()->routeIs('grade-school-admin.users*') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292m15 7.296a9 9 0 01-9 9 9.01 9.01 0 01-9-9 9 9 0 018.905-8.995m15.095 8.995a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"></path></svg>
                <span>User Accounts</span>
            </a>

            <a href="{{ route('grade-school-admin.system-logs') }}" class="nav-item {{ request()->routeIs('grade-school-admin.system-logs') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span>System Logs</span>
            </a>

            @php
                try {
                    $myPendingRequests = \App\Models\PermissionRequest::where('requester_id', Auth::id())->where('status','pending')->count();
                } catch (\Exception $e) {
                    $myPendingRequests = 0;
                }
            @endphp
            <a href="{{ route('grade-school-admin.permission-requests') }}" class="nav-item {{ request()->routeIs('grade-school-admin.permission-requests') ? 'active' : '' }}" style="position:relative;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span>Ask Principal</span>
                @if($myPendingRequests > 0)
                    <span style="margin-left:auto;background:#f0c040;color:#1a3a5c;font-size:0.7rem;font-weight:700;border-radius:9999px;padding:1px 7px;line-height:1.5;">{{ $myPendingRequests }}</span>
                @endif
            </a>

            <a href="{{ route('grade-school-admin.settings') }}" class="nav-item {{ request()->routeIs('grade-school-admin.settings') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span>System Settings</span>
            </a>

        </nav>
        <div class="sidebar-footer">
            <p class="copyright">© 2024 SPUP. All rights reserved.</p>
            <div class="user-card">
                <a href="{{ route('grade-school-admin.settings') }}" title="My Profile" style="text-decoration: none; flex-shrink: 0;">
                    <div class="user-avatar" style="cursor: pointer; overflow: hidden;">
                        @if(Auth::user()->profile_photo_path)
                            <img src="{{ Storage::url(Auth::user()->profile_photo_path) }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" alt="Profile Photo">
                        @else
                            {{ strtoupper(substr(Auth::user()->first_name ?? 'G', 0, 1) . substr(Auth::user()->last_name ?? 'S', 0, 1)) }}
                        @endif
                    </div>
                </a>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->first_name ?? '' }} {{ Auth::user()->last_name ?? '' }}</div>
                    <div class="user-role">{{ Auth::user()->role?->display_name ?? 'Grade School Admin' }}</div>
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

    <div id="admin-header-actions-source" hidden aria-hidden="true">
        @include('partials.admin-header-actions', ['portal' => 'grade_school'])
    </div>

    <script>
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-theme', savedTheme);

        const moonSvgFilled = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
        const sunSvgFilled = `<svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.364l.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707m12.02-2.02a7 7 0 11-9.9 9.9 7 7 0 019.9-9.9z"></path></svg>`;

        function updateThemeButton() {
            const isDark = html.getAttribute('data-theme') === 'dark';
            document.querySelectorAll('[data-theme-toggle]').forEach(function(btn) {
                btn.title = isDark ? 'Light mode' : 'Dark mode';
                btn.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
            });
            const toolbarIcon = document.getElementById('toolbarThemeIcon');
            if (toolbarIcon) toolbarIcon.innerHTML = isDark ? sunSvgFilled : moonSvgFilled;
        }

        function toggleTheme() {
            const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeButton();
        }

        function ensureHeaderRight(header) {
            let right = header.querySelector('.header-right');
            if (!right) {
                right = document.createElement('div');
                right.className = 'header-right';
                Array.from(header.children).forEach(function(child) {
                    if (!child.classList.contains('header-left')) {
                        right.appendChild(child);
                    }
                });
                header.appendChild(right);
            }
            return right;
        }

        function mountAdminHeaderActions() {
            const source = document.getElementById('admin-header-actions-source');
            const actions = source ? source.querySelector('.admin-header-actions') : null;
            if (!actions) return;

            let header = document.querySelector('.main-content .header')
                || document.querySelector('.main-content .pe-header')
                || document.querySelector('.header')
                || document.querySelector('.pe-header');
            if (!header || header.querySelector('.admin-header-actions')) {
                return;
            }
            if (header.classList.contains('pe-header')) {
                header.classList.remove('pe-header');
                header.classList.add('header');
            }
            const right = ensureHeaderRight(header);
            right.insertBefore(actions, right.firstChild);
        }

        function initAdminPortalChrome() {
            mountAdminHeaderActions();
            updateThemeButton();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAdminPortalChrome);
        } else {
            initAdminPortalChrome();
        }

        // Prevent sidebar auto-scroll on navigation click
        document.addEventListener('DOMContentLoaded', function() {
            const navItems = document.querySelectorAll('.nav-item');
            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    // Store current scroll position
                    const scrollPos = {
                        mainContent: document.querySelector('.main-content')?.scrollTop || 0,
                        sidebar: document.querySelector('.sidebar-nav')?.scrollTop || 0
                    };
                    localStorage.setItem('scrollPos', JSON.stringify(scrollPos));
                });
            });

            // Restore scroll position after page load
            window.addEventListener('load', function() {
                const scrollPos = JSON.parse(localStorage.getItem('scrollPos') || '{}');
                if (scrollPos.mainContent) {
                    const mainContent = document.querySelector('.main-content');
                    if (mainContent) {
                        mainContent.scrollTop = 0; // Start at top for new content
                    }
                }
                if (scrollPos.sidebar) {
                    const sidebar = document.querySelector('.sidebar-nav');
                    if (sidebar) {
                        // Scroll sidebar to maintain position
                        sidebar.scrollTop = scrollPos.sidebar;
                    }
                }
                localStorage.removeItem('scrollPos');
            });
        });

        // Prevent page scroll when clicking nav items
        document.addEventListener('click', function(e) {
            if (e.target.closest('.nav-item') || e.target.closest('.nav-item *')) {
                // Allow natural navigation but don't prevent default
                // The browser will handle the page navigation
            }
        });
    </script>

    <script src="{{ asset('js/admin-schedule-display.js') }}" defer></script>
    <script src="{{ asset('js/admin-schedule-delete.js') }}" defer></script>
    @include('partials.spup-responsive-script')
    @stack('scripts')

    {{-- Session heartbeat: keeps auth alive while the tab is open --}}
    <script>
        (function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) return;

            function sendHeartbeat() {
                fetch('/auth/heartbeat', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    keepalive: true,
                }).catch(function () {});
            }

            sendHeartbeat();
            setInterval(sendHeartbeat, 20000);

            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') sendHeartbeat();
            });
        })();
    </script>
    @include('partials.spup-toast')
</body>
</html>
