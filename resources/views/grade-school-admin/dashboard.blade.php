{{-- resources/views/grade-school-admin/dashboard.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Dashboard - Grade School')

@section('content')
    <style>
        html[data-theme="dark"] .stat-card {
            background: linear-gradient(135deg, #323232 0%, #262626 100%) !important;
            border: 1px solid #404040 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35) !important;
        }

        html[data-theme="dark"] .stat-card:hover {
            border-color: #4a9d6f !important;
            box-shadow: 0 8px 20px rgba(0,0,0,0.45) !important;
        }

        html[data-theme="dark"] .stat-card .stat-label {
            color: #b0b0b0 !important;
        }

        html[data-theme="dark"] .stat-card .stat-value {
            color: #f3f4f6 !important;
        }

        html[data-theme="dark"] .stat-card .stat-change {
            color: #7dd3a7 !important;
        }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div>
                <h1 class="page-title">Dashboard for Grade School</h1>
            </div>
        </div>
        <div class="header-right"></div>
    </div>

    @include('partials.admin-teacher-absence-banner')

    <style>
        .stat-card-clickable { cursor: pointer; transition: border-color .2s, box-shadow .2s; }
        .stat-card-clickable:hover { border-color: var(--green-primary, #2d7a50); box-shadow: 0 4px 14px rgba(45,122,80,.12); }
    </style>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card stat-card-clickable" onclick="window.location='{{ route('grade-school-admin.users.index') }}'" title="Open user accounts">
            <div>
                <p class="stat-label">Total Faculty</p>
                <p class="stat-value">{{ $totalFaculty ?? 0 }}</p>
                <p class="stat-change">Active Teachers</p>
            </div>
        </div>
        <div class="stat-card stat-card-clickable" onclick="window.location='{{ route('grade-school-admin.class-schedule') }}'" title="Open class schedule">
            <div>
                <p class="stat-label">Total Schedules</p>
                <p class="stat-value">{{ $totalClasses ?? 0 }}</p>
                <p class="stat-change">{{ $approvedSchedules ?? 0 }} Approved</p>
            </div>
        </div>
        <div class="stat-card stat-card-clickable" onclick="window.location='{{ route('grade-school-admin.class-schedule') }}#pending-schedules'" title="Review pending schedules">
            <div>
                <p class="stat-label">Pending Approvals</p>
                <p class="stat-value">{{ $pendingApprovals ?? 0 }}</p>
                <p class="stat-change">Need Attention</p>
            </div>
        </div>
        <div class="stat-card stat-card-clickable" onclick="window.location='{{ route('grade-school-admin.rooms-sections.index') }}'" title="Manage rooms">
            <div>
                <p class="stat-label">Total Rooms</p>
                <p class="stat-value">{{ $totalRooms ?? 0 }}</p>
                <p class="stat-change">Available for Schedule</p>
            </div>
        </div>
        <div class="stat-card stat-card-clickable" onclick="window.location='{{ route('grade-school-admin.faculty-loading') }}'" title="Faculty loading">
            <div>
                <p class="stat-label">Total Load Hours</p>
                <p class="stat-value">{{ number_format($totalLoadHours ?? 0, 1) }}</p>
                <p class="stat-change">Faculty Assignments</p>
            </div>
        </div>
        <div class="stat-card stat-card-clickable" onclick="window.location='{{ route('grade-school-admin.class-schedule') }}'" title="Review scheduling conflicts">
            <div>
                <p class="stat-label">Scheduling Conflicts</p>
                <p class="stat-value" style="{{ ($schedulingConflicts ?? 0) > 0 ? 'color:#ef4444;' : 'color:#16a34a;' }}">{{ $schedulingConflicts ?? 0 }}</p>
                <p class="stat-change" style="{{ ($schedulingConflicts ?? 0) > 0 ? 'color:#ef4444;' : 'color:#16a34a;' }}">{{ ($schedulingConflicts ?? 0) > 0 ? 'Requires attention' : 'No conflicts detected' }}</p>
            </div>
        </div>
        <div class="stat-card stat-card-clickable" onclick="window.location='{{ route('grade-school-admin.shared-teacher-requests') }}'" title="All teacher requests">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div>
                    <p class="stat-label">All Requests</p>
                    <p class="stat-value">{{ $stReqTotal ?? 0 }}</p>
                    <p class="stat-change" style="{{ ($stReqPending ?? 0) > 0 ? 'color:#ef4444;font-weight:700;' : '' }}">
                        {{ ($stReqPending ?? 0) > 0 ? ($stReqPending . ' pending') : 'All reviewed' }}
                    </p>
                </div>
                @if(($stReqPending ?? 0) > 0)
                    <span style="background:#ef4444;color:#fff;font-size:.68rem;font-weight:700;border-radius:9999px;padding:2px 8px;line-height:1.6;flex-shrink:0;">{{ $stReqPending }} new</span>
                @endif
            </div>
            <div style="display:flex;gap:.4rem;margin-top:.6rem;flex-wrap:wrap;">
                @if(($stReqApproved ?? 0) > 0)
                    <span style="font-size:.68rem;background:rgba(22,163,74,.12);color:#16a34a;border-radius:.25rem;padding:1px 6px;font-weight:600;">{{ $stReqApproved }} approved</span>
                @endif
                @if(($stReqRejected ?? 0) > 0)
                    <span style="font-size:.68rem;background:rgba(220,38,38,.1);color:#dc2626;border-radius:.25rem;padding:1px 6px;font-weight:600;">{{ $stReqRejected }} rejected</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Faculty Loads, Rooms, Teachers Overall -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="overview-section" style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 2rem; box-shadow: var(--shadow-sm);">
            <h3 style="margin-bottom: 1.5rem; font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-top: 0;">Faculty Loads Total</h3>
            <div style="text-align: center;">
                <p style="font-size: 2.5rem; font-weight: 700; color: var(--green-primary); margin: 0 0 0.5rem 0;" id="faculty-total">{{ $totalFacultyLoads ?? 0 }}</p>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">Total with Assignments</p>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #10b981; margin: 0;" id="faculty-active-count">{{ $activeFacultyLoads ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Active</p>
                        </div>
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #f59e0b; margin: 0;" id="faculty-avail-count">{{ $availableFacultyLoads ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Available</p>
                        </div>
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #ef4444; margin: 0;" id="faculty-overload-count">{{ $overloadFacultyLoads ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Overload</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overview-section" style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 2rem; box-shadow: var(--shadow-sm);">
            <h3 style="margin-bottom: 1.5rem; font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-top: 0;">Rooms Total</h3>
            <div style="text-align: center;">
                <p style="font-size: 2.5rem; font-weight: 700; color: var(--green-primary); margin: 0 0 0.5rem 0;" id="rooms-total">{{ $totalRoomsCount ?? 0 }}</p>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">Total Classrooms</p>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #10b981; margin: 0;" id="rooms-avail-count">{{ $availableRooms ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Available</p>
                        </div>
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #f59e0b; margin: 0;" id="rooms-inuse-count">{{ $inUseRooms ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">In Use</p>
                        </div>
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #ef4444; margin: 0;" id="rooms-maint-count">{{ $maintenanceRooms ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Maintenance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="overview-section" style="background: var(--bg-secondary); border-radius: 0.75rem; padding: 2rem; box-shadow: var(--shadow-sm);">
            <h3 style="margin-bottom: 1.5rem; font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-top: 0;">Teachers/Faculty Total</h3>
            <div style="text-align: center;">
                <p style="font-size: 2.5rem; font-weight: 700; color: var(--green-primary); margin: 0 0 0.5rem 0;" id="teachers-total">{{ $totalTeachers ?? 0 }}</p>
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">Total Staff</p>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #10b981; margin: 0;" id="teachers-active-count">{{ $activeTeachers ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Active</p>
                        </div>
                        <div>
                            <p style="font-size: 1.5rem; font-weight: 600; color: #9ca3af; margin: 0;" id="teachers-inactive-count">{{ $inactiveTeachers ?? 0 }}</p>
                            <p style="font-size: 0.75rem; color: var(--text-secondary); margin: 0.5rem 0 0 0;">Inactive</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Timetable -->
    @include('partials.admin-weekly-timetable', [
        'prefix' => 'gs',
        'apiUrl' => url('/api/grade-school-admin/combined-schedules'),
        'initial' => $timetableSchedules ?? [],
        'grades' => ['1', '2', '3', '4', '5', '6'],
        'slots' => \App\Support\SchoolScheduleSlots::scheduleDashboardSlots('grade_school'),
        'slotsByDay' => \App\Support\SchoolScheduleSlots::scheduleDashboardSlotsByDay('grade_school'),
        'sections' => [
            '1' => ['STEPHEN', 'PETER', 'ST. PAUL'],
            '2' => ['ST. LUKE', 'ST. MARK', 'ST. MATTHEW'],
            '3' => ['ST. JOHN', 'ST. JAMES', 'ST. JOSEPH'],
            '4' => ['ST. FRANCIS', 'ST. AQUINAS', 'ST. LORENZO'],
            '5' => ['ST. MARGARETTE', 'ST. THERESE', 'ST. AGATHA'],
            '6' => ['ST. MA. GORETTI', 'ST. CATHERINE', 'ST. CLAIRE'],
        ],
    ])

    <!-- Edit Faculty Load Modal -->
    <div id="editFacultyModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Faculty Load</h2>
                <button onclick="closeModal('editFacultyModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="editFacultyForm">
                <input type="hidden" id="facultyId">
                <div class="form-group">
                    <label>Classes Assigned</label>
                    <input type="number" id="classesAssigned" min="0" required>
                </div>
                <div class="form-group">
                    <label>Load Hours</label>
                    <input type="number" id="loadHours" min="0" step="0.5" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="facultyStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="overload">Overload</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary">Update Load</button>
                    <button type="button" onclick="closeModal('editFacultyModal')" class="action-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Room</h2>
                <button type="button" onclick="closeAddRoomModal()" class="modal-close-btn">&times;</button>
            </div>
            <form id="addRoomForm">
                <div class="form-group">
                    <label>Capacity *</label>
                    <input type="number" id="roomCapacity" required min="1" max="200" placeholder="Number of seats">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select id="roomStatus" required>
                        <option value="available">Available</option>
                        <option value="in-use">In Use</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary">Add Room</button>
                    <button type="button" onclick="closeAddRoomModal()" class="action-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Faculty Load Modal -->
    <div id="addFacultyLoadModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Faculty Load</h2>
                <button onclick="closeAddFacultyLoadModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="addFacultyLoadForm">
                <div class="form-group">
                    <label>Faculty *</label>
                    <select id="facultySelectId" required>
                        <option value="">Select a faculty member</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Grade Level *</label>
                    <select id="facultyGradeLevel" required>
                        <option value="">Select Grade Level</option>
                        <option value="Grade 1">Grade 1</option>
                        <option value="Grade 2">Grade 2</option>
                        <option value="Grade 3">Grade 3</option>
                        <option value="Grade 4">Grade 4</option>
                        <option value="Grade 5">Grade 5</option>
                        <option value="Grade 6">Grade 6</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department *</label>
                    <input type="text" id="facultyDepartment" required placeholder="e.g., Grade School - Mathematics">
                </div>
                <div class="form-group">
                    <label>Classes Assigned *</label>
                    <input type="number" id="facultyClasses" required min="1" placeholder="Number of classes">
                </div>
                <div class="form-group">
                    <label>Load Hours *</label>
                    <input type="number" id="facultyLoadHours" required min="0.5" step="0.5" placeholder="e.g., 12.5">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select id="facultyLoadStatus" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="facultyNotes" placeholder="Additional notes" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); border-radius: 0.375rem; font-family: inherit;"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary">Add Faculty Load</button>
                    <button type="button" onclick="closeAddFacultyLoadModal()" class="action-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div id="addTeacherModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Teacher</h2>
                <button onclick="closeAddTeacherModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="addTeacherForm">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" id="teacherName" required placeholder="e.g., Maria Santos">
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" id="teacherFirstName" placeholder="First name">
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" id="teacherLastName" placeholder="Last name">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="teacherEmail" required placeholder="email@spup.edu.ph">
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" id="teacherPosition" placeholder="e.g., Senior Teacher">
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary">Add Teacher</button>
                    <button type="button" onclick="closeAddTeacherModal()" class="action-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule Event Modal -->
    <div id="scheduleEventModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="eventTitle">Schedule Details</h2>
                <button onclick="closeScheduleModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="editScheduleEventForm">
                <input type="hidden" id="eventScheduleId">
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" id="eventDate" required>
                </div>
                <div class="form-group">
                    <label>Grade/Section</label>
                    <input type="text" id="eventGrade" required>
                </div>
                <div class="form-group">
                    <label>Teacher</label>
                    <input type="text" id="eventTeacher" disabled style="background-color: var(--bg-tertiary); opacity: 0.7;">
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" id="eventSubject" required>
                </div>
                <div class="form-group">
                    <label>Time Start</label>
                    <input type="time" id="eventTimeStart" required>
                </div>
                <div class="form-group">
                    <label>Time End</label>
                    <input type="time" id="eventTimeEnd" required>
                </div>
                <div class="form-group">
                    <label>Room</label>
                    <input type="text" id="eventRoom" placeholder="N/A">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="eventStatus" required>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary">Update Schedule</button>
                    <button type="button" onclick="deleteScheduleEvent()" class="action-btn action-btn-danger">Delete</button>
                    <button type="button" onclick="closeScheduleModal()" class="action-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            overflow-y: auto;
        }
        .modal-content {
            background: var(--bg-secondary);
            padding: 2rem;
            border-radius: 0.75rem;
            width: 90%;
            max-width: 600px;
            margin: auto;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        .modal-close-btn {
            background: none; border: none;
            font-size: 1.5rem; cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.2s;
            padding: 0; line-height: 1;
        }
        .modal-close-btn:hover { color: var(--text-primary); }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }
        .modal-header h2 { color: var(--text-primary); margin: 0; }
        .modal-header button {
            background: none; border: none;
            font-size: 1.5rem; cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.2s;
        }
        .modal-header button:hover { color: var(--text-primary); }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block; margin-bottom: 0.5rem;
            font-weight: 500; color: var(--text-primary);
        }
        .form-group label input[type="checkbox"] {
            margin-right: 0.5rem; cursor: pointer;
            accent-color: var(--green-primary);
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            color: var(--text-primary);
            border-radius: 0.375rem;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--green-primary);
            box-shadow: 0 0 0 3px rgba(45, 122, 80, 0.1);
        }
        .form-group input:disabled,
        .form-group select:disabled {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }
        .form-actions { display: flex; gap: 1rem; margin-top: 2rem; }
        .form-actions button { flex: 1; }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-available  { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-unavailable{ background: rgba(239, 68, 68, 0.1);   color: #ef4444; }
        .badge-maintenance{ background: rgba(245, 158, 11, 0.1);  color: #f59e0b; }
        .badge-active     { background: rgba(16, 185, 129, 0.1);  color: #10b981; }
        .badge-inactive   { background: var(--bg-tertiary);        color: var(--text-secondary); }
        .badge-overload   { background: rgba(239, 68, 68, 0.1);   color: #ef4444; }
        /* Calendar layout */
        .calendar-card { background: var(--bg-secondary, #fff); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; padding: 1.5rem; margin-bottom: 2rem; }
        .calendar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .calendar-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin: 0; }
        .month-nav { display: flex; gap: 0.5rem; }
        .month-btn { padding: 0.5rem 1rem; background: transparent; border: 1px solid var(--border-color); cursor: pointer; border-radius: 0.375rem; transition: all 0.2s; font-weight: 500; color: var(--text-primary); }
        .month-btn:hover { background: rgba(45,122,80,0.1); color: var(--green-primary); border-color: var(--green-primary); }
        .weekdays { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; margin-bottom: 0.75rem; }
        .weekday { text-align: center; font-weight: 600; color: var(--text-secondary); padding: 0.5rem; font-size: 0.875rem; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.5rem; }
        .calendar-day {
            min-height: 100px;
            border: 1px solid var(--border-color);
            padding: 0.75rem 0.5rem;
            background: var(--bg-secondary);
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: var(--text-primary);
        }
        .calendar-day:hover { background: rgba(45,122,80,0.05); border-color: var(--green-primary); }
        .calendar-day.other-month { background: var(--bg-tertiary); color: var(--text-secondary); border-color: var(--border-color); }
        .calendar-day.today { background: rgba(45, 122, 80, 0.15); border-color: var(--green-primary); }
        .day-number { font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem; color: var(--text-primary); }
        .calendar-day.other-month .day-number { color: var(--text-secondary); }
        .schedule-event {
            padding: 0.25rem 0.5rem;
            background: linear-gradient(135deg, rgba(45,122,80,0.8) 0%, rgba(45,122,80,0.6) 100%);
            color: white;
            border-radius: 0.25rem;
            font-size: 0.65rem;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s;
            display: block;
        }
        .schedule-event:hover { background: linear-gradient(135deg, rgba(45,122,80,1) 0%, rgba(45,122,80,0.8) 100%); }
        .schedule-event.pending   { background: linear-gradient(135deg, rgba(234,179,8,0.8) 0%, rgba(234,179,8,0.6) 100%); }
        .schedule-event.pending:hover { background: linear-gradient(135deg, rgba(234,179,8,1) 0%, rgba(234,179,8,0.8) 100%); }
        .schedule-event.completed { background: linear-gradient(135deg, rgba(150,150,150,0.8) 0%, rgba(150,150,150,0.6) 100%); }
        .schedule-event.rejected  { background: linear-gradient(135deg, rgba(239,68,68,0.8)  0%, rgba(239,68,68,0.6)  100%); }
        .calendar-day.drag-over   { background: rgba(45,122,80,0.15) !important; outline: 2px dashed var(--green-primary); }
        .schedule-event[draggable="true"] { cursor: grab; }
        .schedule-event[draggable="true"]:active { cursor: grabbing; opacity: 0.8; }
    </style>

    <script>
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function openModal(modalId)  { document.getElementById(modalId).style.display = 'flex'; }
        function closeModal(modalId) { document.getElementById(modalId).style.display = 'none'; }

        function showSuccess(msg) { alert('\u2713 ' + msg); }
        function showError(msg)   { alert('\u2717 ' + msg); }

        let currentDate = new Date();

        function viewScheduleEvent(ev, scheduleId) {
            ev?.stopPropagation();
            const schedule = (window.__dashTimetable?.getAll?.() || []).find(s => s.id === scheduleId);
            if (!schedule) return;

            document.getElementById('eventScheduleId').value = schedule.id;
            document.getElementById('eventTitle').textContent = `${schedule.subject || 'Schedule'} - ${schedule.grade || 'N/A'}`;

            let eventDate = schedule.schedule_date ? String(schedule.schedule_date).substring(0, 10) : '';

            document.getElementById('eventDate').value      = eventDate;
            document.getElementById('eventGrade').value     = schedule.grade_section || schedule.grade || '';
            document.getElementById('eventTeacher').value   = schedule.faculty?.name || 'N/A';
            document.getElementById('eventSubject').value   = schedule.subject || '';
            document.getElementById('eventTimeStart').value = schedule.start_time ? schedule.start_time.substring(0,5) : '';
            document.getElementById('eventTimeEnd').value   = schedule.end_time   ? schedule.end_time.substring(0,5)   : '';
            document.getElementById('eventRoom').value      = schedule.room_id ? 'Room #' + schedule.room_id : '';
            document.getElementById('eventStatus').value    = schedule.status || 'pending';

            openModal('scheduleEventModal');
        }

        function closeScheduleModal() { closeModal('scheduleEventModal'); }

        document.getElementById('editScheduleEventForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('eventScheduleId').value;

            const data = {
                grade:         document.getElementById('eventGrade').value,
                subject:       document.getElementById('eventSubject').value,
                schedule_date: document.getElementById('eventDate').value,
                start_time:    document.getElementById('eventTimeStart').value,
                end_time:      document.getElementById('eventTimeEnd').value,
                status:        document.getElementById('eventStatus').value,
            };
            fetch(`/api/grade-school-admin/schedules/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(() => { showSuccess('Schedule updated successfully'); closeScheduleModal(); window.loadSchedules?.(); })
            .catch(() => showError('Error updating schedule'));
        });

        function deleteScheduleEvent() {
            if (!confirm('Are you sure you want to delete this schedule?')) return;
            const id = document.getElementById('eventScheduleId').value;
            closeScheduleModal();
            if (typeof adminRemoveScheduleInstant === 'function') adminRemoveScheduleInstant(id);
            const api = (typeof adminScheduleDeleteApi === 'function')
                ? adminScheduleDeleteApi(id, { url: '/api/grade-school-admin/schedules/' + id })
                : fetch(`/api/grade-school-admin/schedules/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token }
                }).then(r => r.json());
            api.then(() => showSuccess('Schedule deleted'))
                .catch(() => {
                    showError('Error deleting schedule');
                    window.__dashTimetable?.reload?.();
                });
        }

        // ─── Faculty Loads ─────────────────────────────────────────────────────────
        function loadFacultyLoads() {
            fetch('/api/grade-school-admin/faculty-loads')
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    const loads    = data.data || data || [];
                    const active   = loads.filter(l => l.status === 'active').length;
                    const avail    = loads.filter(l => l.status === 'part-time' || l.status === 'available').length;
                    const overload = loads.filter(l => l.status === 'overload' || l.status === 'overloaded').length;

                    document.getElementById('faculty-total').textContent          = loads.length;
                    document.getElementById('faculty-active-count').textContent   = active;
                    document.getElementById('faculty-avail-count').textContent    = avail;
                    document.getElementById('faculty-overload-count').textContent = overload;
                })
                .catch(() => {
                    console.error('Error loading faculty loads');
                });
        }

        // ─── Rooms ─────────────────────────────────────────────────────────────────
        function loadRooms() {
            fetch('/api/grade-school-admin/rooms')
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    const rooms = data.data || data || [];
                    const avail = rooms.filter(r => r.status === 'available').length;
                    const inUse = rooms.filter(r => r.status === 'in-use' || r.status === 'in use').length;
                    const maint = rooms.filter(r => r.status === 'maintenance').length;

                    document.getElementById('rooms-total').textContent       = rooms.length;
                    document.getElementById('rooms-avail-count').textContent = avail;
                    document.getElementById('rooms-inuse-count').textContent = inUse;
                    document.getElementById('rooms-maint-count').textContent = maint;
                })
                .catch(() => {
                    console.error('Error loading rooms');
                });
        }

        // ─── Teachers ──────────────────────────────────────────────────────────────
        function loadTeachers() {
            fetch('/api/grade-school-admin/teachers')
                .then(r => { if (!r.ok) throw new Error(); return r.json(); })
                .then(data => {
                    const teachers = data.data || data || [];
                    const active   = teachers.filter(t => t.is_active === true  || t.is_active === 1).length;
                    const inactive = teachers.filter(t => !t.is_active || t.is_active === false || t.is_active === 0).length;

                    document.getElementById('teachers-total').textContent          = teachers.length;
                    document.getElementById('teachers-active-count').textContent   = active;
                    document.getElementById('teachers-inactive-count').textContent = inactive;
                })
                .catch(() => {
                    console.error('Error loading teachers');
                });
        }
        

        // ─── Edit Faculty Load ─────────────────────────────────────────────────────
        function editFacultyModal(id, classes, hours, status) {
            document.getElementById('facultyId').value       = id;
            document.getElementById('classesAssigned').value = classes;
            document.getElementById('loadHours').value       = hours;
            document.getElementById('facultyStatus').value   = status;
            openModal('editFacultyModal');
        }

        document.getElementById('editFacultyForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const id   = document.getElementById('facultyId').value;
            const data = {
                classes_assigned: document.getElementById('classesAssigned').value,
                load_hours:       document.getElementById('loadHours').value,
                status:           document.getElementById('facultyStatus').value,
            };
            fetch(`/api/grade-school-admin/faculty-loads/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(() => { showSuccess('Faculty load updated'); closeModal('editFacultyModal'); loadFacultyLoads(); })
            .catch(() => showError('Error updating faculty load'));
        });

        // ─── Add Room ──────────────────────────────────────────────────────────────
        function openAddRoomModal()  { document.getElementById('addRoomForm').reset();  document.getElementById('addRoomModal').style.display = 'flex'; }
        function closeAddRoomModal() { document.getElementById('addRoomModal').style.display = 'none'; }

        document.getElementById('addRoomForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                capacity:       parseInt(document.getElementById('roomCapacity').value),
                status:         document.getElementById('roomStatus').value,
                school_level:   'grade_school',
            };
            fetch('/api/grade-school-admin/rooms', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) { showSuccess('Room added successfully'); closeAddRoomModal(); loadRooms(); }
                else { const msg = res.errors ? Object.values(res.errors).flat().join(', ') : res.message; showError(msg || 'Error adding room'); }
            })
            .catch(() => showError('Error adding room'));
        });

        // ─── Add Faculty Load ──────────────────────────────────────────────────────
        function openAddFacultyLoadModal() {
            document.getElementById('addFacultyLoadForm').reset();
            document.getElementById('addFacultyLoadModal').style.display = 'flex';
            fetch('/api/grade-school-admin/teachers')
                .then(r => r.json())
                .then(data => {
                    const select = document.getElementById('facultySelectId');
                    select.innerHTML = '<option value="">Select a faculty member</option>';
                    (data.data || data || []).forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id; opt.textContent = t.name;
                        select.appendChild(opt);
                    });
                })
                .catch(err => console.error('Error loading teachers:', err));
        }

        function refreshFacultyDropdown() {
            fetch('/api/grade-school-admin/teachers')
                .then(r => r.json())
                .then(data => {
                    const select = document.getElementById('facultySelectId');
                    if (select) {
                        const currentValue = select.value;
                        select.innerHTML = '<option value="">Select a faculty member</option>';
                        (data.data || data || []).forEach(t => {
                            const opt = document.createElement('option');
                            opt.value = t.id; opt.textContent = t.name;
                            select.appendChild(opt);
                        });
                        select.value = currentValue;
                    }
                })
                .catch(err => console.error('Error refreshing teachers:', err));
        }
        function closeAddFacultyLoadModal() { document.getElementById('addFacultyLoadModal').style.display = 'none'; }

        document.getElementById('addFacultyLoadForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                faculty_id:       document.getElementById('facultySelectId').value,
                grade_level:      document.getElementById('facultyGradeLevel').value,
                department:       document.getElementById('facultyDepartment').value,
                classes_assigned: parseInt(document.getElementById('facultyClasses').value),
                load_hours:       parseFloat(document.getElementById('facultyLoadHours').value),
                status:           document.getElementById('facultyLoadStatus').value,
                notes:            document.getElementById('facultyNotes').value,
                school_level:     'grade_school',
            };
            fetch('/api/grade-school-admin/faculty-loads', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) { showSuccess('Faculty load added'); closeAddFacultyLoadModal(); loadFacultyLoads(); }
                else { const msg = res.errors ? Object.values(res.errors).flat().join(', ') : res.message; showError(msg || 'Error adding faculty load'); }
            })
            .catch(() => showError('Error adding faculty load'));
        });

        // ─── Add Teacher ───────────────────────────────────────────────────────────
        function openAddTeacherModal()  { document.getElementById('addTeacherForm').reset();  document.getElementById('addTeacherModal').style.display = 'flex'; }
        function closeAddTeacherModal() { document.getElementById('addTeacherModal').style.display = 'none'; }

        document.getElementById('addTeacherForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                name:         document.getElementById('teacherName').value,
                first_name:   document.getElementById('teacherFirstName').value,
                last_name:    document.getElementById('teacherLastName').value,
                email:        document.getElementById('teacherEmail').value,
                position:     document.getElementById('teacherPosition').value,
                school_level: 'grade_school',
            };
            fetch('/api/grade-school-admin/teachers', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) { showSuccess('Teacher added successfully'); closeAddTeacherModal(); loadTeachers(); refreshFacultyDropdown(); }
                else { const msg = res.errors ? Object.values(res.errors).flat().join(', ') : res.message; showError(msg || 'Error adding teacher'); }
            })
            .catch(() => showError('Error adding teacher'));
        });

        // ─── Initialise ────────────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', function () {
            window.loadSchedules?.();
            loadFacultyLoads();
            loadRooms();
            loadTeachers();
            setInterval(() => {
                window.loadSchedules?.();
                loadFacultyLoads();
                loadTeachers();
                loadRooms();
            }, 30000);
        });

        // Reload when user navigates back (browser back button / bfcache)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.loadSchedules?.();
                loadFacultyLoads();
                loadRooms();
                loadTeachers();
            }
        });
    </script>
@endsection
