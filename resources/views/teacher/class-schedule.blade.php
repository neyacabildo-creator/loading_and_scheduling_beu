{{-- resources/views/teacher/class-schedule.blade.php --}}
@extends('layouts.teacher')

@section('title', 'My Class Schedule')

@section('content')
    <style>
        .schedule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        
        /* Calendar Styles */
        .calendar-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; padding: 1.5rem; margin-bottom: 2rem; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .calendar-title { font-size: 1.25rem; font-weight: 600; color: #2d3436; }
        .month-nav { display: flex; gap: 0.5rem; }
        .month-btn { padding: 0.5rem 1rem; background: transparent; border: none; cursor: pointer; border-radius: 0.375rem; transition: all 0.2s; }
        .month-btn:hover { background: rgba(45,122,80,0.1); color: #2d7a50; }
        
        .weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
        .weekday { text-align: center; font-weight: 600; color: #7a7a6e; padding: 0.5rem; font-size: 0.875rem; }
        
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .calendar-day { padding: 1rem 0.5rem; border: 1px solid #e8dcc8; border-radius: 0.5rem; min-height: 100px; cursor: pointer; transition: all 0.2s; }
        .calendar-day:hover { background: rgba(45,122,80,0.05); border-color: #2d7a50; }
        .calendar-day.other-month { color: #ccc; background: #fafaf8; }
        .calendar-day.today { background: rgba(45,122,80,0.15); border-color: #2d7a50; }
        .day-number { font-weight: 600; color: #2d3436; margin-bottom: 0.5rem; font-size: 0.875rem; }
        
        .schedule-event { padding: 0.25rem 0.5rem; background: linear-gradient(135deg, rgba(45,122,80,0.8) 0%, rgba(45,122,80,0.6) 100%); color: white; border-radius: 0.25rem; font-size: 0.65rem; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .schedule-event.pending { background: linear-gradient(135deg, rgba(234,179,8,0.8) 0%, rgba(234,179,8,0.6) 100%); }
        .schedule-event.completed { background: linear-gradient(135deg, rgba(150,150,150,0.8) 0%, rgba(150,150,150,0.6) 100%); }
        
        /* Schedule List */
        .schedule-list { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
        .list-title { font-size: 1.25rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem; }
        .schedule-item-card { display: flex; align-items: center; justify-content: space-between; padding: 1rem; border: 1px solid #e8dcc8; border-radius: 0.5rem; margin-bottom: 1rem; transition: all 0.2s; }
        .schedule-item-card:hover { box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-color: #2d7a50; }
        .item-left { flex: 1; }
        .item-time { font-weight: 600; color: #2d7a50; font-size: 0.875rem; }
        .item-subject { font-size: 1rem; font-weight: 500; color: #2d3436; margin-top: 0.25rem; }
        .item-grade { font-size: 0.875rem; color: #7a7a6e; margin-top: 0.25rem; }
        .item-right { text-align: right; }
        .item-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; background: rgba(45,122,80,0.15); color: #2d7a50; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <button class="search-btn">
                <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <h1 class="page-title">My Class Schedule (Approved)</h1>
        </div>
    </div>

    <!-- Status Information -->
    <div style="background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
        <p style="color: #2d7a50; font-weight: 500; margin: 0;">
            ℹ️ This schedule displays only <strong>admin-approved classes</strong>. Any changes made by the administrator will be reflected here automatically.
        </p>
    </div>

    <!-- Calendar Section -->
    <div class="calendar-card">
        <div class="calendar-header">
            <h2 class="calendar-title">Schedule Calendar - January 2026</h2>
            <div class="month-nav">
                <button class="month-btn">‹ Previous</button>
                <button class="month-btn">Today</button>
                <button class="month-btn">Next ›</button>
            </div>
        </div>

        <!-- Weekdays Header -->
        <div class="weekdays">
            <div class="weekday">Sun</div>
            <div class="weekday">Mon</div>
            <div class="weekday">Tue</div>
            <div class="weekday">Wed</div>
            <div class="weekday">Thu</div>
            <div class="weekday">Fri</div>
            <div class="weekday">Sat</div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
            <!-- Previous month days -->
            <div class="calendar-day other-month">29</div>
            <div class="calendar-day other-month">30</div>
            <div class="calendar-day other-month">31</div>
            
            <!-- January days -->
            <div class="calendar-day">
                <div class="day-number">1</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">2</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">3</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">4</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">5</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">6</div>
                <div class="schedule-event">English - Room 101</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">7</div>
                <div class="schedule-event">Math - Room 102</div>
                <div class="schedule-event">Math Lab - Room 103</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">8</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">9</div>
                <div class="schedule-event">Science - Room 201</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">10</div>
            </div>
            <div class="calendar-day today">
                <div class="day-number">11</div>
                <div class="schedule-event">English - Room 101</div>
                <div class="schedule-event">English - Room 102</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">12</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">13</div>
                <div class="schedule-event">Science - Room 202</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">14</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">15</div>
                <div class="schedule-event pending">Pending Approval</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">16</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">17</div>
                <div class="schedule-event">Math - Room 102</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">18</div>
                <div class="schedule-event">English - Room 101</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">19</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">20</div>
                <div class="schedule-event">Science - Room 202</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">21</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">22</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">23</div>
                <div class="schedule-event">Math Lab - Room 103</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">24</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">25</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">26</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">27</div>
                <div class="schedule-event">English - Room 101</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">28</div>
                <div class="schedule-event">Math - Room 102</div>
                <div class="schedule-event completed">Completed</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">29</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">30</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">31</div>
            </div>
            
            <!-- Next month days -->
            <div class="calendar-day other-month">1</div>
            <div class="calendar-day other-month">2</div>
            <div class="calendar-day other-month">3</div>
        </div>
    </div>

    <!-- Today's Schedule Section -->
    <div class="schedule-list">
        <h2 class="list-title">My Approved Schedules</h2>
        
        <div id="scheduleContainer">
            <div style="text-align: center; padding: 2rem; color: #7a7a6e;">
                Loading your approved schedules...
            </div>
        </div>
    </div>

    <script>
        // Load teacher's approved schedules
        document.addEventListener('DOMContentLoaded', function() {
            loadApprovedSchedules();
            // Refresh every 30 seconds to catch admin changes
            setInterval(loadApprovedSchedules, 30000);
        });

        function loadApprovedSchedules() {
            // Get auth ID from meta tag or document
            const teacherId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '{{ auth()->id() }}');
            
            // Get current user's approved schedules
            fetch('/api/teacher/schedules?faculty_id=' + teacherId, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(schedules => {
                if (!Array.isArray(schedules)) {
                    schedules = schedules.data || [];
                }
                
                // Filter for only approved active schedules
                const approvedSchedules = schedules.filter(s => s.admin_approved && s.status === 'active');
                
                const container = document.getElementById('scheduleContainer');
                
                if (approvedSchedules.length === 0) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #7a7a6e;">
                            <p>No approved schedules yet.</p>
                            <p style="font-size: 0.875rem; margin-top: 0.5rem;">Check back later or contact your administrator.</p>
                        </div>
                    `;
                    return;
                }

                // Sort by day of week
                const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                approvedSchedules.sort((a, b) => {
                    const dayA = dayOrder.indexOf(a.day_of_week);
                    const dayB = dayOrder.indexOf(b.day_of_week);
                    if (dayA !== dayB) return dayA - dayB;
                    return a.start_time.localeCompare(b.start_time);
                });

                // Build schedule cards
                container.innerHTML = approvedSchedules.map(schedule => `
                    <div class="schedule-item-card">
                        <div class="item-left">
                            <div class="item-time">${schedule.start_time} - ${schedule.end_time}</div>
                            <div class="item-subject">${schedule.subject}</div>
                            <div class="item-grade">${schedule.grade_section} - ${schedule.room?.room_number ? 'Room ' + schedule.room.room_number : 'N/A'}</div>
                            <div style="font-size: 0.75rem; color: #999; margin-top: 0.25rem;">
                                📍 ${schedule.day_of_week} | 👥 ${schedule.student_count || 0} students
                            </div>
                            ${schedule.approved_at ? `<div style="font-size: 0.75rem; color: #2d7a50; margin-top: 0.5rem;">✓ Approved on ${new Date(schedule.approved_at).toLocaleDateString()}</div>` : ''}
                        </div>
                        <div class="item-right">
                            <span class="item-badge">Approved</span>
                        </div>
                    </div>
                `).join('');
            })
            .catch(error => {
                console.error('Error loading schedules:', error);
                document.getElementById('scheduleContainer').innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #c83232;">
                        Error loading schedules. Please refresh the page.
                    </div>
                `;
            });
        }
    </script>

@endsection
