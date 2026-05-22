{{-- resources/views/admin/dss-recommendations/index.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'DSS Recommendations')

@section('content')
    <style>
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .recommendation-card { background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .recommendation-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .recommendation-icon { width: 45px; height: 45px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
        .icon-improve { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-optimize { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .icon-conflict { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .icon-balance { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .recommendation-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; }
        .recommendation-priority { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
        .priority-high { background: #fee2e2; color: #c83232; }
        .priority-medium { background: #fef3c7; color: #b8860b; }
        .priority-low { background: #dbeafe; color: #0c4a6e; }
        .recommendation-details { font-size: 0.875rem; color: #7a7a6e; line-height: 1.6; margin-bottom: 1rem; }
        .recommendation-stats { display: flex; gap: 2rem; margin: 1rem 0; padding: 1rem 0; border-top: 1px solid #e8dcc8; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; }
        .stat-item { display: flex; flex-direction: column; }
        .stat-label { color: #7a7a6e; margin-bottom: 0.25rem; }
        .stat-value { font-weight: 600; color: #2d3436; }
        .action-btn { padding: 0.5rem 1.2rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45, 122, 80, 0.3); }
        .btn-secondary { background: #f5f3ed; color: #2d3436; border: 1px solid #e8dcc8; }
        .btn-secondary:hover { background: white; border-color: #2d7a50; }
        .filter-bar { display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .filter-btn { padding: 0.5rem 1rem; border: 1px solid #e8dcc8; background: white; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
        .filter-btn.active { background: #2d7a50; color: white; border-color: #2d7a50; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Decision Support Recommendations</h1>
        </div>
        <div class="header-right">
            <button style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; font-size: 0.875rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="display: inline; margin-right: 0.5rem;"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Run Analysis
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <button class="filter-btn active">All Recommendations</button>
        <button class="filter-btn">High Priority</button>
        <button class="filter-btn">Optimization</button>
        <button class="filter-btn">Conflict Resolution</button>
        <button class="filter-btn">Load Balancing</button>
    </div>

    <!-- Recommendations -->
    <div class="recommendation-card">
        <div class="recommendation-header">
            <div style="display: flex; gap: 1rem; align-items: start; flex: 1;">
                <div class="recommendation-icon icon-conflict"></div>
                <div style="flex: 1;">
                    <h3 class="recommendation-title">Resolve Time Slot Conflict - Dr. Juan Dela Cruz</h3>
                    <span class="recommendation-priority priority-high">High Priority</span>
                </div>
            </div>
        </div>
        <div class="recommendation-details">
            Dr. Juan Dela Cruz is assigned to teach "Advanced Algorithms" (11:00 AM-1:00 PM) and "Data Structures" (12:30 PM-2:30 PM) on the same day. This creates a 1.5-hour overlap conflict that prevents both classes from being scheduled.
        </div>
        <div class="recommendation-stats">
            <div class="stat-item">
                <div class="stat-label">Affected Classes</div>
                <div class="stat-value">2</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Students Impacted</div>
                <div class="stat-value">87</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Suggested Action</div>
                <div class="stat-value">Reschedule one class</div>
            </div>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button class="action-btn btn-primary">Apply Recommendation</button>
            <button class="action-btn btn-secondary">View Details</button>
            <button class="action-btn btn-secondary">Dismiss</button>
        </div>
    </div>

    <div class="recommendation-card">
        <div class="recommendation-header">
            <div style="display: flex; gap: 1rem; align-items: start; flex: 1;">
                <div class="recommendation-icon icon-balance"></div>
                <div style="flex: 1;">
                    <h3 class="recommendation-title">Optimize Faculty Workload Distribution</h3>
                    <span class="recommendation-priority priority-medium">Medium Priority</span>
                </div>
            </div>
        </div>
        <div class="recommendation-details">
            The current load distribution shows that Computer Science faculty have an average of 24% higher teaching loads compared to other departments. Recommendations include reassigning 3 elective courses and adjusting class sizes for optimal utilization.
        </div>
        <div class="recommendation-stats">
            <div class="stat-item">
                <div class="stat-label">Department</div>
                <div class="stat-value">Computer Science</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Potential Relief</div>
                <div class="stat-value">18 units</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Affected Faculty</div>
                <div class="stat-value">7</div>
            </div>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button class="action-btn btn-primary">Apply Recommendation</button>
            <button class="action-btn btn-secondary">View Details</button>
            <button class="action-btn btn-secondary">Dismiss</button>
        </div>
    </div>

    <div class="recommendation-card">
        <div class="recommendation-header">
            <div style="display: flex; gap: 1rem; align-items: start; flex: 1;">
                <div class="recommendation-icon icon-optimize"></div>
                <div style="flex: 1;">
                    <h3 class="recommendation-title">Improve Room Utilization</h3>
                    <span class="recommendation-priority priority-low">Low Priority</span>
                </div>
            </div>
        </div>
        <div class="recommendation-details">
            Several classrooms are underutilized during peak hours. By consolidating some smaller classes and adjusting time slots, room utilization can be increased from 65% to 82% without compromising quality.
        </div>
        <div class="recommendation-stats">
            <div class="stat-item">
                <div class="stat-label">Current Utilization</div>
                <div class="stat-value">65%</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Potential Improvement</div>
                <div class="stat-value">+17%</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Rooms Affected</div>
                <div class="stat-value">12</div>
            </div>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button class="action-btn btn-primary">Apply Recommendation</button>
            <button class="action-btn btn-secondary">View Details</button>
            <button class="action-btn btn-secondary">Dismiss</button>
        </div>
    </div>

    <div class="recommendation-card">
        <div class="recommendation-header">
            <div style="display: flex; gap: 1rem; align-items: start; flex: 1;">
                <div class="recommendation-icon icon-improve"></div>
                <div style="flex: 1;">
                    <h3 class="recommendation-title">Balance Section Sizes</h3>
                    <span class="recommendation-priority priority-medium">Medium Priority</span>
                </div>
            </div>
        </div>
        <div class="recommendation-details">
            Several sections have uneven student distributions. Section A has 45 students while Section B has only 22 students for the same course. Rebalancing would improve learning outcomes and resource efficiency.
        </div>
        <div class="recommendation-stats">
            <div class="stat-item">
                <div class="stat-label">Affected Sections</div>
                <div class="stat-value">4</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Students to Rebalance</div>
                <div class="stat-value">23</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">Improvement Potential</div>
                <div class="stat-value">High</div>
            </div>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button class="action-btn btn-primary">Apply Recommendation</button>
            <button class="action-btn btn-secondary">View Details</button>
            <button class="action-btn btn-secondary">Dismiss</button>
        </div>
    </div>

@endsection
