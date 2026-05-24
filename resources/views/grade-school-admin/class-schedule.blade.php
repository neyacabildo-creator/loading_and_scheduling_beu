{{-- resources/views/grade-school-admin/class-schedule.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Grade School - Class Schedule Management')

@section('content')
    <style>
        .schedule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }

        /* Calendar Styles */
        .calendar-card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; padding: 1.5rem; margin-bottom: 2rem; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .calendar-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); }
        .month-nav { display: flex; gap: 0.5rem; }
        .month-btn { padding: 0.5rem 1rem; background: transparent; border: none; cursor: pointer; border-radius: 0.375rem; transition: all 0.2s; color: var(--text-primary); }
        .month-btn:hover { background: rgba(45,122,80,0.1); color: var(--green-primary); }

        .weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; margin-bottom: 1rem; }
        .weekday { text-align: center; font-weight: 600; color: var(--text-secondary); padding: 0.5rem; font-size: 0.875rem; }

        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .calendar-day { padding: 1rem 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem; min-height: 100px; cursor: pointer; transition: all 0.2s; background: var(--bg-secondary); color: var(--text-primary); }
        .calendar-day:hover { background: rgba(45,122,80,0.05); border-color: var(--green-primary); }
        .calendar-day.other-month { color: var(--text-secondary); background: var(--bg-tertiary); }
        .calendar-day.today { background: rgba(45,122,80,0.15); border-color: var(--green-primary); }
        .calendar-day.drag-over { background: rgba(45,122,80,0.15) !important; outline: 2px dashed var(--green-primary); }
        .day-number { font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem; font-size: 0.875rem; }

        .schedule-event { padding: 0.25rem 0.5rem; background: linear-gradient(135deg, rgba(45,122,80,0.8) 0%, rgba(45,122,80,0.6) 100%); color: white; border-radius: 0.25rem; font-size: 0.65rem; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: grab; }
        .schedule-event:active { cursor: grabbing; opacity: 0.7; }
        .schedule-event.pending { background: linear-gradient(135deg, rgba(234,179,8,0.8) 0%, rgba(234,179,8,0.6) 100%); }
        .schedule-event.rejected { background: linear-gradient(135deg, rgba(200,50,50,0.8) 0%, rgba(200,50,50,0.6) 100%); }

        /* Dark Mode */
        html[data-theme="dark"] .calendar-day { background: #3a3a3a !important; color: #e0e0e0 !important; border-color: #404040 !important; }
        html[data-theme="dark"] .calendar-day:hover { background: rgba(45,122,80,0.3) !important; }
        html[data-theme="dark"] .calendar-day.other-month { background: #2d2d2d !important; color: #707070 !important; }
        html[data-theme="dark"] .calendar-day.today { background: rgba(45,122,80,0.35) !important; border-color: var(--green-primary) !important; }
        html[data-theme="dark"] .calendar-card { background: #2d2d2d !important; border-color: #404040 !important; color: #e0e0e0 !important; }
        html[data-theme="dark"] .calendar-title { color: #e0e0e0 !important; }
        html[data-theme="dark"] .weekday { color: #a0a0a0 !important; }
        html[data-theme="dark"] .day-number { color: #e0e0e0 !important; }
        html[data-theme="dark"] table { background: #2d2d2d !important; }
        html[data-theme="dark"] th { background: #3a3a3a !important; color: #e0e0e0 !important; border-color: #404040 !important; }
        html[data-theme="dark"] td { border-color: #404040 !important; color: #b0b0b0 !important; background: #2d2d2d !important; }
        html[data-theme="dark"] tr:hover { background: #3a3a3a !important; }
        html[data-theme="dark"] .table-card { background: #2d2d2d !important; border-color: #404040 !important; }
        html[data-theme="dark"] .month-btn:hover { background: rgba(45,122,80,0.15) !important; }

        #pendingSchedulesTable th:last-child,
        #pendingSchedulesTable td:last-child,
        #approvedSchedulesTable th:last-child,
        #approvedSchedulesTable td:last-child {
            white-space: nowrap;
            vertical-align: middle;
            min-width: 11.5rem;
        }
        .schedule-table-actions,
        .schedule-actions-row {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }
        .schedule-table-actions .action-btn,
        .schedule-actions-row .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            flex: 0 0 auto;
            white-space: nowrap;
            min-height: 2rem;
            padding: 0.4rem 0.7rem;
            font-size: 0.72rem;
            line-height: 1.2;
            box-sizing: border-box;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .schedule-table-actions .action-btn.approve,
        .schedule-actions-row .action-btn.approve { background: rgba(45,122,80,0.1); color: var(--green-primary); border: 1px solid var(--green-primary); }
        .schedule-table-actions .action-btn.approve:hover,
        .schedule-actions-row .action-btn.approve:hover { background: var(--green-primary); color: white; }
        .schedule-table-actions .action-btn.edit,
        .schedule-actions-row .action-btn.edit { background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); }
        .schedule-table-actions .action-btn.edit:hover,
        .schedule-actions-row .action-btn.edit:hover { background: var(--text-secondary); color: white; }
        .schedule-table-actions .action-btn.delete,
        .schedule-actions-row .action-btn.delete,
        .schedule-table-actions .action-btn.reject,
        .schedule-actions-row .action-btn.reject { background: transparent; color: #c83232; border: 1px solid #c83232; }
        .schedule-table-actions .action-btn.delete:hover,
        .schedule-actions-row .action-btn.delete:hover,
        .schedule-table-actions .action-btn.reject:hover,
        .schedule-actions-row .action-btn.reject:hover { background: #c83232; color: white; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Class Schedule Management</h1>
        </div>
        <div class="header-right"></div>
    </div>

    <!-- Principal Pending Banner -->
    <div id="principalPendingBanner" style="display:none;background:#fefce8;border:1px solid #ca8a04;border-radius:0.625rem;padding:0.875rem 1.25rem;margin-bottom:1.5rem;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <svg width="18" height="18" fill="none" stroke="#ca8a04" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span style="color:#854d0e;font-size:0.875rem;font-weight:500;flex:1;">
            <strong id="principalPendingCount">0</strong> approved schedule(s) are awaiting <strong>Principal final approval</strong>.
            Teachers can already see these but will be reminded the schedule is not yet fully confirmed.
        </span>
        <a href="{{ route('principal.schedule-approvals') }}" style="color:#854d0e;font-size:0.82rem;font-weight:600;text-decoration:underline;white-space:nowrap;">View &amp; Approve →</a>
    </div>

    @include('partials.admin-class-schedule-dss-chrome', ['dssPrefix' => 'gs'])

    <!-- Pending Schedules -->
    <div id="pending-schedules" class="table-card" style="margin-bottom: 2rem;">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
            <h2 class="table-title">Pending Schedules for Approval</h2>
            <div id="gsPendingBulkActions" style="display:none;"></div>
        </div>
        <div style="overflow-x: auto;">
            <table id="pendingSchedulesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Grade/Section</th>
                        <th>Day</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody">
                    <tr><td colspan="9" style="text-align:center;padding:2rem;">Loading schedules...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approved Schedules -->
    <div class="table-card" style="margin-bottom: 2rem;">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
            <h2 class="table-title">Approved Schedules</h2>
            <div id="gsBulkActions" style="display:none;"></div>
        </div>
        <div style="overflow-x: auto;">
            <table id="approvedSchedulesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Grade/Section</th>
                        <th>Day</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="approvedTableBody">
                    <tr><td colspan="10" style="text-align:center;padding:2rem;">Loading schedules...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div id="approvalModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:var(--bg-secondary);border-radius:0.75rem;padding:2rem;max-width:500px;width:90%;box-shadow:var(--shadow-md);border:1px solid var(--border-color);">
            <h3 id="modalTitle" style="font-size:1.25rem;font-weight:600;margin-bottom:1rem;color:var(--text-primary);"></h3>
            <div id="scheduleDetails" style="background:var(--bg-tertiary);padding:1rem;border-radius:0.5rem;margin-bottom:1.5rem;font-size:0.875rem;color:var(--text-secondary);"></div>
            <div id="rejectReasonSection" style="display:none;margin-bottom:1.5rem;">
                <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Reason:</label>
                <textarea id="rejectReason" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);background:var(--bg-secondary);color:var(--text-primary);border-radius:0.5rem;resize:vertical;min-height:100px;" placeholder="Enter reason..."></textarea>
            </div>
            <div style="display:flex;gap:1rem;">
                <button id="confirmBtn" style="flex:1;padding:0.75rem;background:var(--green-primary);color:white;border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;">Confirm</button>
                <button onclick="gsCloseModal()" style="flex:1;padding:0.75rem;background:var(--border-color);color:var(--text-primary);border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:1.5rem;box-sizing:border-box;">
        <div style="background:var(--bg-secondary);border-radius:0.75rem;max-width:600px;width:100%;max-height:calc(100vh - 3rem);display:flex;flex-direction:column;box-shadow:var(--shadow-md);margin:0 auto;border:1px solid var(--border-color);">
            <div style="padding:1.25rem 1.5rem 0;flex-shrink:0;">
                <h3 style="font-size:1.25rem;font-weight:600;margin:0;color:var(--text-primary);">Edit Schedule</h3>
            </div>
            <form id="editForm" style="overflow-y:auto;flex:1;padding:1.25rem 1.5rem 1.5rem;">
                <div style="margin-bottom:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Teacher:</label>
                    <select id="editFacultyId" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);" required>
                        <option value="">Select grade and subject first</option>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Subject:</label>
                    <select id="editSubject" onchange="var t=document.getElementById('editFacultyId');if(t){delete t.dataset.lastTeacher;delete t.dataset.teacherLabel;}gsEditLoadTeachers()" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);" required>
                        <option value="">Select Subject</option>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Grade Level:</label>
                    <select id="editGradeLevel" onchange="gsEditOnGradeChange()" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);" required>
                        <option value="">Select Grade</option>
                        <option value="Grade 1">Grade 1</option>
                        <option value="Grade 2">Grade 2</option>
                        <option value="Grade 3">Grade 3</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option>
                        <option value="Grade 6">Grade 6</option>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Section:</label>
                    <select id="editSectionName" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);" required>
                        <option value="">Select Section</option>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Day:</label>
                    <select id="editDay" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);" required>
                        <option value="">Select Day</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div style="margin-bottom:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Schedule Date:</label>
                    <input id="editScheduleDate" type="date" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">Start Time:</label>
                        <input id="editStartTime" type="time" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);" required>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:500;color:var(--text-primary);">End Time:</label>
                        <input id="editEndTime" type="time" style="width:100%;padding:0.75rem;border:1px solid var(--border-color);border-radius:0.5rem;background:var(--bg-secondary);color:var(--text-primary);" required>
                    </div>
                </div>
                <div style="display:flex;gap:1rem;">
                    <button type="submit" style="flex:1;padding:0.75rem;background:var(--green-primary);color:white;border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;">Save Changes</button>
                    <button type="button" onclick="gsCloseEditModal()" style="flex:1;padding:0.75rem;background:var(--border-color);color:var(--text-primary);border:none;border-radius:0.5rem;cursor:pointer;font-weight:500;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let gsCurrentScheduleId = null;
        let gsCurrentAction = null;
        let gsScheduleCache = {};

        let gsSharedTeacherIds = new Set();

        function gsRefreshDss() {
            if (!window.AdminScheduleDss) {
                return { analysis: { conflictIds: new Set(), missingDateIds: new Set(), missingRoomIds: new Set(), summaries: [] } };
            }
            return AdminScheduleDss.refreshFromCache(gsScheduleCache, 'gs');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Fetch shared teacher IDs for badge display
            fetch('/api/shared-teachers-panel', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            }).then(r => r.json()).then(d => {
                if (d.success && d.data) gsSharedTeacherIds = new Set(d.data.map(t => t.id));
            }).catch(() => {});

            gsLoadPendingSchedules();
            gsLoadApprovedSchedules();
            setInterval(() => {
                gsLoadPendingSchedules();
                gsLoadApprovedSchedules();
            }, 30000);
        });

        // ---- Timetable ----
        const GS_DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        const GS_GRADE_SECTIONS_MAP = {
            'Grade 1': ['STEPHEN', 'PETER', 'ST. PAUL'],
            'Grade 2': ['ST. LUKE', 'ST. MARK', 'ST. MATTHEW'],
            'Grade 3': ['ST. JOHN', 'ST. JAMES', 'ST. JOSEPH'],
            'Grade 4': ['ST. FRANCIS', 'ST. AQUINAS', 'ST. LORENZO'],
            'Grade 5': ['ST. MARGARETTE', 'ST. THERESE', 'ST. AGATHA'],
            'Grade 6': ['ST. MA. GORETTI', 'ST. CATHERINE', 'ST. CLAIRE'],
        };
        let gsCurrentGradeFilter = '';
        function gsSetGradeFilter(grade) { gsCurrentGradeFilter = grade; gsRenderTimetable(); }
        const GS_SLOTS = @json(\App\Support\SchoolScheduleSlots::scheduleDashboardSlots('grade_school'));
        let gsCurrentDay = 'Monday';
        let gsWeekOffset = 0;

        // Mark today
        (function(){
            const names=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const t=names[new Date().getDay()];
            const defaultDay = GS_DAYS.includes(t) ? t : 'Monday';
            gsCurrentDay = defaultDay;
            document.querySelectorAll('.gs-day-btn').forEach(b=>{
                if(b.dataset.day===t) b.classList.add('today-marker');
                b.classList.toggle('active',b.dataset.day===gsCurrentDay);
            });
            gsUpdateTTDate();
        })();

        function gsGetTTWeekDate(dayName){
            const days=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const today=new Date();
            const diff=days.indexOf(dayName)-today.getDay();
            const target=new Date(today);
            target.setDate(today.getDate()+diff+gsWeekOffset*7);
            return target.toLocaleDateString('en-US',{weekday:'long',month:'long',day:'numeric',year:'numeric'});
        }
        function gsUpdateTTDate(){
            const el=document.getElementById('gsTTDateLabel');
            if(el){
                const weekSuffix = gsWeekOffset===0?' (This Week)':gsWeekOffset<0?` (${Math.abs(gsWeekOffset)} week${Math.abs(gsWeekOffset)>1?'s':''} ago)`:(` (${gsWeekOffset} week${gsWeekOffset>1?'s':''} ahead)`);
                el.textContent=gsGetTTWeekDate(gsCurrentDay)+weekSuffix;
            }
        }
        function gsTTPrevWeek(){ gsWeekOffset--; gsUpdateTTDate(); }
        function gsTTNextWeek(){ gsWeekOffset++; gsUpdateTTDate(); }
        function gsTTSetDay(btn,day){
            gsCurrentDay=day;
            document.querySelectorAll('.gs-day-btn').forEach(b=>b.classList.toggle('active',b.dataset.day===day));
            gsRenderTimetable();
            gsUpdateTTDate();
        }
        function gsTTPrevDay(){
            const idx=GS_DAYS.indexOf(gsCurrentDay);
            gsCurrentDay=GS_DAYS[(idx-1+GS_DAYS.length)%GS_DAYS.length];
            document.querySelectorAll('.gs-day-btn').forEach(b=>b.classList.toggle('active',b.dataset.day===gsCurrentDay));
            gsRenderTimetable();
            gsUpdateTTDate();
        }
        function gsTTNextDay(){
            const idx=GS_DAYS.indexOf(gsCurrentDay);
            gsCurrentDay=GS_DAYS[(idx+1)%GS_DAYS.length];
            document.querySelectorAll('.gs-day-btn').forEach(b=>b.classList.toggle('active',b.dataset.day===gsCurrentDay));
            gsRenderTimetable();
            gsUpdateTTDate();
        }
        function gsTimeToMins(t){if(!t)return 0;const p=String(t).substring(0,5).split(':');return parseInt(p[0])*60+parseInt(p[1]||0);}
        function gsLoadTimetable() {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/api/grade-school-admin/combined-schedules', {
                headers: { 'X-CSRF-TOKEN': token || '', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(d => { gsTimetableData = d.data || d || []; gsRenderTimetable(); })
            .catch(() => gsRenderTimetable());
        }
        function gsRenderTimetable() {
            const filter = (document.getElementById('gsTTFilter')?.value||'').toLowerCase().trim();
            const day    = gsCurrentDay;
            let allDay   = gsTimetableData.filter(s => !['rejected','deleted'].includes(s.status) && (s.day_of_week||'').toLowerCase()===day.toLowerCase());
            if(filter) allDay=allDay.filter(s => (s.faculty?.name||'').toLowerCase().includes(filter));

            // Split GS vs JH; apply grade filter only to GS schedules
            let gsDay = allDay.filter(s => s.school !== 'JH');
            let jhDay = allDay.filter(s => s.school === 'JH');
            if(gsCurrentGradeFilter) gsDay = gsDay.filter(s => (s.grade_level||'') === gsCurrentGradeFilter);
            const schedules = [...gsDay, ...jhDay];

            // GS section columns: use grade map when grade is filtered, else only data-driven
            const mapGSSections = gsCurrentGradeFilter ? (GS_GRADE_SECTIONS_MAP[gsCurrentGradeFilter] || []) : [];
            const dataGSSections = [...new Set(gsDay.map(s => s.section_name || '—'))];
            const gsSections = mapGSSections.length
                ? [...mapGSSections, ...dataGSSections.filter(s => !mapGSSections.includes(s))]
                : dataGSSections;

            // JH section columns (shared teachers only)
            const jhSections = [...new Set(jhDay.map(s => s.section_name || 'JH'))];

            const sections = [...gsSections, ...jhSections];

            // Grade lookup for column headers
            const sectionGradeMap = new Map();
            schedules.forEach(s => { const sec=s.section_name||'—'; if(!sectionGradeMap.has(sec)) sectionGradeMap.set(sec, s.grade_level||''); });
            mapGSSections.forEach(sec => { if(!sectionGradeMap.has(sec)) sectionGradeMap.set(sec, gsCurrentGradeFilter||''); });

            schedules.forEach(s => { if (s && s.id) gsScheduleCache[s.id] = s; });
            const conflictIds = gsRefreshDss().analysis.conflictIds;

            const thead=document.getElementById('gsTimetableHead');
            const tbody=document.getElementById('gsTimetableBody');

            if(!sections.length){
                thead.innerHTML=`<tr><th style="width:80px;padding:.6rem .5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);font-size:.75rem;text-align:center;font-weight:600;color:var(--text-secondary);">Time</th><th style="padding:.6rem;background:linear-gradient(135deg,rgba(45,122,80,.12),rgba(45,122,80,.04));border:1px solid var(--border-color);font-size:.78rem;font-weight:700;color:var(--text-primary);text-align:center;">${day}</th></tr>`;
                tbody.innerHTML=`<tr><td colspan="2" style="text-align:center;padding:2rem;color:var(--text-secondary);">No schedules for ${day}${filter?' matching "'+filter+'"':''}.</td></tr>`;
                return;
            }

            let headHtml=`<tr><th style="width:80px;padding:.6rem .5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);font-size:.75rem;text-align:center;font-weight:600;color:var(--text-secondary);">Time</th>`;
            sections.forEach(sec=>{
                const isJH = jhSections.includes(sec);
                const grade = sectionGradeMap.get(sec) || '';
                const bg = isJH
                    ? 'background:linear-gradient(135deg,rgba(37,99,235,.12),rgba(37,99,235,.04))'
                    : 'background:linear-gradient(135deg,rgba(45,122,80,.12),rgba(45,122,80,.04))';
                const schoolBadge = isJH ? '<span style="display:inline-block;font-size:.6rem;background:#2563eb;color:white;border-radius:3px;padding:1px 5px;font-weight:700;margin-bottom:2px;">JH</span><br>' : '';
                headHtml+=`<th style="padding:.6rem;${bg};border:1px solid var(--border-color);font-size:.78rem;font-weight:700;color:var(--text-primary);text-align:center;min-width:110px;">${grade?'<span style="font-size:.7rem;color:var(--text-secondary);">'+grade+'</span><br>':''}${schoolBadge}${sec}</th>`;
            });
            thead.innerHTML=headHtml+'</tr>';

            let html='';
            GS_SLOTS.forEach(slot=>{
                if(slot.isBreak){
                    html+=`<tr><td style="padding:.4rem;border:1px solid var(--border-color);text-align:center;font-size:.68rem;color:#92400e;background:rgba(245,158,11,.07);font-weight:700;">${slot.label}</td><td colspan="${sections.length}" style="border:1px solid var(--border-color);background:rgba(245,158,11,.07);text-align:center;font-size:.72rem;color:#92400e;font-weight:700;letter-spacing:.06em;">&#10022; ${slot.label} BREAK &#10022;</td></tr>`;
                    return;
                }
                const slotS=gsTimeToMins(slot.start),slotE=gsTimeToMins(slot.end);
                let row=`<tr><td style="padding:.4rem .5rem;border:1px solid var(--border-color);text-align:center;font-size:.72rem;color:var(--text-secondary);background:var(--bg-tertiary);white-space:pre;font-weight:500;">${slot.label}</td>`;
                sections.forEach(sec=>{
                    const cell=schedules.filter(s=>{
                        const sMatch=(s.section_name||'—')===sec;
                        return sMatch&&gsTimeToMins(s.start_time)<slotE&&gsTimeToMins(s.end_time)>slotS;
                    });
                    if(cell.length){
                        const pills=cell.map(s=>{
                            const name=(s.faculty?.name||'Unknown').replace(/</g,'&lt;');
                            const subj=(s.subject||'').replace(/</g,'&lt;');
                            const isConflict=conflictIds.has(s.id);
                            const isMissing=!s.schedule_date||!s.room_id;
                            const isJHSched = s.school === 'JH';
                            const ok=!!s.admin_approved;
                            const bc=isConflict?'#c83232':(isMissing?'#d97706':(isJHSched?'#2563eb':(ok?'var(--green-primary)':'#ca8a04')));
                            const bg=isConflict?'rgba(200,50,50,.12)':(isMissing?'rgba(217,119,6,.12)':(isJHSched?'rgba(37,99,235,.1)':(ok?'rgba(45,122,80,.1)':'rgba(202,138,4,.1)')));
                            const nc=isConflict?'#c83232':(isMissing?'#b45309':(isJHSched?'#1d4ed8':(ok?'var(--green-primary)':'#92400e')));
                            const cf=isConflict?'<div style="font-size:.62rem;color:#c83232;font-weight:700;">&#9888; CONFLICT</div>':(isMissing?'<div style="font-size:.62rem;color:#d97706;font-weight:700;">Missing date/room</div>':'');
                            const jhBadge=isJHSched?'<span style="font-size:.6rem;background:#2563eb;color:white;border-radius:2px;padding:0 4px;font-weight:700;margin-right:2px;">JH</span>':'';
                            return `<div style="padding:.3rem .4rem;background:${bg};border-left:3px solid ${bc};border-radius:.25rem;margin-bottom:.2rem;font-size:.68rem;line-height:1.3;"><div style="font-weight:700;color:${nc};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${name}">${jhBadge}${name}</div>${subj?`<div style="color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${subj}</div>`:''}${cf}</div>`;
                        }).join('');
                        row+=`<td style="padding:.3rem;border:1px solid var(--border-color);vertical-align:top;">${pills}</td>`;
                    } else {
                        row+=`<td style="padding:.3rem;border:1px solid var(--border-color);background:var(--bg-secondary);"></td>`;
                    }
                });
                html+=row+'</tr>';
            });
            tbody.innerHTML=html;
        }

        // ---- Tables ----
        function gsLoadPendingSchedules() {
            const tbody = document.getElementById('pendingTableBody');
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/api/grade-school-admin/schedules', {
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                const all = data.data || data || [];
                const schedules = all.filter(s => s && (!s.admin_approved || s.admin_approved === false) && (s.status === 'pending' || !s.status));
                all.forEach(s => { if (s && s.id) gsScheduleCache[s.id] = s; });
                const analysis = gsRefreshDss().analysis;
                if (schedules.length > 0) {
                    tbody.innerHTML = schedules.map(s => {
                        const rowCls = AdminScheduleDss ? AdminScheduleDss.rowClass(s, analysis) : '';
                        const badge = AdminScheduleDss ? AdminScheduleDss.rowBadge(s, analysis) : '';
                        return `<tr data-schedule-id="${s.id}"${rowCls ? ` class="${rowCls}"` : ''}>
                            <td class="id-badge">#${s.id}</td>
                            <td>${badge}${s.faculty?.name || 'N/A'}${gsSharedTeacherIds.has(s.faculty_id) ? ' <span style="background:#2563eb;color:white;border-radius:9999px;font-size:0.65rem;padding:1px 7px;font-weight:700;vertical-align:middle;white-space:nowrap;">SHARED</span>' : ''}</td>
                            <td>${s.subject || 'N/A'}</td>
                            <td>${adminScheduleGradeSection(s)}</td>
                            <td>${s.day_of_week || '—'}</td>
                            <td>${adminScheduleDate(s)}</td>
                            <td>${adminScheduleTimeRange(s)}</td>
                            <td>${adminScheduleRoom(s)}</td>
                            <td>
                                <div class="schedule-table-actions">
                                    <button type="button" class="action-btn approve" onclick="gsOpenApprovalModal(${s.id}, 'approve')">Approve</button>
                                    <button type="button" class="action-btn edit" onclick="gsOpenEditModal(${s.id})">Edit</button>
                                    <button type="button" class="action-btn reject" onclick="gsQuickRejectSchedule(${s.id}, this)">Reject</button>
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-secondary);">No pending schedules</td></tr>';
                }
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:#ef4444;padding:2rem;">Unable to load schedules. ${err.message}</td></tr>`;
            });
        }

        function gsLoadApprovedSchedules() {
            const tbody = document.getElementById('approvedTableBody');
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/api/grade-school-admin/schedules', {
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                const all = data.data || data || [];
                const schedules = all.filter(s => s && s.admin_approved && (s.status === 'active' || s.status === 'approved'));
                all.forEach(s => { if (s && s.id) gsScheduleCache[s.id] = s; });
                const analysis = gsRefreshDss().analysis;
                // Principal final-approval pending banner
                const pendingPrincipal = all.filter(s => s && s.admin_approved && !s.principal_approved).length;
                const banner = document.getElementById('principalPendingBanner');
                if (banner) {
                    banner.style.display = pendingPrincipal > 0 ? 'flex' : 'none';
                    const countEl = document.getElementById('principalPendingCount');
                    if (countEl) countEl.textContent = pendingPrincipal;
                }
                if (schedules.length > 0) {
                    tbody.innerHTML = schedules.map(s => {
                        const rowCls = AdminScheduleDss ? AdminScheduleDss.rowClass(s, analysis) : '';
                        const badge = AdminScheduleDss ? AdminScheduleDss.rowBadge(s, analysis) : '';
                        return `<tr data-schedule-id="${s.id}"${rowCls ? ` class="${rowCls}"` : ''}>
                            <td class="id-badge">#${s.id}</td>
                            <td>${badge}${s.faculty?.name || 'N/A'}${gsSharedTeacherIds.has(s.faculty_id) ? ' <span style="background:#2563eb;color:white;border-radius:9999px;font-size:0.65rem;padding:1px 7px;font-weight:700;vertical-align:middle;white-space:nowrap;">SHARED</span>' : ''}</td>
                            <td>${s.subject || 'N/A'}</td>
                            <td>${adminScheduleGradeSection(s)}</td>
                            <td>${s.day_of_week || '—'}</td>
                            <td>${adminScheduleDate(s)}</td>
                            <td>${adminScheduleTimeRange(s)}</td>
                            <td>${adminScheduleRoom(s)}</td>
                            <td><span class="badge badge-active">${s.status || 'active'}</span></td>
                            <td>
                                <div class="schedule-actions-row">
                                    <button type="button" class="action-btn edit" onclick="gsOpenEditModal(${s.id})">Edit</button>
                                    <button type="button" class="action-btn delete" onclick="gsQuickDeleteSchedule(${s.id}, this)">Delete</button>
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-secondary);">No approved schedules</td></tr>';
                }
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;color:#ef4444;padding:2rem;">Unable to load schedules. ${err.message}</td></tr>`;
            });
        }

        // ---- Modals ----
        function gsOpenApprovalModal(scheduleId, action) {
            gsCurrentScheduleId = scheduleId;
            gsCurrentAction = action;
            const modal = document.getElementById('approvalModal');
            document.getElementById('modalTitle').textContent =
                action === 'approve' ? 'Approve Schedule' : (action === 'reject' ? 'Reject Schedule' : 'Delete Schedule');
            document.getElementById('rejectReasonSection').style.display = (action !== 'approve') ? 'block' : 'none';

            const cached = gsScheduleCache[scheduleId];
            if (cached) {
                const timeStart = cached.start_time ? cached.start_time.substring(0, 5) : 'N/A';
                const timeEnd   = cached.end_time   ? cached.end_time.substring(0, 5)   : 'N/A';
                const gradeSection = cached.grade_level
                    ? cached.grade_level + (cached.section_name ? ' - ' + cached.section_name : '')
                    : (cached.section_name || 'N/A');
                document.getElementById('scheduleDetails').innerHTML =
                    `<strong>${cached.subject || 'N/A'}</strong> - ${gradeSection}<br>` +
                    `${cached.day_of_week || 'N/A'} | ${timeStart} - ${timeEnd}<br>` +
                    `Teacher: ${cached.faculty?.name || 'N/A'}`;
            } else {
                document.getElementById('scheduleDetails').textContent = 'Loading...';
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch(`/api/grade-school-admin/schedules/${scheduleId}`, {
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    credentials: 'same-origin'
                })
                .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                .then(res => {
                    const s = res.data || res;
                    gsScheduleCache[s.id] = s;
                    const timeStart = s.start_time ? s.start_time.substring(0, 5) : 'N/A';
                    const timeEnd   = s.end_time   ? s.end_time.substring(0, 5)   : 'N/A';
                    const gradeSection = s.grade_level
                        ? s.grade_level + (s.section_name ? ' - ' + s.section_name : '')
                        : (s.section_name || 'N/A');
                    document.getElementById('scheduleDetails').innerHTML =
                        `<strong>${s.subject || 'N/A'}</strong> - ${gradeSection}<br>` +
                        `${s.day_of_week || 'N/A'} | ${timeStart} - ${timeEnd}<br>` +
                        `Teacher: ${s.faculty?.name || 'N/A'}`;
                })
                .catch(() => { document.getElementById('scheduleDetails').textContent = 'Schedule #' + scheduleId; });
            }

            modal.style.display = 'flex';
            document.getElementById('confirmBtn').onclick = function() {
                if (action === 'approve') gsApproveSchedule(scheduleId);
                else if (action === 'reject') gsRejectSchedule(scheduleId);
                else gsDeleteSchedule(scheduleId);
            };
        }

        function gsCloseModal() {
            document.getElementById('approvalModal').style.display = 'none';
            document.getElementById('rejectReason').value = '';
        }

        function gsApproveSchedule(id) {
            const schedule = gsScheduleCache[id];
            const all = Object.values(gsScheduleCache);
            if (window.AdminScheduleDss && !AdminScheduleDss.shouldProceedWithApprove(schedule, all)) {
                return;
            }
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(`/api/grade-school-admin/schedules/${id}/approve`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(d => {
                if (d.success) { alert('Schedule approved!'); gsCloseModal(); gsLoadPendingSchedules(); gsLoadApprovedSchedules(); }
                else alert('Error: ' + (d.message || 'Failed to approve'));
            })
            .catch(err => alert('Error approving schedule: ' + err.message));
        }

        function gsQuickRejectSchedule(id, btn) {
            if (!confirm('Reject and remove this schedule from the system?')) return;
            if (btn) { btn.disabled = true; btn.textContent = 'Rejecting…'; }
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(`/api/grade-school-admin/schedules/${id}/reject`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ reason: 'Rejected by administrator' })
            })
            .then(r => { if (!r.ok) return r.text().then(t => { throw new Error('HTTP ' + r.status + ': ' + t); }); return r.json(); })
            .then(d => {
                if (d.success) {
                    gsLoadPendingSchedules();
                    gsLoadApprovedSchedules();
                } else {
                    alert('Error: ' + (d.message || 'Failed to reject'));
                    if (btn) { btn.disabled = false; btn.textContent = 'Reject'; }
                }
            })
            .catch(err => {
                alert('Error rejecting schedule: ' + err.message);
                if (btn) { btn.disabled = false; btn.textContent = 'Reject'; }
            });
        }

        function gsQuickDeleteSchedule(id, btn) {
            adminQuickDeleteSchedule(id, btn, {
                url: '/api/grade-school-admin/schedules/' + id,
                onRollback: function () { gsLoadPendingSchedules(); gsLoadApprovedSchedules(); }
            });
        }

        function gsRejectSchedule(id) {
            const reason = document.getElementById('rejectReason').value.trim() || 'Rejected by administrator';
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(`/api/grade-school-admin/schedules/${id}/reject`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ reason })
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(d => {
                if (d.success) { gsCloseModal(); gsLoadPendingSchedules(); gsLoadApprovedSchedules(); }
                else alert('Error: ' + (d.message || 'Failed to reject'));
            })
            .catch(err => alert('Error rejecting schedule: ' + err.message));
        }

        function gsDeleteSchedule(id) {
            const reason = document.getElementById('rejectReason').value.trim() || 'Deleted by administrator';
            gsCloseModal();
            adminRemoveScheduleInstant(id);
            adminScheduleDeleteApi(id, { reason: reason, url: '/api/grade-school-admin/schedules/' + id })
                .catch(function () { gsLoadPendingSchedules(); gsLoadApprovedSchedules(); });
        }

        function gsLoadRoomsIntoSelect(selectedRoomId) {
            const sel = document.getElementById('editRoomId');
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch('/api/grade-school-admin/rooms', {
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => r.json())
            .then(res => {
                const rooms = res.data || [];
                sel.innerHTML = '<option value="">No Room / Unassigned</option>' +
                    rooms.map(r => `<option value="${r.id}" ${r.id == selectedRoomId ? 'selected' : ''}>Room #${r.id} (Cap: ${r.capacity})</option>`).join('');
            })
            .catch(() => {});
        }

        const GS_EDIT_GRADE_SUBJECTS = {
            'Grade 1': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Mother Tongue','Reading'],
            'Grade 2': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Mother Tongue','Reading'],
            'Grade 3': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Mother Tongue','Reading'],
            'Grade 4': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Computer Education','Technology and Livelihood Education','Values Education'],
            'Grade 5': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Computer Education','Technology and Livelihood Education','Values Education'],
            'Grade 6': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Christian Living Education','MAPEH','Computer Education','Technology and Livelihood Education','Values Education'],
        };

        function gsUpdateSectionOptions() {
            const grade = document.getElementById('editGradeLevel').value;
            const sel = document.getElementById('editSectionName');
            const sections = GS_GRADE_SECTIONS_MAP[grade] || [];
            sel.innerHTML = '<option value="">Select Section</option>' +
                sections.map(s => `<option value="${s}">${s}</option>`).join('');
        }

        function normalizeGradeKey(gradeVal) {
            const m = String(gradeVal || '').match(/(\d+)/);
            return m ? 'Grade ' + m[1] : String(gradeVal || '').trim();
        }

        function setEditGradeLevel(gradeVal) {
            const sel = document.getElementById('editGradeLevel');
            const key = normalizeGradeKey(gradeVal);
            let matched = false;
            [...sel.options].forEach(o => {
                if (!matched && (normalizeGradeKey(o.value) === key || o.value === gradeVal)) {
                    sel.value = o.value;
                    matched = true;
                }
            });
            if (!matched && gradeVal) {
                const opt = document.createElement('option');
                opt.value = gradeVal;
                opt.textContent = gradeVal;
                sel.appendChild(opt);
                sel.value = gradeVal;
            }
        }

        function gsEditPopulateSubjects(selectedSubject) {
            const gradeKey = normalizeGradeKey(document.getElementById('editGradeLevel').value);
            const subjSel = document.getElementById('editSubject');
            let subjects = GS_EDIT_GRADE_SUBJECTS[gradeKey] || [];
            const sel = (selectedSubject || '').trim();
            if (sel && !subjects.some(s => s.toLowerCase() === sel.toLowerCase())) {
                subjects = [sel, ...subjects];
            }
            subjSel.innerHTML = '<option value="">Select Subject</option>' +
                subjects.map(s => `<option value="${s}">${s}</option>`).join('');
            if (sel) {
                const opt = [...subjSel.options].find(o => o.value.toLowerCase() === sel.toLowerCase());
                if (opt) subjSel.value = opt.value;
            }
        }

        function gsEditOnGradeChange() {
            gsUpdateSectionOptions();
            gsEditPopulateSubjects('');
            gsEditLoadTeachers();
            gsEditAssignRoom();
        }

        function gsEditAssignRoom() {
            const grade = document.getElementById('editGradeLevel')?.value || '';
            const section = document.getElementById('editSectionName')?.value || '';
            const labelEl = document.getElementById('editRoomLabel');
            const idEl = document.getElementById('editRoomId');
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!labelEl || !idEl) return;
            if (!grade || !section) {
                labelEl.value = 'Select grade and section';
                idEl.value = '';
                return;
            }
            labelEl.value = 'Loading room…';
            const params = new URLSearchParams({ grade_level: grade, section_name: section });
            fetch('/api/grade-school-admin/room-for-section?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token || '' }
            })
            .then(r => r.json())
            .then(res => {
                labelEl.value = res.label || 'No room assigned';
                idEl.value = res.room_id ? String(res.room_id) : '';
            })
            .catch(() => {
                labelEl.value = 'Unable to load room';
                idEl.value = '';
            });
        }

        function gsEditLoadTeachers(presetFacultyId) {
            const grade = document.getElementById('editGradeLevel').value;
            const subject = document.getElementById('editSubject').value;
            const teacherSel = document.getElementById('editFacultyId');
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!grade && !subject) {
                teacherSel.innerHTML = '<option value="">Select grade and subject first</option>';
                return;
            }
            const params = new URLSearchParams();
            if (grade) params.set('grade_level', grade);
            if (subject) params.set('subject', subject);
            teacherSel.innerHTML = '<option value="">Loading teachers…</option>';
            fetch('/api/grade-school-admin/teachers/by-grade-subject?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token || '' }
            })
            .then(r => r.json())
            .then(res => {
                const teachers = res.teachers || [];
                const keepId = presetFacultyId || teacherSel.dataset.lastTeacher || teacherSel.value;
                if (!teachers.length && keepId) {
                    const label = teacherSel.dataset.teacherLabel || ('Teacher #' + keepId);
                    teacherSel.innerHTML = '<option value="">Select Teacher</option>' +
                        `<option value="${keepId}" selected>${label}</option>`;
                    teacherSel.value = String(keepId);
                    return;
                }
                if (!teachers.length) {
                    teacherSel.innerHTML = '<option value="">-- Select Teacher --</option>';
                    return;
                }
                teacherSel.innerHTML = '<option value="">Select Teacher</option>' +
                    teachers.map(t => `<option value="${t.id}">${t.name}</option>`).join('');
                if (keepId) teacherSel.value = String(keepId);
                if (teacherSel.value) teacherSel.dataset.lastTeacher = teacherSel.value;
            })
            .catch(() => { teacherSel.innerHTML = '<option value="">Unable to load teachers</option>'; });
        }

        function gsOpenEditModal(scheduleId) {
            gsCurrentScheduleId = scheduleId;
            const modal = document.getElementById('editModal');
            ['editDay','editScheduleDate','editStartTime','editEndTime'].forEach(id => {
                document.getElementById(id).value = '';
            });
            document.getElementById('editGradeLevel').value  = '';
            document.getElementById('editSubject').innerHTML = '<option value="">Loading…</option>';
            document.getElementById('editFacultyId').innerHTML = '<option value="">Loading…</option>';
            document.getElementById('editSectionName').value = '';
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(`/api/grade-school-admin/schedules/${scheduleId}`, {
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(res => {
                const s = res.data || res;
                setEditGradeLevel(s.grade_level || '');
                gsEditPopulateSubjects(s.subject || '');
                gsUpdateSectionOptions();
                document.getElementById('editSectionName').value  = s.section_name  || '';
                document.getElementById('editDay').value          = s.day_of_week   || '';
                document.getElementById('editScheduleDate').value = s.schedule_date ? String(s.schedule_date).substring(0, 10) : '';
                document.getElementById('editStartTime').value    = s.start_time    ? String(s.start_time).substring(0, 5) : '';
                document.getElementById('editEndTime').value      = s.end_time      ? String(s.end_time).substring(0, 5) : '';
                const facultyName = s.faculty
                    ? ((s.faculty.first_name || '') + ' ' + (s.faculty.last_name || '')).trim() || s.faculty.name
                    : '';
                const editFacultySel = document.getElementById('editFacultyId');
                if (editFacultySel && s.faculty_id) {
                    editFacultySel.dataset.lastTeacher = String(s.faculty_id);
                    editFacultySel.dataset.teacherLabel = facultyName || ('Teacher #' + s.faculty_id);
                }
                gsEditLoadTeachers(s.faculty_id);
                modal.style.display = 'flex';
            })
            .catch(err => alert('Error loading schedule: ' + err.message));
            document.getElementById('editForm').onsubmit = function(e) {
                e.preventDefault();
                gsUpdateSchedule(scheduleId);
            };
        }

        function gsCloseEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function gsUpdateSchedule(scheduleId) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const facultyId = parseInt(document.getElementById('editFacultyId').value, 10);
            if (!facultyId) {
                alert('Please select a teacher before saving.');
                return;
            }
            const payload = {
                faculty_id:    facultyId,
                subject:       document.getElementById('editSubject').value,
                grade_level:   document.getElementById('editGradeLevel').value,
                section_name:  document.getElementById('editSectionName').value,
                day_of_week:   document.getElementById('editDay').value,
                schedule_date: document.getElementById('editScheduleDate').value,
                start_time:    document.getElementById('editStartTime').value,
                end_time:      document.getElementById('editEndTime').value
            };
            const all = Object.values(gsScheduleCache);
            if (window.AdminScheduleDss && !AdminScheduleDss.shouldProceedWithSave(payload, all, scheduleId)) {
                return;
            }
            fetch(`/api/grade-school-admin/schedules/${scheduleId}`, {
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            })
            .then(async r => {
                const body = await r.json().catch(() => ({}));
                if (!r.ok) {
                    const msg = body.errors
                        ? Object.values(body.errors).flat().join(', ')
                        : (body.message || 'HTTP ' + r.status);
                    throw new Error(msg);
                }
                return body;
            })
            .then(d => {
                if (d.success === false) throw new Error(d.message || 'Failed to update');
                gsCloseEditModal();
                gsLoadPendingSchedules();
                gsLoadApprovedSchedules();
            })
            .catch(err => alert('Error updating schedule: ' + err.message));
        }

        // Close modals on overlay click
        document.addEventListener('click', function(e) {
            if (e.target === document.getElementById('approvalModal')) gsCloseModal();
            if (e.target === document.getElementById('editModal')) gsCloseEditModal();
        });

    </script>
@endsection
