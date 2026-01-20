{{-- resources/views/admin/faculty-loading.blade.php --}}
@extends('layouts.admin')

@section('title', 'Faculty Loading')

@section('content')
    <style>
        .filter-section { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .filter-input { padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem; min-width: 200px; }
        .filter-btn { padding: 0.75rem 1.5rem; background: #2d7a50; color: white; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; }
        .filter-btn:hover { background: #1a5336; }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 1000px) { .stats-row { grid-template-columns: repeat(2, 1fr); } }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <button class="search-btn">
                <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <h1 class="page-title">Faculty Loading</h1>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <input type="text" class="filter-input" placeholder="Search teacher name...">
        <select class="filter-input">
            <option value="">All Departments</option>
            <option value="english">English Department</option>
            <option value="math">Mathematics Department</option>
            <option value="science">Science Department</option>
            <option value="pe">Physical Education</option>
        </select>
        <select class="filter-input">
            <option value="">All Grade Levels</option>
            <option value="5">Grade 5</option>
            <option value="6">Grade 6</option>
            <option value="7">Grade 7</option>
            <option value="8">Grade 8</option>
        </select>
        <button class="filter-btn">Apply Filters</button>
    </div>

    <!-- Faculty Loading Stats -->
    <div class="stats-row">
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Faculty</p>
                    <p class="stat-value">{{ $totalFaculty ?? 32 }}</p>
                    <p class="stat-change">Active teachers</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Classes</p>
                    <p class="stat-value">{{ $totalClasses ?? 128 }}</p>
                    <p class="stat-change">Assigned classes</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Avg Load</p>
                    <p class="stat-value">{{ $avgLoad ?? '4.2' }}</p>
                    <p class="stat-change">Classes per teacher</p>
                </div>
                <div class="stat-icon warning">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Overloaded</p>
                    <p class="stat-value">{{ $overloaded ?? 3 }}</p>
                    <p class="stat-change">Teachers with 6+ classes</p>
                </div>
                <div class="stat-icon default">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 4v2m0-10a2 2 0 00-4 0v6a2 2 0 004 0v-6zm6-6v6a2 2 0 004 0v-6a2 2 0 00-4 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Faculty Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">Faculty Load Details</h2>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Teacher Name</th>
                    <th>Department</th>
                    <th>Classes Assigned</th>
                    <th>Load (Hours/Week)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Ms. Maria Santos</strong></td>
                    <td>English</td>
                    <td>4</td>
                    <td>16 hours</td>
                    <td><span class="badge badge-active">Normal</span></td>
                    <td>
                        <button class="action-btn" title="View Details">👁</button>
                        <button class="action-btn" title="Edit">✎</button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Mr. Juan Cruz</strong></td>
                    <td>Mathematics</td>
                    <td>5</td>
                    <td>20 hours</td>
                    <td><span class="badge badge-warning">High Load</span></td>
                    <td>
                        <button class="action-btn" title="View Details">👁</button>
                        <button class="action-btn" title="Edit">✎</button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Ms. Ana Reyes</strong></td>
                    <td>Science</td>
                    <td>3</td>
                    <td>12 hours</td>
                    <td><span class="badge badge-active">Normal</span></td>
                    <td>
                        <button class="action-btn" title="View Details">👁</button>
                        <button class="action-btn" title="Edit">✎</button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Mr. Pedro Garcia</strong></td>
                    <td>Filipino</td>
                    <td>6</td>
                    <td>24 hours</td>
                    <td><span class="badge badge-pending">Overloaded</span></td>
                    <td>
                        <button class="action-btn" title="View Details">👁</button>
                        <button class="action-btn" title="Edit">✎</button>
                    </td>
                </tr>
                <tr>
                    <td><strong>Ms. Rosa Fernandez</strong></td>
                    <td>Physical Education</td>
                    <td>4</td>
                    <td>16 hours</td>
                    <td><span class="badge badge-active">Normal</span></td>
                    <td>
                        <button class="action-btn" title="View Details">👁</button>
                        <button class="action-btn" title="Edit">✎</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="pagination">
            <div class="page-numbers">
                <button class="page-btn active">1</button>
                <button class="page-btn">2</button>
                <button class="page-btn">3</button>
            </div>
            <div>
                <button class="page-btn">‹</button>
                <button class="page-btn">›</button>
            </div>
        </div>
    </div>

    <style>
        .badge-warning { background: rgba(234,179,8,0.2); color: #b8860b; }
        .action-btn { background: transparent; border: none; color: #2d7a50; font-size: 1rem; cursor: pointer; padding: 0.5rem; transition: all 0.2s; border-radius: 0.375rem; }
        .action-btn:hover { background: rgba(45,122,80,0.1); }
    </style>

@endsection
