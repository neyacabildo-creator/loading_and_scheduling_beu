{{-- resources/views/admin/schedule-approval/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Class Schedule Management')

@section('content')
    <style>
        /* ── Calendar ── */
        .calendar-card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; padding: 1.5rem; margin-top: 2rem; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .calendar-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); }
        .month-nav { display: flex; gap: 0.5rem; }
        .month-btn { padding: 0.5rem 1rem; background: transparent; border: none; cursor: pointer; border-radius: 0.375rem; transition: all 0.2s; color: var(--text-primary); font-weight: 500; }
        .month-btn:hover { background: rgba(45,122,80,0.1); color: var(--green-primary); }
        .weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
        .weekday { text-align: center; font-weight: 600; color: var(--text-secondary); padding: 0.5rem; font-size: 0.875rem; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .calendar-day { padding: 1rem 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; min-height: 100px; cursor: pointer; transition: all 0.2s; background: var(--bg-secondary); color: var(--text-primary); }
        .calendar-day:hover { background: rgba(45,122,80,0.05); border-color: var(--green-primary); }
        .calendar-day.other-month { color: var(--text-secondary); background: var(--bg-tertiary); }
        .calendar-day.today { background: rgba(45,122,80,0.15); border-color: var(--green-primary); }
        .day-number { font-weight: 600; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .schedule-event { padding: 0.25rem 0.5rem; background: linear-gradient(135deg,rgba(45,122,80,0.8) 0%,rgba(45,122,80,0.6) 100%); color: white; border-radius: 0.25rem; font-size: 0.65rem; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .schedule-event.pending  { background: linear-gradient(135deg,rgba(234,179,8,0.8)  0%,rgba(234,179,8,0.6)  100%); }
        .schedule-event.rejected { background: linear-gradient(135deg,rgba(239,68,68,0.8)   0%,rgba(239,68,68,0.6)   100%); }
        html[data-theme="dark"] .calendar-day { background: #3a3a3a !important; color: #e0e0e0 !important; border-color: #404040 !important; }
        html[data-theme="dark"] .calendar-day.other-month { background: #2d2d2d !important; color: #707070 !important; }
        html[data-theme="dark"] .calendar-day.today { background: rgba(45,122,80,0.35) !important; }
        html[data-theme="dark"] .calendar-card { background: #2d2d2d !important; border-color: #404040 !important; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .status-card { background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; text-align: center; }
        .status-value { font-size: 2rem; font-weight: 700; color: #2d7a50; margin-bottom: 0.5rem; }
        .status-label { font-size: 0.875rem; color: #7a7a6e; }
        .table-card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); overflow-x: auto; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .table-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); }
        .table-title { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 1.5rem; background: var(--bg-primary); text-align: left; font-weight: 600; color: var(--text-primary); border-bottom: 1px solid var(--border-color); font-size: 0.875rem; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; vertical-align: middle; color: var(--text-primary); }
        tr:hover { background: var(--bg-tertiary); }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
        .badge-pending { background: #fef3c7; color: #b8860b; }
        .badge-active { background: #dcfce7; color: #22863a; }
        .badge-rejected { background: #fee2e2; color: #c83232; }
        .action-btn { padding: 0.7rem 1.2rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 600; transition: all 0.2s; margin: 0.2rem; }
        .btn-view { background: rgba(59,130,246,0.15); color: #3b82f6; }
        .btn-view:hover { background: #3b82f6; color: white; }
        .btn-approve { background: rgba(34,197,94,0.15); color: #22c55e; }
        .btn-approve:hover { background: #22c55e; color: white; }
        .btn-reject { background: rgba(239,68,68,0.15); color: #ef4444; }
        .btn-reject:hover { background: #ef4444; color: white; }
        .loading { display: none; }
        .alert { padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; }
        .alert-success { background: rgba(45,122,80,0.15); border: 1px solid #2d7a50; color: #2d7a50; }
        .alert-error { background: rgba(220,38,38,0.15); border: 1px solid #dc2626; color: #dc2626; }
        .empty-state { padding: 2rem; text-align: center; color: #7a7a6e; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Class Schedule Management</h1>
        </div>
    </div>

    <!-- Status Cards -->
    <div class="status-grid">
        <div class="status-card">
            <div class="status-value">{{ $stats['pending'] ?? 0 }}</div>
            <div class="status-label">Pending Review</div>
        </div>
        <div class="status-card">
            <div class="status-value">{{ $stats['approved'] ?? 0 }}</div>
            <div class="status-label">Approved</div>
        </div>
        <div class="status-card">
            <div class="status-value">{{ $stats['rejected'] ?? 0 }}</div>
            <div class="status-label">Rejected</div>
        </div>
        <div class="status-card">
            <div class="status-value">{{ $stats['total'] ?? 0 }}</div>
            <div class="status-label">Total Schedules</div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-header">
            <div class="table-title">All Class Schedules</div>
        </div>
        @if($schedules->count() > 0)
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Faculty Name</th>
                        <th>Subject</th>
                        <th>Grade/Section</th>
                        <th>Room</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $schedule)
                    <tr>
                        <td><strong>{{ optional($schedule->faculty)->name ?? 'N/A' }}</strong></td>
                        <td>{{ $schedule->subject }}</td>
                        <td>{{ $schedule->grade_section }}</td>
                        <td>{{ optional($schedule->room)->room_number ?? 'N/A' }}</td>
                        <td>{{ $schedule->start_time ?? 'N/A' }} - {{ $schedule->end_time ?? 'N/A' }}</td>
                        <td>
                            @if($schedule->admin_approved && $schedule->status === 'active')
                                <span class="badge badge-active">Approved</span>
                            @elseif($schedule->status === 'rejected')
                                <span class="badge badge-rejected">Rejected</span>
                            @else
                                <span class="badge badge-pending">Pending</span>
                            @endif
                        </td>
                        <td>
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                            <a href="{{ route('admin.schedule-approval.show', $schedule->id) }}" class="action-btn btn-view">View</a>
                            @if($schedule->status !== 'active' || !$schedule->admin_approved)
                                <form method="POST" action="{{ route('admin.schedule-approval.approve', $schedule->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="action-btn btn-approve" onclick="return confirm('Approve this schedule?')">Approve</button>
                                </form>
                            @endif
                            @if($schedule->status !== 'rejected')
                                <form method="POST" action="{{ route('admin.schedule-approval.reject', $schedule->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="action-btn btn-reject" onclick="return confirm('Reject this schedule?')">Reject</button>
                                </form>
                            @endif
                        </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding: 1.5rem; text-align: right;">
            {{ $schedules->render() }}
        </div>
        @else
        <div class="empty-state">
            <p>No schedules found.</p>
        </div>
        @endif
    </div>

    <!-- Schedule Calendar -->
    <div class="calendar-card">
        <div class="calendar-header">
            <h2 class="calendar-title">Schedule Calendar</h2>
            <div style="display:flex;align-items:center;gap:1rem;">
                <span id="jhApprovalMonthYear" style="font-weight:600;color:var(--text-primary);min-width:150px;text-align:center;"></span>
                <div class="month-nav">
                    <button class="month-btn" onclick="jhPrevMonth()">‹ Previous</button>
                    <button class="month-btn" onclick="jhToday()">Today</button>
                    <button class="month-btn" onclick="jhNextMonth()">Next ›</button>
                </div>
            </div>
        </div>
        <div class="weekdays">
            <div class="weekday">Sun</div>
            <div class="weekday">Mon</div>
            <div class="weekday">Tue</div>
            <div class="weekday">Wed</div>
            <div class="weekday">Thu</div>
            <div class="weekday">Fri</div>
            <div class="weekday">Sat</div>
        </div>
        <div class="calendar-grid" id="jh-approval-calendar-days"></div>
    </div>

    <script>
        let jhCalDate    = new Date();
        let jhSchedules  = [];

        function renderJHCalendar() {
            const year  = jhCalDate.getFullYear();
            const month = jhCalDate.getMonth();

            document.getElementById('jhApprovalMonthYear').textContent =
                jhCalDate.toLocaleString('default', { month: 'long', year: 'numeric' });

            const firstDay       = new Date(year, month, 1);
            const lastDay        = new Date(year, month + 1, 0);
            const daysInMonth    = lastDay.getDate();
            const startDayOfWeek = firstDay.getDay();

            let html = '';
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            for (let i = startDayOfWeek - 1; i >= 0; i--) {
                html += `<div class="calendar-day other-month"><div class="day-number">${prevMonthLastDay - i}</div></div>`;
            }

            const dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            for (let day = 1; day <= daysInMonth; day++) {
                const today   = new Date();
                const isToday = day === today.getDate() && month === today.getMonth() && year === today.getFullYear();
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dateDow = dayNames[new Date(dateStr).getDay()];

                const dayScheds = jhSchedules.filter(s =>
                    String(s.schedule_date || '').substring(0, 10) === dateStr
                );

                html += `<div class="calendar-day ${isToday ? 'today' : ''}">
                    <div class="day-number">${day}</div>
                    ${dayScheds.slice(0, 2).map(s => {
                        const cls = s.admin_approved ? '' : (s.status === 'rejected' ? 'rejected' : 'pending');
                        return `<div class="schedule-event ${cls}" title="${s.subject || ''} – ${s.grade_section || ''}">${s.subject || 'Schedule'}</div>`;
                    }).join('')}
                    ${dayScheds.length > 2 ? `<div style="font-size:0.65rem;color:var(--text-secondary);padding:0.25rem;">+${dayScheds.length - 2} more</div>` : ''}
                </div>`;
            }

            const totalCells     = startDayOfWeek + daysInMonth;
            const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
            for (let i = 1; i <= remainingCells; i++) {
                html += `<div class="calendar-day other-month"><div class="day-number">${i}</div></div>`;
            }

            document.getElementById('jh-approval-calendar-days').innerHTML = html;
        }

        function jhPrevMonth() { jhCalDate.setMonth(jhCalDate.getMonth() - 1); renderJHCalendar(); }
        function jhNextMonth() { jhCalDate.setMonth(jhCalDate.getMonth() + 1); renderJHCalendar(); }
        function jhToday()     { jhCalDate = new Date(); renderJHCalendar(); }

        function loadJHCalendarSchedules() {
            fetch('/api/admin/schedules', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(data => { jhSchedules = data.data || data || []; renderJHCalendar(); })
            .catch(err => { console.error('Calendar load error:', err); renderJHCalendar(); });
        }

        document.addEventListener('DOMContentLoaded', function () {
            renderJHCalendar();
            loadJHCalendarSchedules();
            setInterval(loadJHCalendarSchedules, 30000);
        });
    </script>

@endsection
