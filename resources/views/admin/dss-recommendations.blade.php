{{-- resources/views/admin/dss-recommendations.blade.php --}}
@extends('layouts.admin')

@section('title', 'DSS Recommendations')

@section('content')
    <style>
        .rec-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .rec-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
        .rec-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .rec-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; }
        .rec-priority { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .priority-high { background: rgba(239,68,68,0.2); color: #991b1b; }
        .priority-medium { background: rgba(234,179,8,0.2); color: #b8860b; }
        .priority-low { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .rec-content { margin-bottom: 1rem; }
        .rec-issue { color: #2d3436; font-weight: 500; margin-bottom: 0.5rem; }
        .rec-description { color: #7a7a6e; font-size: 0.875rem; margin-bottom: 0.75rem; }
        .rec-solution { background: rgba(45,122,80,0.05); padding: 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; color: #2d3436; }
        .rec-actions { display: flex; gap: 0.5rem; }
        .rec-btn { flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #e8dcc8; background: white; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; transition: all 0.2s; }
        .rec-btn:hover { border-color: #2d7a50; color: #2d7a50; }
        .rec-btn.accept { background: #2d7a50; color: white; border-color: #2d7a50; }
        .rec-btn.accept:hover { background: #1a5336; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <button class="search-btn">
                <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <h1 class="page-title">DSS Recommendations</h1>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Recommendations</p>
                    <p class="stat-value">12</p>
                    <p class="stat-change">Based on schedule analysis</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <p class="stat-label">High Priority</p>
                    <p class="stat-value">4</p>
                    <p class="stat-change">Require immediate action</p>
                </div>
                <div class="stat-icon warning">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Implemented</p>
                    <p class="stat-value">7</p>
                    <p class="stat-change">Recommendations applied</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Pending Review</p>
                    <p class="stat-value">1</p>
                    <p class="stat-change">Awaiting decision</p>
                </div>
                <div class="stat-icon default">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Recommendations Grid -->
    <h2 style="font-size: 1.25rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem;">System Recommendations</h2>
    <div class="rec-grid">
        <!-- High Priority -->
        <div class="rec-card">
            <div class="rec-header">
                <div class="rec-title">Teacher Overload</div>
                <div class="rec-priority priority-high">High</div>
            </div>
            <div class="rec-content">
                <div class="rec-issue">Issue: Mr. Pedro Garcia has 6 classes assigned</div>
                <div class="rec-description">
                    Exceeds recommended maximum of 5 classes per teacher. May impact teaching quality and teacher well-being.
                </div>
                <div class="rec-solution">
                    <strong>Recommendation:</strong> Redistribute one class to another available teacher or hire additional faculty.
                </div>
            </div>
            <div class="rec-actions">
                <button class="rec-btn accept">Accept</button>
                <button class="rec-btn">Reject</button>
            </div>
        </div>

        <!-- Medium Priority -->
        <div class="rec-card">
            <div class="rec-header">
                <div class="rec-title">Class Balance</div>
                <div class="rec-priority priority-medium">Medium</div>
            </div>
            <div class="rec-content">
                <div class="rec-issue">Issue: Uneven class distribution across grades</div>
                <div class="rec-description">
                    Grade 7 has 35 students in one class vs 20 in another. Creates learning disparities.
                </div>
                <div class="rec-solution">
                    <strong>Recommendation:</strong> Rebalance students to ensure optimal class sizes (25-30 students).
                </div>
            </div>
            <div class="rec-actions">
                <button class="rec-btn accept">Accept</button>
                <button class="rec-btn">Reject</button>
            </div>
        </div>

        <!-- Low Priority -->
        <div class="rec-card">
            <div class="rec-header">
                <div class="rec-title">Room Utilization</div>
                <div class="rec-priority priority-low">Low</div>
            </div>
            <div class="rec-content">
                <div class="rec-issue">Issue: Room 104 underutilized (2 hours/day)</div>
                <div class="rec-description">
                    Specialized room available during peak hours. Consider assigning additional classes.
                </div>
                <div class="rec-solution">
                    <strong>Recommendation:</strong> Schedule more classes in Room 104 during available time slots.
                </div>
            </div>
            <div class="rec-actions">
                <button class="rec-btn accept">Accept</button>
                <button class="rec-btn">Reject</button>
            </div>
        </div>

        <!-- High Priority -->
        <div class="rec-card">
            <div class="rec-header">
                <div class="rec-title">Schedule Gap</div>
                <div class="rec-priority priority-high">High</div>
            </div>
            <div class="rec-content">
                <div class="rec-issue">Issue: Extended break between classes</div>
                <div class="rec-description">
                    Ms. Maria Santos has 3-hour gap between classes. Inefficient schedule arrangement.
                </div>
                <div class="rec-solution">
                    <strong>Recommendation:</strong> Reorganize schedule to minimize gaps and improve efficiency.
                </div>
            </div>
            <div class="rec-actions">
                <button class="rec-btn accept">Accept</button>
                <button class="rec-btn">Reject</button>
            </div>
        </div>

        <!-- Medium Priority -->
        <div class="rec-card">
            <div class="rec-header">
                <div class="rec-title">Facility Assignment</div>
                <div class="rec-priority priority-medium">Medium</div>
            </div>
            <div class="rec-content">
                <div class="rec-issue">Issue: Science class in regular classroom</div>
                <div class="rec-description">
                    Science Lab class should be assigned to specialized lab facility for optimal learning.
                </div>
                <div class="rec-solution">
                    <strong>Recommendation:</strong> Move Science classes to dedicated laboratory with equipment.
                </div>
            </div>
            <div class="rec-actions">
                <button class="rec-btn accept">Accept</button>
                <button class="rec-btn">Reject</button>
            </div>
        </div>

        <!-- Low Priority -->
        <div class="rec-card">
            <div class="rec-header">
                <div class="rec-title">Teacher Preference</div>
                <div class="rec-priority priority-low">Low</div>
            </div>
            <div class="rec-content">
                <div class="rec-issue">Issue: Schedule conflicts with teacher availability</div>
                <div class="rec-description">
                    One teacher prefers afternoon classes but assigned mostly morning slots.
                </div>
                <div class="rec-solution">
                    <strong>Recommendation:</strong> Adjust schedule to align with teacher preferences when possible.
                </div>
            </div>
            <div class="rec-actions">
                <button class="rec-btn accept">Accept</button>
                <button class="rec-btn">Reject</button>
            </div>
        </div>
    </div>

@endsection
