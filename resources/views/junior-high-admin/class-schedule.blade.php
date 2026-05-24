{{-- resources/views/admin/class-schedule.blade.php --}}
@extends('layouts.admin')

@section('title', 'Class Schedule')

@section('content')
    <style>
        .schedule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .schedule-nav { display: flex; gap: 1rem; }
        .schedule-btn { padding: 0.5rem 1rem; border: 1px solid var(--border-color); background: var(--bg-secondary); border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; color: var(--text-primary); }
        .schedule-btn:hover, .schedule-btn.active { background: var(--green-primary); color: white; border-color: var(--green-primary); }
        
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
        .day-number { font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem; font-size: 0.875rem; }
        
        .schedule-event { padding: 0.25rem 0.5rem; background: linear-gradient(135deg, rgba(45,122,80,0.8) 0%, rgba(45,122,80,0.6) 100%); color: white; border-radius: 0.25rem; font-size: 0.65rem; margin-bottom: 0.25rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .schedule-event.pending { background: linear-gradient(135deg, rgba(234,179,8,0.8) 0%, rgba(234,179,8,0.6) 100%); }
        .schedule-event.completed { background: linear-gradient(135deg, rgba(150,150,150,0.8) 0%, rgba(150,150,150,0.6) 100%); }
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
        
        /* Dark Mode Calendar Styles - Force Apply */
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
        html[data-theme="dark"] .room-card { background: #3a3a3a !important; border-color: #404040 !important; }
        html[data-theme="dark"] .month-btn:hover { background: rgba(45,122,80,0.15) !important; }
        
        /* Rooms Section */
        .rooms-section { margin-top: 2rem; }
        .rooms-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
        .room-card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 1.5rem; }
        .room-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .room-name { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); }
        .room-status { display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .room-status.active { background: rgba(45,122,80,0.15); color: var(--green-primary); }
        .room-status.inactive { background: rgba(150,150,150,0.15); color: var(--text-secondary); }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .room-info { display: flex; flex-direction: column; gap: 0.75rem; }
        .room-info-item { font-size: 0.875rem; color: var(--text-secondary); }
        .room-info-label { font-weight: 500; color: var(--text-primary); }
        .room-schedule { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color); }
        .schedule-item { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; font-size: 0.875rem; }
        .schedule-time { color: var(--text-secondary); font-weight: 500; }
        .schedule-subject { color: var(--text-primary); }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Class Schedule Management</h1>
        </div>

    </div>

    <!-- Principal Pending Banner -->
    <div id="principalPendingBanner" style="display:none;background:#fefce8;border:1px solid #ca8a04;border-radius:0.625rem;padding:0.875rem 1.25rem;margin-bottom:1.5rem;align-items:center;gap:0.75rem;flex-wrap:wrap;">
        <svg width="18" height="18" fill="none" stroke="#ca8a04" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <span style="color:#854d0e;font-size:0.875rem;font-weight:500;flex:1;">
            <strong id="principalPendingCount">0</strong> approved schedule(s) are awaiting <strong>Principal final approval</strong>.
            Teachers can already see these but will be reminded the schedule is not yet fully confirmed.
        </span>
    </div>

    <!-- Pending Schedules for Review -->
    <div id="pending-schedules" class="table-card" style="margin-bottom: 2rem;">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
            <h2 class="table-title">Pending Schedules for Approval</h2>
            <div id="jhPendingBulkActions" style="display:none;"></div>
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
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2rem;">Loading schedules...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approved Schedules -->
    <div class="table-card" style="margin-bottom: 2rem;">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
            <h2 class="table-title">Approved Schedules</h2>
            <div id="jhBulkActions" style="display:none;"></div>
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
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 2rem;">Loading schedules...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Approve/Reject -->
    <div id="approvalModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 2rem; max-width: 500px; width: 90%; box-shadow: var(--shadow-md); border: 1px solid var(--border-color);">
            <h3 id="modalTitle" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary);"></h3>
            <div id="scheduleDetails" style="background: var(--bg-tertiary); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem; color: var(--text-secondary);"></div>
            <div id="rejectReasonSection" style="display: none; margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Reason:</label>
                <textarea id="rejectReason" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); border-radius: 0.5rem; resize: vertical; min-height: 100px;" placeholder="Enter reason for rejection..."></textarea>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button id="confirmBtn" style="flex: 1; padding: 0.75rem; background: var(--green-primary); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;">Confirm</button>
                <button onclick="closeModal()" style="flex: 1; padding: 0.75rem; background: var(--border-color); color: var(--text-primary); border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Modal for Edit -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem; box-sizing: border-box;">
        <div class="edit-schedule-modal-panel" style="background: var(--bg-secondary); border-radius: 0.75rem; max-width: 600px; width: 100%; max-height: calc(100vh - 3rem); display: flex; flex-direction: column; box-shadow: var(--shadow-md); border: 1px solid var(--border-color); margin: 0 auto;">
            <div style="padding: 1.25rem 1.5rem 0; flex-shrink: 0;">
                <h3 style="font-size: 1.25rem; font-weight: 600; margin: 0; color: var(--text-primary);">Edit Schedule</h3>
            </div>
            <form id="editForm" style="overflow-y: auto; flex: 1; padding: 1.25rem 1.5rem 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Teacher:</label>
                    <select id="editFacultyId" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                        <option value="">Select grade and subject first</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Subject:</label>
                    <select id="editSubject" onchange="var t=document.getElementById('editFacultyId');if(t){delete t.dataset.lastTeacher;delete t.dataset.teacherLabel;}jhEditLoadTeachers()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                        <option value="">Select Subject</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Grade Level:</label>
                    <select id="editGradeLevel" onchange="jhEditOnGradeChange()" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                        <option value="">Select Grade</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Section:</label>
                    <select id="editSectionName" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                        <option value="">Select Section</option>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Day:</label>
                    <select id="editDay" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
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
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Schedule Date:</label>
                    <input id="editScheduleDate" type="date" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Start Time:</label>
                        <input id="editStartTime" type="time" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">End Time:</label>
                        <input id="editEndTime" type="time" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                    </div>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.75rem; background: var(--green-primary); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;">Save Changes</button>
                    <button type="button" onclick="closeEditModal()" style="flex: 1; padding: 0.75rem; background: var(--border-color); color: var(--text-primary); border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500; transition: all 0.2s;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .calendar-day.drag-over {
            background: rgba(45, 122, 80, 0.15) !important;
            outline: 2px dashed var(--green-primary);
        }
        .schedule-event[draggable="true"] {
            cursor: grab;
        }
        .schedule-event[draggable="true"]:active {
            cursor: grabbing;
            opacity: 0.7;
        }
    </style>

    <script>
        let currentScheduleId = null;
        let currentAction = null;
        let jhScheduleCache = {};
        
        let jhSharedTeacherIds = new Set();

        // Load schedules on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Fetch shared teacher IDs for badge display
            fetch('/api/shared-teachers-panel', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            }).then(r => r.json()).then(d => {
                if (d.success && d.data) jhSharedTeacherIds = new Set(d.data.map(t => t.id));
            }).catch(() => {});

            loadPendingSchedules();
            loadApprovedSchedules();
            // Refresh every 30 seconds
            setInterval(() => {
                loadPendingSchedules();
                loadApprovedSchedules();
            }, 30000);
        });

        function jhTimeToMins(t){if(!t)return 0;const p=String(t).substring(0,5).split(':');return parseInt(p[0])*60+parseInt(p[1]||0);}

        function jhRenderTimetable() {
            const filter  = (document.getElementById('jhTTFilter')?.value||'').toLowerCase().trim();
            const day     = jhCurrentDay;
            let schedules = jhTimetableData.filter(s => !['rejected', 'deleted'].includes(s.status) && (s.day_of_week||'').toLowerCase()===day.toLowerCase());
            if(filter) schedules=schedules.filter(s => (s.faculty?.name||'').toLowerCase().includes(filter));

            // Build section columns
            const sectionMap=new Map();
            schedules.forEach(s=>{
                const sec=s.section_name||'—';
                if(!sectionMap.has(sec)) sectionMap.set(sec,s.grade_level||'');
            });
            const sections=[...sectionMap.keys()];

            // Conflict detection
            const conflictIds=new Set();
            const byFaculty={};
            schedules.forEach(s=>{if(s.faculty_id){(byFaculty[s.faculty_id]=byFaculty[s.faculty_id]||[]).push(s);}});
            Object.values(byFaculty).forEach(arr=>{
                for(let i=0;i<arr.length;i++) for(let j=i+1;j<arr.length;j++){
                    const a=arr[i],b=arr[j];
                    if(jhTimeToMins(a.start_time)<jhTimeToMins(b.end_time)&&jhTimeToMins(b.start_time)<jhTimeToMins(a.end_time)){
                        conflictIds.add(a.id);conflictIds.add(b.id);
                    }
                }
            });
            const banner=document.getElementById('jhConflictBanner');
            if(banner) banner.style.display=conflictIds.size>0?'block':'none';

            const thead=document.getElementById('jhTimetableHead');
            const tbody=document.getElementById('jhTimetableBody');

            if(!schedules.length){
                thead.innerHTML=`<tr><th style="width:80px;padding:.6rem .5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);font-size:.75rem;text-align:center;font-weight:600;color:var(--text-secondary);">Time</th><th style="padding:.6rem;background:linear-gradient(135deg,rgba(45,122,80,.12),rgba(45,122,80,.04));border:1px solid var(--border-color);font-size:.78rem;font-weight:700;color:var(--text-primary);text-align:center;">${day}</th></tr>`;
                tbody.innerHTML=`<tr><td colspan="2" style="text-align:center;padding:2rem;color:var(--text-secondary);">No schedules for ${day}${filter?' matching "'+filter+'"':''}.</td></tr>`;
                return;
            }

            let headHtml=`<tr><th style="width:80px;padding:.6rem .5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);font-size:.75rem;text-align:center;font-weight:600;color:var(--text-secondary);">Time</th>`;
            sections.forEach(sec=>{
                const grade=sectionMap.get(sec);
                headHtml+=`<th style="padding:.6rem;background:linear-gradient(135deg,rgba(45,122,80,.12),rgba(45,122,80,.04));border:1px solid var(--border-color);font-size:.78rem;font-weight:700;color:var(--text-primary);text-align:center;min-width:110px;">${grade?'<span style="font-size:.7rem;color:var(--text-secondary);">'+grade+'</span><br>':''}${sec}</th>`;
            });
            thead.innerHTML=headHtml+'</tr>';

            let html='';
            JH_SLOTS.forEach(slot=>{
                if(slot.isBreak){
                    html+=`<tr><td style="padding:.4rem;border:1px solid var(--border-color);text-align:center;font-size:.68rem;color:#92400e;background:rgba(245,158,11,.07);font-weight:700;">${slot.label}</td><td colspan="${sections.length}" style="border:1px solid var(--border-color);background:rgba(245,158,11,.07);text-align:center;font-size:.72rem;color:#92400e;font-weight:700;letter-spacing:.06em;">&#10022; ${slot.label} BREAK &#10022;</td></tr>`;
                    return;
                }
                const slotS=jhTimeToMins(slot.start),slotE=jhTimeToMins(slot.end);
                let row=`<tr><td style="padding:.4rem .5rem;border:1px solid var(--border-color);text-align:center;font-size:.72rem;color:var(--text-secondary);background:var(--bg-tertiary);white-space:pre;font-weight:500;">${slot.label}</td>`;
                sections.forEach(sec=>{
                    const cell=schedules.filter(s=>{
                        const sMatch=(s.section_name||'—')===sec;
                        return sMatch&&jhTimeToMins(s.start_time)<slotE&&jhTimeToMins(s.end_time)>slotS;
                    });
                    if(cell.length){
                        const pills=cell.map(s=>{
                            const name=(s.faculty?.name||'Unknown').replace(/</g,'&lt;');
                            const subj=(s.subject||'').replace(/</g,'&lt;');
                            const isConflict=conflictIds.has(s.id);
                            const ok=!!s.admin_approved;
                            const bc=isConflict?'#c83232':(ok?'var(--green-primary)':'#ca8a04');
                            const bg=isConflict?'rgba(200,50,50,.12)':(ok?'rgba(45,122,80,.1)':'rgba(202,138,4,.1)');
                            const nc=isConflict?'#c83232':(ok?'var(--green-primary)':'#92400e');
                            const cf=isConflict?'<div style="font-size:.62rem;color:#c83232;font-weight:700;">&#9888; CONFLICT</div>':'';
                            return `<div style="padding:.3rem .4rem;background:${bg};border-left:3px solid ${bc};border-radius:.25rem;margin-bottom:.2rem;font-size:.68rem;line-height:1.3;"><div style="font-weight:700;color:${nc};white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="${name}">${name}</div>${subj?`<div style="color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${subj}</div>`:''}${cf}</div>`;
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

        // Load pending schedules
        function loadPendingSchedules() {
            const tbody = document.getElementById('pendingTableBody');
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            
            if (!token) {
                console.error('CSRF token not found');
                tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; color: #ef4444; padding: 2rem;">Security error: CSRF token missing.</td></tr>';
                return;
            }
            
            fetch('/api/admin/schedules', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('Pending Schedules API Response Status:', response.status, response.statusText);
                
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('API Error Response:', response.status, text);
                        throw new Error(`HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('Pending Schedules API Data Received:', data);
                const allSchedules = (data.data || data || []);
                
                if (!Array.isArray(allSchedules)) {
                    throw new Error('Invalid data format - expected array');
                }
                
                const schedules = allSchedules.filter(s => s && (!s.admin_approved || s.admin_approved === false) && (s.status === 'pending' || !s.status));
                
                // Cache all schedules for modal use
                allSchedules.forEach(s => { if (s && s.id) jhScheduleCache[s.id] = s; });
                
                if (schedules.length > 0) {
                    tbody.innerHTML = schedules.map(schedule => {
                        const dayOfWeek = schedule.day_of_week || '—';
                        return `
                            <tr data-schedule-id="${schedule.id}">
                                <td class="id-badge">#${schedule.id}</td>
                                <td>${schedule.faculty?.name || 'N/A'}${jhSharedTeacherIds.has(schedule.faculty_id) ? ' <span style="background:#2563eb;color:white;border-radius:9999px;font-size:0.65rem;padding:1px 7px;font-weight:700;vertical-align:middle;white-space:nowrap;">SHARED</span>' : ''}</td>
                                <td>${schedule.subject || 'N/A'}</td>
                                <td>${adminScheduleGradeSection(schedule)}</td>
                                <td>${dayOfWeek}</td>
                                <td>${adminScheduleDate(schedule)}</td>
                                <td>${adminScheduleTimeRange(schedule)}</td>
                                <td>${adminScheduleRoom(schedule)}</td>
                                <td>
                                    <div class="schedule-table-actions">
                                        <button type="button" class="action-btn approve" onclick="openApprovalModal(${schedule.id}, 'approve')">Approve</button>
                                        <button type="button" class="action-btn edit" onclick="openEditModal(${schedule.id})">Edit</button>
                                        <button type="button" class="action-btn reject" onclick="quickRejectSchedule(${schedule.id}, this)">Reject</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No pending schedules</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading pending schedules:', error);
                tbody.innerHTML = `<tr><td colspan="9" style="text-align: center; color: #ef4444; padding: 2rem;">Unable to load schedules. ${error.message}</td></tr>`;
            });
        }

        // Load approved schedules
        function loadApprovedSchedules() {
            const tbody = document.getElementById('approvedTableBody');
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            
            if (!token) {
                console.error('CSRF token not found');
                tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; color: #ef4444; padding: 2rem;">Security error: CSRF token missing.</td></tr>';
                return;
            }
            
            fetch('/api/admin/schedules', {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('API Response Status:', response.status, response.statusText);
                
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('API Error Response:', response.status, text);
                        throw new Error(`HTTP ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('API Data Received:', data);
                const allSchedules = (data.data || data || []);
                
                if (!Array.isArray(allSchedules)) {
                    throw new Error('Invalid data format');
                }
                
                const schedules = allSchedules.filter(s => s && s.admin_approved && (s.status === 'active' || s.status === 'approved'));
                
                // Cache all schedules for modal use
                allSchedules.forEach(s => { if (s && s.id) jhScheduleCache[s.id] = s; });
                
                // Principal final-approval pending banner
                const pendingPrincipal = allSchedules.filter(s => s && s.admin_approved && !s.principal_approved).length;
                const banner = document.getElementById('principalPendingBanner');
                if (banner) {
                    banner.style.display = pendingPrincipal > 0 ? 'flex' : 'none';
                    const countEl = document.getElementById('principalPendingCount');
                    if (countEl) countEl.textContent = pendingPrincipal;
                }

                if (schedules.length > 0) {
                    tbody.innerHTML = schedules.map(schedule => {
                        const dayOfWeek = schedule.day_of_week || '—';
                        return `
                            <tr data-schedule-id="${schedule.id}">
                                <td class="id-badge">#${schedule.id}</td>
                                <td>${schedule.faculty?.name || 'N/A'}${jhSharedTeacherIds.has(schedule.faculty_id) ? ' <span style="background:#2563eb;color:white;border-radius:9999px;font-size:0.65rem;padding:1px 7px;font-weight:700;vertical-align:middle;white-space:nowrap;">SHARED</span>' : ''}</td>
                                <td>${schedule.subject || 'N/A'}</td>
                                <td>${adminScheduleGradeSection(schedule)}</td>
                                <td>${dayOfWeek}</td>
                                <td>${adminScheduleDate(schedule)}</td>
                                <td>${adminScheduleTimeRange(schedule)}</td>
                                <td>${adminScheduleRoom(schedule)}</td>
                                <td><span class="badge badge-active">${schedule.status || 'active'}</span></td>
                                <td>
                                    <div class="schedule-actions-row">
                                        <button type="button" class="action-btn edit" onclick="openEditModal(${schedule.id})">Edit</button>
                                        <button type="button" class="action-btn delete" onclick="adminQuickDeleteSchedule(${schedule.id}, this, { url: '/api/admin/schedules/${schedule.id}', onRollback: function(){ loadPendingSchedules(); loadApprovedSchedules(); } })">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No approved schedules</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading approved schedules:', error);
                tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; color: #ef4444; padding: 2rem;">Unable to load schedules. ${error.message}</td></tr>`;
            });
        }

        // Open approval/rejection modal
        function openApprovalModal(scheduleId, action) {
            currentScheduleId = scheduleId;
            currentAction = action;
            const modal = document.getElementById('approvalModal');
            const modalTitle = document.getElementById('modalTitle');
            const scheduleDetails = document.getElementById('scheduleDetails');
            const rejectReasonSection = document.getElementById('rejectReasonSection');

            if (action === 'approve') {
                modalTitle.textContent = 'Approve Schedule';
                rejectReasonSection.style.display = 'none';
            } else {
                modalTitle.textContent = action === 'reject' ? 'Reject Schedule' : 'Delete Schedule';
                rejectReasonSection.style.display = 'block';
            }

            // Use cached schedule data (already loaded in the table)
            const schedule = jhScheduleCache[scheduleId];
            if (schedule) {
                const timeStart = schedule.start_time ? schedule.start_time.substring(0, 5) : (schedule.time_start ? String(schedule.time_start).substring(11, 16) : 'N/A');
                const timeEnd   = schedule.end_time   ? schedule.end_time.substring(0, 5)   : (schedule.time_end   ? String(schedule.time_end).substring(11, 16)   : 'N/A');
                const gradeSection = schedule.grade_level
                    ? schedule.grade_level + (schedule.section_name ? ' - ' + schedule.section_name : '')
                    : (schedule.section_name || 'N/A');
                scheduleDetails.innerHTML = `
                    <strong>${schedule.subject || 'N/A'}</strong> - ${gradeSection}<br>
                    ${schedule.day_of_week || 'N/A'} | ${timeStart} - ${timeEnd}<br>
                    Teacher: ${schedule.faculty?.name || 'N/A'}
                `;
            } else {
                scheduleDetails.innerHTML = `<em>Loading details…</em>`;
                // Fallback fetch if not cached yet
                fetch(`/api/admin/schedules/${scheduleId}`, {
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
                })
                .then(r => r.ok ? r.json() : Promise.reject(r.status))
                .then(s => {
                    jhScheduleCache[scheduleId] = s;
                    const ts = s.start_time ? s.start_time.substring(0, 5) : 'N/A';
                    const te = s.end_time   ? s.end_time.substring(0, 5)   : 'N/A';
                    const gs = s.grade_level ? s.grade_level + (s.section_name ? ' - ' + s.section_name : '') : (s.section_name || 'N/A');
                    scheduleDetails.innerHTML = `
                        <strong>${s.subject || 'N/A'}</strong> - ${gs}<br>
                        ${s.day_of_week || 'N/A'} | ${ts} - ${te}<br>
                        Teacher: ${s.faculty?.name || 'N/A'}
                    `;
                })
                .catch(() => { scheduleDetails.innerHTML = `<em style="color:#c83232;">Could not load schedule details.</em>`; });
            }

            modal.style.display = 'flex';

            document.getElementById('confirmBtn').onclick = function() {
                if (action === 'approve') {
                    approveSchedule(scheduleId);
                } else if (action === 'reject') {
                    rejectSchedule(scheduleId);
                } else {
                    deleteSchedule(scheduleId);
                }
            };
        }

        // Close approval modal
        function closeModal() {
            document.getElementById('approvalModal').style.display = 'none';
            document.getElementById('rejectReason').value = '';
        }

        // Approve schedule
        function approveSchedule(scheduleId) {
            fetch(`/api/admin/schedules/${scheduleId}/approve`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Schedule approved successfully!');
                    closeModal();
                    loadPendingSchedules();
                    loadApprovedSchedules();
                    // Trigger a page refresh for the dashboard if it exists
                    window.dispatchEvent(new Event('scheduleUpdated'));
                } else {
                    alert('Error: ' + (data.message || 'Failed to approve schedule'));
                }
            })
            .catch(error => {
                console.error('Full error:', error);
                alert('Error approving schedule: ' + error.message);
            });
        }

        function quickRejectSchedule(scheduleId, btn) {
            if (!confirm('Reject and remove this schedule from the system?')) return;
            if (btn) { btn.disabled = true; btn.textContent = 'Rejecting…'; }
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            fetch(`/api/admin/schedules/${scheduleId}/reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: 'Rejected by administrator' })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { throw new Error(`HTTP ${response.status}: ${text}`); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    loadPendingSchedules();
                    loadApprovedSchedules();
                    window.dispatchEvent(new Event('scheduleUpdated'));
                } else {
                    alert('Error: ' + (data.message || 'Failed to reject schedule'));
                    if (btn) { btn.disabled = false; btn.textContent = 'Reject'; }
                }
            })
            .catch(error => {
                console.error('Full error:', error);
                alert('Error rejecting schedule: ' + error.message);
                if (btn) { btn.disabled = false; btn.textContent = 'Reject'; }
            });
        }

        function quickDeleteSchedule(scheduleId, btn) {
            adminQuickDeleteSchedule(scheduleId, btn, {
                url: '/api/admin/schedules/' + scheduleId,
                onRollback: function () { loadPendingSchedules(); loadApprovedSchedules(); }
            });
        }

        // Reject schedule (modal flow — approve still uses modal)
        function rejectSchedule(scheduleId) {
            const reason = document.getElementById('rejectReason').value.trim() || 'Rejected by administrator';

            fetch(`/api/admin/schedules/${scheduleId}/reject`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP ${response.status}: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    closeModal();
                    loadPendingSchedules();
                    loadApprovedSchedules();
                    window.dispatchEvent(new Event('scheduleUpdated'));
                } else {
                    alert('Error: ' + (data.message || 'Failed to reject schedule'));
                }
            })
            .catch(error => {
                console.error('Full error:', error);
                alert('Error rejecting schedule: ' + error.message);
            });
        }

        // Delete schedule (modal flow)
        function deleteSchedule(scheduleId) {
            const reason = document.getElementById('rejectReason').value.trim() || 'Deleted by administrator';
            closeModal();
            adminRemoveScheduleInstant(scheduleId);
            adminScheduleDeleteApi(scheduleId, { reason: reason, url: '/api/admin/schedules/' + scheduleId })
                .catch(function () {
                    loadPendingSchedules();
                    loadApprovedSchedules();
                });
        }

        // Open edit modal
        // JH section names keyed by grade number
        const JH_SECTIONS = {
            '7':  ['SERAPHIM','CHERUBIM','MICHAEL','RAPHAEL','GABRIEL'],
            '8':  ['THERESE','ALOYSIUS','AGNES','JOHN','GORETTI'],
            '9':  ['CHARTRES','PIAT','FATIMA','CARMEL','LOURDES'],
            '10': ['PAUL','PLC','MBF','MICHEAU','MARIA'],
        };

        const JH_EDIT_GRADE_SUBJECTS = {
            'Grade 7':  ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
            'Grade 8':  ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
            'Grade 9':  ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
            'Grade 10': ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
        };

        function jhUpdateSectionOptions() {
            const gradeVal = document.getElementById('editGradeLevel').value;
            const sel = document.getElementById('editSectionName');
            const gradeNum = (gradeVal.match(/\d+/) || [])[0] || '';
            const sections = JH_SECTIONS[gradeNum] || [];
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

        function jhEditPopulateSubjects(selectedSubject) {
            const gradeKey = normalizeGradeKey(document.getElementById('editGradeLevel').value);
            const subjSel = document.getElementById('editSubject');
            let subjects = JH_EDIT_GRADE_SUBJECTS[gradeKey] || [];
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

        function jhEditOnGradeChange() {
            jhUpdateSectionOptions();
            jhEditPopulateSubjects('');
            jhEditLoadTeachers();
            jhEditAssignRoom();
        }

        function jhEditAssignRoom() {
            const grade = document.getElementById('editGradeLevel')?.value || '';
            const section = document.getElementById('editSectionName')?.value || '';
            const labelEl = document.getElementById('editRoomLabel');
            const idEl = document.getElementById('editRoomId');
            if (!labelEl || !idEl) return;
            if (!grade || !section) {
                labelEl.value = 'Select grade and section';
                idEl.value = '';
                return;
            }
            labelEl.value = 'Loading room…';
            const params = new URLSearchParams({ grade_level: grade, section_name: section });
            fetch('/api/admin/room-for-section?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
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

        function jhEditLoadTeachers(presetFacultyId) {
            const grade = document.getElementById('editGradeLevel').value;
            const subject = document.getElementById('editSubject').value;
            const teacherSel = document.getElementById('editFacultyId');
            if (!grade && !subject) {
                teacherSel.innerHTML = '<option value="">Select grade and subject first</option>';
                return;
            }
            const params = new URLSearchParams();
            if (grade) params.set('grade_level', grade);
            if (subject) params.set('subject', subject);
            teacherSel.innerHTML = '<option value="">Loading teachers…</option>';
            fetch('/api/admin/teachers/by-grade-subject?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
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

        function openEditModal(scheduleId) {
            currentScheduleId = scheduleId;
            const modal = document.getElementById('editModal');

            // Clear fields before fetch to avoid stale values
            ['editDay','editScheduleDate','editStartTime','editEndTime'].forEach(id => {
                document.getElementById(id).value = '';
            });
            document.getElementById('editGradeLevel').value  = '';
            document.getElementById('editSubject').innerHTML = '<option value="">Loading…</option>';
            document.getElementById('editFacultyId').innerHTML = '<option value="">Loading…</option>';
            document.getElementById('editSectionName').innerHTML = '<option value="">Select Section</option>';

            fetch(`/api/admin/schedules/${scheduleId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(t => { throw new Error(`HTTP ${response.status}: ${t}`); });
                }
                return response.json();
            })
            .then(res => {
                const s = res.data || res;
                setEditGradeLevel(s.grade_level || '');
                jhEditPopulateSubjects(s.subject || '');
                jhUpdateSectionOptions();
                document.getElementById('editSectionName').value   = s.section_name  || '';
                document.getElementById('editDay').value           = s.day_of_week   || '';
                document.getElementById('editScheduleDate').value  = s.schedule_date ? String(s.schedule_date).substring(0, 10) : '';
                document.getElementById('editStartTime').value     = s.start_time    ? String(s.start_time).substring(0, 5) : '';
                document.getElementById('editEndTime').value       = s.end_time      ? String(s.end_time).substring(0, 5) : '';
                const facultyName = s.faculty
                    ? ((s.faculty.first_name || '') + ' ' + (s.faculty.last_name || '')).trim() || s.faculty.name
                    : '';
                const editFacultySel = document.getElementById('editFacultyId');
                if (editFacultySel && s.faculty_id) {
                    editFacultySel.dataset.lastTeacher = String(s.faculty_id);
                    editFacultySel.dataset.teacherLabel = facultyName || ('Teacher #' + s.faculty_id);
                }
                jhEditLoadTeachers(s.faculty_id);
                modal.style.display = 'flex';
            })
            .catch(error => {
                console.error('Error loading schedule:', error);
                alert('Error loading schedule details: ' + error.message);
            });

            document.getElementById('editForm').onsubmit = function(e) {
                e.preventDefault();
                updateSchedule(scheduleId);
            };
        }

        // Close edit modal
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Update schedule
        function updateSchedule(scheduleId) {
            const facultyId = parseInt(document.getElementById('editFacultyId').value, 10);
            if (!facultyId) {
                alert('Please select a teacher before saving.');
                return;
            }
            const data = {
                faculty_id:    facultyId,
                subject:       document.getElementById('editSubject').value,
                grade_level:   document.getElementById('editGradeLevel').value,
                section_name:  document.getElementById('editSectionName').value,
                day_of_week:   document.getElementById('editDay').value,
                schedule_date: document.getElementById('editScheduleDate').value,
                start_time:    document.getElementById('editStartTime').value,
                end_time:      document.getElementById('editEndTime').value
            };
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch(`/api/admin/schedules/${scheduleId}`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(async response => {
                const body = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const msg = body.errors
                        ? Object.values(body.errors).flat().join(', ')
                        : (body.message || `HTTP ${response.status}`);
                    throw new Error(msg);
                }
                return body;
            })
            .then(data => {
                if (data.success === false) {
                    throw new Error(data.message || 'Failed to update schedule');
                }
                closeEditModal();
                loadPendingSchedules();
                loadApprovedSchedules();
                window.dispatchEvent(new Event('scheduleUpdated'));
            })
            .catch(error => {
                alert('Error updating schedule: ' + error.message);
                console.error(error);
            });
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            const approvalModal = document.getElementById('approvalModal');
            const editModal = document.getElementById('editModal');
            
            if (e.target === approvalModal) {
                closeModal();
            }
            if (e.target === editModal) {
                closeEditModal();
            }
        });

    </script>

@endsection
