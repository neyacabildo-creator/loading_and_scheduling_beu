{{-- resources/views/teacher/dashboard.blade.php --}}
@extends('layouts.teacher')

@section('title', 'Teacher Dashboard')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <button class="search-btn">
                <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <h1 class="page-title">Teacher Dashboard</h1>
        </div>
        <div class="header-right">
            <button class="header-btn">EN</button>
            <button class="header-btn" style="position: relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span class="notification-dot"></span>
            </button>
        </div>
    </div>

    <!-- Teacher Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">My Classes</p>
                    <p class="stat-value">{{ $myClasses ?? 5 }}</p>
                    <p class="stat-change">Active this semester</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Students</p>
                    <p class="stat-value">{{ $totalStudents ?? 156 }}</p>
                    <p class="stat-change">Across all classes</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Teaching Load</p>
                    <p class="stat-value">{{ $teachingLoad ?? 24 }}</p>
                    <p class="stat-change">Units this semester</p>
                </div>
                <div class="stat-icon warning">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Pending Tasks</p>
                    <p class="stat-value">{{ $pendingTasks ?? 3 }}</p>
                    <p class="stat-change">Need your attention</p>
                </div>
                <div class="stat-icon default">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Content Grid -->
    <div class="content-grid">
        <!-- My Class Schedules Table -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">My Class Schedules</h2>
            </div>
            <div class="table-tabs">
                <button class="tab-btn active">All 5</button>
                <button class="tab-btn">Morning 2</button>
                <button class="tab-btn">Afternoon 3</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grade/Section</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mySchedules ?? [] as $schedule)
                    @if($schedule instanceof App\Models\ClassSchedule)
                    @php
                        $scheduleId = $schedule->id ?? 1;
                        $gradeLevel = $schedule->grade_level ?? 'N/A';
                        $section = $schedule->section ?? 'N/A';
                        $subject = $schedule->subject ?? 'N/A';
                        $time = $schedule->time ?? 'N/A';
                        $room = $schedule->room ?? 'Room A';
                        $status = $schedule->status ?? 'active';
                        $statusDisplay = ucfirst($status);
                    @endphp
                    <tr>
                        <td><span class="id-badge">#SC{{ str_pad($scheduleId, 3, '0', STR_PAD_LEFT) }}</span></td>
                        <td>
                            <div class="grade-cell">
                                <div class="grade-avatar">G{{ $gradeLevel }}</div>
                                <div class="grade-info">
                                    <p class="grade-name">Grade {{ $gradeLevel }}</p>
                                    <p class="grade-section">{{ $section }}</p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $subject }}</td>
                        <td style="color: #6b7280; font-size: 0.875rem;">{{ $time }}</td>
                        <td>{{ $room }}</td>
                        <td><span class="badge badge-{{ $status }}">{{ $statusDisplay }}</span></td>
                        <td>
                            <button class="action-btn" title="Edit">✎</button>
                            <button class="action-btn" title="Delete">✕</button>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td><span class="id-badge">#SC001</span></td>
                        <td>
                            <div class="grade-cell">
                                <div class="grade-avatar">G5</div>
                                <div class="grade-info">
                                    <p class="grade-name">Grade 5</p>
                                    <p class="grade-section">Section A</p>
                                </div>
                            </div>
                        </td>
                        <td>English</td>
                        <td style="color: #6b7280; font-size: 0.875rem;">7:30 AM - 8:30 AM</td>
                        <td>Room 101</td>
                        <td><span class="badge badge-active">Active</span></td>
                        <td>
                            <button class="action-btn" title="Edit">✎</button>
                            <button class="action-btn" title="Delete">✕</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="id-badge">#SC002</span></td>
                        <td>
                            <div class="grade-cell">
                                <div class="grade-avatar">G6</div>
                                <div class="grade-info">
                                    <p class="grade-name">Grade 6</p>
                                    <p class="grade-section">Section B</p>
                                </div>
                            </div>
                        </td>
                        <td>Mathematics</td>
                        <td style="color: #6b7280; font-size: 0.875rem;">8:30 AM - 9:30 AM</td>
                        <td>Room 102</td>
                        <td><span class="badge badge-active">Active</span></td>
                        <td>
                            <button class="action-btn" title="Edit">✎</button>
                            <button class="action-btn" title="Delete">✕</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="id-badge">#SC003</span></td>
                        <td>
                            <div class="grade-cell">
                                <div class="grade-avatar">G6</div>
                                <div class="grade-info">
                                    <p class="grade-name">Grade 6</p>
                                    <p class="grade-section">Section C</p>
                                </div>
                            </div>
                        </td>
                        <td>Science</td>
                        <td style="color: #6b7280; font-size: 0.875rem;">9:45 AM - 10:45 AM</td>
                        <td>Room 103</td>
                        <td><span class="badge badge-active">Active</span></td>
                        <td>
                            <button class="action-btn" title="Edit">✎</button>
                            <button class="action-btn" title="Delete">✕</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="id-badge">#SC004</span></td>
                        <td>
                            <div class="grade-cell">
                                <div class="grade-avatar">G7</div>
                                <div class="grade-info">
                                    <p class="grade-name">Grade 7</p>
                                    <p class="grade-section">Section A</p>
                                </div>
                            </div>
                        </td>
                        <td>English</td>
                        <td style="color: #6b7280; font-size: 0.875rem;">1:00 PM - 2:00 PM</td>
                        <td>Room 104</td>
                        <td><span class="badge badge-pending">Pending</span></td>
                        <td>
                            <button class="action-btn" title="Edit">✎</button>
                            <button class="action-btn" title="Delete">✕</button>
                        </td>
                    </tr>
                    <tr>
                        <td><span class="id-badge">#SC005</span></td>
                        <td>
                            <div class="grade-cell">
                                <div class="grade-avatar">G7</div>
                                <div class="grade-info">
                                    <p class="grade-name">Grade 7</p>
                                    <p class="grade-section">Section B</p>
                                </div>
                            </div>
                        </td>
                        <td>Mathematics</td>
                        <td style="color: #6b7280; font-size: 0.875rem;">2:00 PM - 3:00 PM</td>
                        <td>Room 105</td>
                        <td><span class="badge badge-active">Active</span></td>
                        <td>
                            <button class="action-btn" title="Edit">✎</button>
                            <button class="action-btn" title="Delete">✕</button>
                        </td>
                    </tr>
                    @endforelse
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

        <!-- Attendance & Performance Card -->
        <div class="activity-card">
            <h3 class="activity-title">Class Performance</h3>
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <p style="font-weight: 500; color: #374151;">Grade 5 Section A</p>
                    <span style="background: #dbeafe; color: #1e40af; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">85% Attendance</span>
                </div>
                <div style="background: #f3f4f6; border-radius: 0.375rem; height: 6px; overflow: hidden;">
                    <div style="width: 85%; height: 100%; background: #3b82f6;"></div>
                </div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <p style="font-weight: 500; color: #374151;">Grade 6 Section B</p>
                    <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">78% Attendance</span>
                </div>
                <div style="background: #f3f4f6; border-radius: 0.375rem; height: 6px; overflow: hidden;">
                    <div style="width: 78%; height: 100%; background: #f59e0b;"></div>
                </div>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <p style="font-weight: 500; color: #374151;">Grade 6 Section C</p>
                    <span style="background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 0.375rem; font-size: 0.75rem; font-weight: 600;">92% Attendance</span>
                </div>
                <div style="background: #f3f4f6; border-radius: 0.375rem; height: 6px; overflow: hidden;">
                    <div style="width: 92%; height: 100%; background: #10b981;"></div>
                </div>
            </div>
            <div style="border-top: 1px solid #e5e7eb; padding-top: 1rem;">
                <h4 style="font-weight: 600; margin-bottom: 0.75rem; color: #374151; font-size: 0.875rem;">Quick Stats</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div style="padding: 0.75rem; background: #f0f9ff; border-radius: 0.375rem; border-left: 3px solid #0284c7;">
                        <p style="color: #0c4a6e; font-size: 0.875rem;">Avg. Grade</p>
                        <p style="color: #0284c7; font-weight: 600; font-size: 1rem; margin-top: 0.25rem;">85.5%</p>
                    </div>
                    <div style="padding: 0.75rem; background: #fef2f2; border-radius: 0.375rem; border-left: 3px solid #dc2626;">
                        <p style="color: #7c2d12; font-size: 0.875rem;">At-Risk Students</p>
                        <p style="color: #dc2626; font-weight: 600; font-size: 1rem; margin-top: 0.25rem;">3 students</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teaching Load Management Section -->
    <div style="margin-top: 2rem;">
        <div class="content-grid">
            <!-- Teaching Load Details -->
            <div class="table-card">
                <div class="table-header">
                    <h2 class="table-title">Teaching Load Summary</h2>
                </div>
                <div class="table-tabs">
                    <button class="tab-btn active">All 5</button>
                    <button class="tab-btn">This Semester</button>
                    <button class="tab-btn">History</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Grade/Section</th>
                            <th>Subject</th>
                            <th>Students</th>
                            <th>Units</th>
                            <th>Schedule</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="grade-cell">
                                    <div class="grade-avatar">G5</div>
                                    <div class="grade-info">
                                        <p class="grade-name">Grade 5 Section A</p>
                                        <p class="grade-section">Elementary</p>
                                    </div>
                                </div>
                            </td>
                            <td>English</td>
                            <td>35</td>
                            <td>5 units</td>
                            <td style="color: #6b7280; font-size: 0.875rem;">7:30 AM - 8:30 AM</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="action-btn" title="Edit">✎</button>
                                <button class="action-btn" title="Delete">✕</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="grade-cell">
                                    <div class="grade-avatar">G6</div>
                                    <div class="grade-info">
                                        <p class="grade-name">Grade 6 Section B</p>
                                        <p class="grade-section">Elementary</p>
                                    </div>
                                </div>
                            </td>
                            <td>Mathematics</td>
                            <td>38</td>
                            <td>5 units</td>
                            <td style="color: #6b7280; font-size: 0.875rem;">8:30 AM - 9:30 AM</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="action-btn" title="Edit">✎</button>
                                <button class="action-btn" title="Delete">✕</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="grade-cell">
                                    <div class="grade-avatar">G6</div>
                                    <div class="grade-info">
                                        <p class="grade-name">Grade 6 Section C</p>
                                        <p class="grade-section">Elementary</p>
                                    </div>
                                </div>
                            </td>
                            <td>Science</td>
                            <td>36</td>
                            <td>5 units</td>
                            <td style="color: #6b7280; font-size: 0.875rem;">9:45 AM - 10:45 AM</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="action-btn" title="Edit">✎</button>
                                <button class="action-btn" title="Delete">✕</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="grade-cell">
                                    <div class="grade-avatar">G7</div>
                                    <div class="grade-info">
                                        <p class="grade-name">Grade 7 Section A</p>
                                        <p class="grade-section">Junior High</p>
                                    </div>
                                </div>
                            </td>
                            <td>English</td>
                            <td>40</td>
                            <td>4 units</td>
                            <td style="color: #6b7280; font-size: 0.875rem;">1:00 PM - 2:00 PM</td>
                            <td><span class="badge badge-pending">Pending</span></td>
                            <td>
                                <button class="action-btn" title="Edit">✎</button>
                                <button class="action-btn" title="Delete">✕</button>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="grade-cell">
                                    <div class="grade-avatar">G7</div>
                                    <div class="grade-info">
                                        <p class="grade-name">Grade 7 Section B</p>
                                        <p class="grade-section">Junior High</p>
                                    </div>
                                </div>
                            </td>
                            <td>Mathematics</td>
                            <td>38</td>
                            <td>5 units</td>
                            <td style="color: #6b7280; font-size: 0.875rem;">2:00 PM - 3:00 PM</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <button class="action-btn" title="Edit">✎</button>
                                <button class="action-btn" title="Delete">✕</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div style="padding: 1rem; background: #f9fafb; border-top: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-weight: 500; color: #374151;">Total Teaching Load: <span style="font-size: 1.25rem; color: #059669;">24 units</span></div>
                    <button style="padding: 0.5rem 1rem; background: #3b82f6; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 500;">Submit Load</button>
                </div>
            </div>

            <!-- Load Management Stats -->
            <div class="activity-card">
                <h3 class="activity-title">Load Management</h3>
                <div style="padding: 1rem 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb;">
                        <p style="color: #6b7280;">Total Units Assigned</p>
                        <p style="font-size: 1.25rem; font-weight: 600; color: #059669;">24 / 30</p>
                    </div>
                    <div style="margin: 1rem 0; background: #f3f4f6; border-radius: 0.375rem; height: 8px; overflow: hidden;">
                        <div style="width: 80%; height: 100%; background: #059669;"></div>
                    </div>
                </div>
                <div style="padding: 1rem; background: #eff6ff; border-radius: 0.375rem; border-left: 4px solid #3b82f6; margin: 1rem 0;">
                    <p style="font-weight: 500; color: #1e40af;">Load Status</p>
                    <p style="color: #1e3a8a; font-size: 0.875rem; margin-top: 0.25rem;">You have been assigned 24 units. The maximum is 30 units per semester.</p>
                </div>
                <div style="padding: 1rem; background: #f0fdf4; border-radius: 0.375rem; border-left: 4px solid #059669;">
                    <p style="font-weight: 500; color: #15803d;">Availability</p>
                    <p style="color: #166534; font-size: 0.875rem; margin-top: 0.25rem;">You have 6 units remaining. You can accept more classes if needed.</p>
                </div>
                <div style="margin-top: 1.5rem;">
                    <h4 style="font-weight: 600; margin-bottom: 0.75rem; color: #374151;">Recent Load Changes</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                            <p style="font-size: 0.875rem; color: #6b7280;">Grade 7 Section A added - 4 units</p>
                            <p style="font-size: 0.75rem; color: #9ca3af;">2 days ago</p>
                        </div>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem; margin-bottom: 0.5rem;">
                            <p style="font-size: 0.875rem; color: #6b7280;">Grade 5 Section A updated - 5 units</p>
                            <p style="font-size: 0.75rem; color: #9ca3af;">1 week ago</p>
                        </div>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0.375rem;">
                            <p style="font-size: 0.875rem; color: #6b7280;">Initial load assignment - 15 units</p>
                            <p style="font-size: 0.75rem; color: #9ca3af;">1 month ago</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
