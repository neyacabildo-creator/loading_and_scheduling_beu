{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.teacher')

@section('title', 'Dashboard')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <button class="search-btn">
                <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <h1 class="page-title">Dashboard</h1>
        </div>
        <div class="header-right">
            <button class="header-btn" style="position: relative;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span class="notification-dot"></span>
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div>
                <p class="stat-label">Total Faculty</p>
                <p class="stat-value">{{ $totalFaculty ?? 45 }}</p>
                <p class="stat-change">+3 this semester</p>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-label">Active Schedules</p>
                <p class="stat-value">{{ $activeSchedules ?? 126 }}</p>
                <p class="stat-change">+12 this week</p>
            </div>
        </div>
        <div class="stat-card">
            <div>
                <p class="stat-label">Pending Approvals</p>
                <p class="stat-value">{{ $pendingApprovals ?? 8 }}</p>
                <p class="stat-change">Need attention</p>
            </div>
        </div>
    </div>

    <!-- Class Schedules Section -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">Class Schedules</h2>
        </div>
        <div class="table-tabs">
            <button class="tab-btn active" onclick="switchTab(event.target, 'schedules-all')">All Schedules <span class="count" id="count-all">0</span></button>
            <button class="tab-btn" onclick="switchTab(event.target, 'schedules-pending')">Pending Review <span class="count" id="count-pending">0</span></button>
            <button class="tab-btn" onclick="switchTab(event.target, 'schedules-approval')">Pending Approval <span class="count" id="count-approval">0</span></button>
            <button class="tab-btn" onclick="switchTab(event.target, 'schedules-active')">Active <span class="count" id="count-active">0</span></button>
        </div>

        <!-- All Schedules Tab -->
        <div id="schedules-all" class="tab-content active">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grade/Section</th>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="schedules-all-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">Loading schedules...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pending Review Tab -->
        <div id="schedules-pending" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grade/Section</th>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="schedules-pending-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No pending schedules</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pending Approval Tab -->
        <div id="schedules-approval" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grade/Section</th>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="schedules-approval-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No schedules pending approval</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Active Tab -->
        <div id="schedules-active" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grade/Section</th>
                        <th>Teacher</th>
                        <th>Subject</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="schedules-active-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No active schedules</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Faculty Loads Section -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">Faculty Loads</h2>
        </div>
        <div class="table-tabs">
            <button class="tab-btn active" onclick="switchTab(event.target, 'faculty-all')">All Faculty <span class="count" id="count-faculty-all">0</span></button>
            <button class="tab-btn" onclick="switchTab(event.target, 'faculty-active')">Currently Teaching <span class="count" id="count-faculty-active">0</span></button>
            <button class="tab-btn" onclick="switchTab(event.target, 'faculty-available')">Available <span class="count" id="count-faculty-available">0</span></button>
            <button class="tab-btn" onclick="switchTab(event.target, 'faculty-overload')">Overload <span class="count" id="count-faculty-overload">0</span></button>
        </div>

        <div id="faculty-all" class="tab-content active">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Classes Assigned</th>
                        <th>Load Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="faculty-all-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">Loading faculty loads...</td></tr>
                </tbody>
            </table>
        </div>

        <div id="faculty-active" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Classes Assigned</th>
                        <th>Load Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="faculty-active-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No active faculty loads</td></tr>
                </tbody>
            </table>
        </div>

        <div id="faculty-available" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Available Hours</th>
                        <th>Max Capacity</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="faculty-available-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No available faculty</td></tr>
                </tbody>
            </table>
        </div>

        <div id="faculty-overload" class="tab-content" style="display: none;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Faculty Name</th>
                        <th>Department</th>
                        <th>Assigned Hours</th>
                        <th>Max Hours</th>
                        <th>Excess Hours</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="faculty-overload-body">
                    <tr><td colspan="7" style="text-align: center; padding: 2rem;">No overloaded faculty</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Rooms Management Section -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">Rooms Management</h2>
            <button class="action-btn action-btn-primary" onclick="openAddRoomModal()" style="padding: 0.75rem 1.5rem;">+ Add Room</button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Room Number</th>
                    <th>Building</th>
                    <th>Capacity</th>
                    <th>Features</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="rooms-body">
                <tr><td colspan="7" style="text-align: center; padding: 2rem;">Loading rooms...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Teachers/Faculty Management Section -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">Teachers/Faculty</h2>
            <button class="action-btn action-btn-primary" onclick="openAddTeacherModal()" style="padding: 0.75rem 1.5rem;">+ Add Teacher</button>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="teachers-body">
                <tr><td colspan="6" style="text-align: center; padding: 2rem;">Loading teachers...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editScheduleModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Schedule</h2>
                <button onclick="closeModal('editScheduleModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="editScheduleForm">
                <input type="hidden" id="scheduleId">
                <div class="form-group">
                    <label>Grade/Section</label>
                    <input type="text" id="scheduleGrade" required>
                </div>
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" id="scheduleSubject" required>
                </div>
                <div class="form-group">
                    <label>Time Start</label>
                    <input type="time" id="scheduleTimeStart" required>
                </div>
                <div class="form-group">
                    <label>Time End</label>
                    <input type="time" id="scheduleTimeEnd" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select id="scheduleStatus" required>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary">Update Schedule</button>
                    <button type="button" onclick="closeModal('editScheduleModal')" class="action-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

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
                <button onclick="closeAddRoomModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form id="addRoomForm">
                <div class="form-group">
                    <label>Room Number *</label>
                    <input type="text" id="roomNumber" required placeholder="e.g., A101">
                </div>
                <div class="form-group">
                    <label>Building *</label>
                    <input type="text" id="roomBuilding" required placeholder="e.g., Building A">
                </div>
                <div class="form-group">
                    <label>Capacity *</label>
                    <input type="number" id="roomCapacity" required min="1" max="200" placeholder="Number of seats">
                </div>
                <div class="form-group">
                    <label>Features</label>
                    <input type="text" id="roomFeatures" placeholder="e.g., Smart Board, Air Conditioning">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="roomLab"> Has Laboratory
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="roomProjector"> Has Projector
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="roomAC"> Has Air Conditioning
                    </label>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select id="roomStatus" required>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
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
                    <input type="text" id="teacherName" required placeholder="e.g., John Doe">
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
                    <input type="email" id="teacherEmail" required placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" id="teacherPosition" placeholder="e.g., Senior Teacher">
                </div>
                <div class="form-group">
                    <label>School Level</label>
                    <select id="teacherSchoolLevel">
                        <option value="">Select School Level</option>
                        <option value="Elementary">Elementary</option>
                        <option value="Junior High">Junior High</option>
                        <option value="Senior High">Senior High</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="action-btn action-btn-primary">Add Teacher</button>
                    <button type="button" onclick="closeAddTeacherModal()" class="action-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.75rem;
            min-width: 400px;
            max-width: 600px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 0.375rem;
            font-size: 1rem;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        .form-actions button {
            flex: 1;
        }
    </style>

    <script>
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function switchTab(btn, tabId) {
            const tabsContainer = btn.parentElement;
            tabsContainer.querySelectorAll('.tab-btn').forEach(button => {
                button.classList.remove('active');
            });
            btn.classList.add('active');

            const container = tabsContainer.parentElement;
            container.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });

            const selectedTab = document.getElementById(tabId);
            if (selectedTab) {
                selectedTab.style.display = 'block';
            }
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function showSuccess(message) {
            alert(' ' + message);
        }

        function showError(message) {
            alert(' ' + message);
        }

        // Load all schedules
        function loadSchedules() {
            fetch('/api/admin/schedules')
                .then(response => response.json())
                .then(data => {
                    const schedules = data.data || data || [];
                    const all = [];
                    const pending = [];
                    const approval = [];
                    const active = [];

                    schedules.forEach(schedule => {
                        const row = `
                            <tr>
                                <td>#SC${String(schedule.id).padStart(3, '0')}</td>
                                <td><strong>${schedule.grade || 'N/A'}</strong></td>
                                <td>${schedule.faculty?.name || 'N/A'}</td>
                                <td>${schedule.subject || 'N/A'}</td>
                                <td>${schedule.time_start || 'N/A'} - ${schedule.time_end || 'N/A'}</td>
                                <td><span class="badge badge-${schedule.status}">${schedule.status || 'pending'}</span></td>
                                <td>
                                    ${schedule.admin_approved === false ? `
                                        <button class="action-btn action-btn-primary" onclick="approveSchedule(${schedule.id})">Approve</button>
                                        <button class="action-btn action-btn-danger" onclick="rejectSchedule(${schedule.id})">Reject</button>
                                    ` : `
                                        <button class="action-btn" onclick="editScheduleModal(${schedule.id}, '${schedule.grade}', '${schedule.subject}', '${schedule.time_start}', '${schedule.time_end}', '${schedule.status}')">Edit</button>
                                        <button class="action-btn action-btn-danger" onclick="deleteSchedule(${schedule.id})">Delete</button>
                                    `}
                                </td>
                            </tr>
                        `;
                        all.push(row);

                        if (schedule.status === 'pending' && !schedule.admin_approved) {
                            pending.push(row);
                        }
                        if (!schedule.admin_approved) {
                            approval.push(row);
                        }
                        if (schedule.status === 'active') {
                            active.push(row);
                        }
                    });

                    document.getElementById('schedules-all-body').innerHTML = all.length ? all.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No schedules found</td></tr>';
                    document.getElementById('schedules-pending-body').innerHTML = pending.length ? pending.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No pending schedules</td></tr>';
                    document.getElementById('schedules-approval-body').innerHTML = approval.length ? approval.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No schedules pending approval</td></tr>';
                    document.getElementById('schedules-active-body').innerHTML = active.length ? active.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No active schedules</td></tr>';

                    document.getElementById('count-all').textContent = all.length;
                    document.getElementById('count-pending').textContent = pending.length;
                    document.getElementById('count-approval').textContent = approval.length;
                    document.getElementById('count-active').textContent = active.length;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('schedules-all-body').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">Error loading schedules</td></tr>';
                });
        }

        function approveSchedule(scheduleId) {
            if (confirm('Are you sure you want to approve this schedule?')) {
                fetch(`/api/admin/schedules/${scheduleId}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    showSuccess('Schedule approved successfully');
                    loadSchedules();
                })
                .catch(error => showError('Error approving schedule'));
            }
        }

        function rejectSchedule(scheduleId) {
            if (confirm('Are you sure you want to reject this schedule?')) {
                fetch(`/api/admin/schedules/${scheduleId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    showSuccess('Schedule rejected');
                    loadSchedules();
                })
                .catch(error => showError('Error rejecting schedule'));
            }
        }

        function editScheduleModal(id, grade, subject, timeStart, timeEnd, status) {
            document.getElementById('scheduleId').value = id;
            document.getElementById('scheduleGrade').value = grade;
            document.getElementById('scheduleSubject').value = subject;
            document.getElementById('scheduleTimeStart').value = timeStart;
            document.getElementById('scheduleTimeEnd').value = timeEnd;
            document.getElementById('scheduleStatus').value = status;
            openModal('editScheduleModal');
        }

        document.getElementById('editScheduleForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('scheduleId').value;
            const data = {
                grade: document.getElementById('scheduleGrade').value,
                subject: document.getElementById('scheduleSubject').value,
                time_start: document.getElementById('scheduleTimeStart').value,
                time_end: document.getElementById('scheduleTimeEnd').value,
                status: document.getElementById('scheduleStatus').value,
            };

            fetch(`/api/admin/schedules/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                showSuccess('Schedule updated successfully');
                closeModal('editScheduleModal');
                loadSchedules();
            })
            .catch(error => showError('Error updating schedule'));
        });

        function deleteSchedule(scheduleId) {
            if (confirm('Are you sure you want to delete this schedule?')) {
                fetch(`/api/admin/schedules/${scheduleId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showSuccess('Schedule deleted successfully');
                    loadSchedules();
                })
                .catch(error => showError('Error deleting schedule'));
            }
        }

        // Load faculty loads
        function loadFacultyLoads() {
            fetch('/api/faculty-loads')
                .then(response => response.json())
                .then(data => {
                    const loads = data.data || data || [];
                    const all = [];
                    const active = [];
                    const available = [];
                    const overload = [];

                    loads.forEach((load, idx) => {
                        const row = `
                            <tr>
                                <td>${idx + 1}</td>
                                <td><strong>${load.faculty?.name || 'N/A'}</strong></td>
                                <td>${load.department || 'N/A'}</td>
                                <td>${load.classes_assigned || 0}</td>
                                <td>${load.load_hours || 0} hrs</td>
                                <td><span class="badge badge-${load.status}">${load.status || 'active'}</span></td>
                                <td>
                                    <button class="action-btn" onclick="editFacultyModal(${load.id}, ${load.classes_assigned}, ${load.load_hours}, '${load.status}')">Edit</button>
                                    <button class="action-btn action-btn-danger" onclick="deleteFacultyLoad(${load.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                        all.push(row);

                        if (load.status === 'active') {
                            active.push(row);
                        }
                        if (load.status === 'available') {
                            available.push(row);
                        }
                        if (load.status === 'overload') {
                            overload.push(row);
                        }
                    });

                    document.getElementById('faculty-all-body').innerHTML = all.length ? all.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No faculty loads found</td></tr>';
                    document.getElementById('faculty-active-body').innerHTML = active.length ? active.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No active faculty</td></tr>';
                    document.getElementById('faculty-available-body').innerHTML = available.length ? available.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No available faculty</td></tr>';
                    document.getElementById('faculty-overload-body').innerHTML = overload.length ? overload.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No overloaded faculty</td></tr>';

                    document.getElementById('count-faculty-all').textContent = all.length;
                    document.getElementById('count-faculty-active').textContent = active.length;
                    document.getElementById('count-faculty-available').textContent = available.length;
                    document.getElementById('count-faculty-overload').textContent = overload.length;
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('faculty-all-body').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">Error loading faculty loads</td></tr>';
                });
        }

        function editFacultyModal(id, classes, hours, status) {
            document.getElementById('facultyId').value = id;
            document.getElementById('classesAssigned').value = classes;
            document.getElementById('loadHours').value = hours;
            document.getElementById('facultyStatus').value = status;
            openModal('editFacultyModal');
        }

        document.getElementById('editFacultyForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const id = document.getElementById('facultyId').value;
            const data = {
                classes_assigned: document.getElementById('classesAssigned').value,
                load_hours: document.getElementById('loadHours').value,
                status: document.getElementById('facultyStatus').value,
            };

            fetch(`/api/faculty-loads/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                showSuccess('Faculty load updated successfully');
                closeModal('editFacultyModal');
                loadFacultyLoads();
            })
            .catch(error => showError('Error updating faculty load'));
        });

        function deleteFacultyLoad(id) {
            if (confirm('Are you sure you want to delete this faculty load?')) {
                fetch(`/api/faculty-loads/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showSuccess('Faculty load deleted successfully');
                    loadFacultyLoads();
                })
                .catch(error => showError('Error deleting faculty load'));
            }
        }

        // Load rooms
        function loadRooms() {
            fetch('/api/rooms')
                .then(response => response.json())
                .then(data => {
                    const rooms = data.data || data || [];
                    const rows = [];

                    rooms.forEach((room, idx) => {
                        const features = [];
                        if (room.has_laboratory) features.push('Lab');
                        if (room.has_projector) features.push('Projector');
                        if (room.has_ac) features.push('AC');
                        
                        const row = `
                            <tr>
                                <td>${idx + 1}</td>
                                <td><strong>${room.room_number || 'N/A'}</strong></td>
                                <td>${room.building || 'N/A'}</td>
                                <td>${room.capacity || 0}</td>
                                <td>${features.length > 0 ? features.join(', ') : 'None'}</td>
                                <td><span class="badge badge-${room.status}">${room.status || 'available'}</span></td>
                                <td>
                                    <button class="action-btn" onclick="editRoomModal(${room.id}, '${room.room_number}', '${room.building}', ${room.capacity}, '${room.features || ''}', ${room.has_laboratory || false}, ${room.has_projector || false}, ${room.has_ac || false}, '${room.status}')">Edit</button>
                                    <button class="action-btn action-btn-danger" onclick="deleteRoom(${room.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                        rows.push(row);
                    });

                    document.getElementById('rooms-body').innerHTML = rows.length ? rows.join('') : '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No rooms found</td></tr>';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('rooms-body').innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">Error loading rooms</td></tr>';
                });
        }

        // Load teachers
        function loadTeachers() {
            fetch('/api/teachers')
                .then(response => response.json())
                .then(data => {
                    const teachers = data.data || data || [];
                    const rows = [];

                    teachers.forEach((teacher, idx) => {
                        const row = `
                            <tr>
                                <td>${idx + 1}</td>
                                <td><strong>${teacher.name || 'N/A'}</strong></td>
                                <td>${teacher.email || 'N/A'}</td>
                                <td>${teacher.position || 'N/A'}</td>
                                <td><span class="badge badge-${teacher.is_active ? 'active' : 'inactive'}">${teacher.is_active ? 'Active' : 'Inactive'}</span></td>
                                <td>
                                    <button class="action-btn" onclick="editTeacherModal(${teacher.id}, '${teacher.name}', '${teacher.email}', '${teacher.position || ''}', '${teacher.school_level || ''}')">Edit</button>
                                    <button class="action-btn action-btn-danger" onclick="deleteTeacher(${teacher.id})">Delete</button>
                                </td>
                            </tr>
                        `;
                        rows.push(row);
                    });

                    document.getElementById('teachers-body').innerHTML = rows.length ? rows.join('') : '<tr><td colspan="6" style="text-align: center; padding: 2rem;">No teachers found</td></tr>';
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('teachers-body').innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 2rem;">Error loading teachers</td></tr>';
                });
        }

        // Add room modal
        function openAddRoomModal() {
            document.getElementById('addRoomForm').reset();
            document.getElementById('addRoomModal').style.display = 'block';
        }

        function closeAddRoomModal() {
            document.getElementById('addRoomModal').style.display = 'none';
        }

        document.getElementById('addRoomForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                room_number: document.getElementById('roomNumber').value,
                building: document.getElementById('roomBuilding').value,
                capacity: parseInt(document.getElementById('roomCapacity').value),
                features: document.getElementById('roomFeatures').value,
                has_laboratory: document.getElementById('roomLab').checked,
                has_projector: document.getElementById('roomProjector').checked,
                has_ac: document.getElementById('roomAC').checked,
                status: document.getElementById('roomStatus').value,
            };

            fetch('/api/rooms', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                showSuccess('Room added successfully');
                closeAddRoomModal();
                loadRooms();
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error adding room');
            });
        });

        function deleteRoom(id) {
            if (confirm('Are you sure you want to delete this room?')) {
                fetch(`/api/rooms/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showSuccess('Room deleted successfully');
                    loadRooms();
                })
                .catch(error => showError('Error deleting room'));
            }
        }

        // Add teacher modal
        function openAddTeacherModal() {
            document.getElementById('addTeacherForm').reset();
            document.getElementById('addTeacherModal').style.display = 'block';
        }

        function closeAddTeacherModal() {
            document.getElementById('addTeacherModal').style.display = 'none';
        }

        document.getElementById('addTeacherForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                name: document.getElementById('teacherName').value,
                first_name: document.getElementById('teacherFirstName').value,
                last_name: document.getElementById('teacherLastName').value,
                email: document.getElementById('teacherEmail').value,
                position: document.getElementById('teacherPosition').value,
                school_level: document.getElementById('teacherSchoolLevel').value,
            };

            fetch('/api/teachers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                showSuccess('Teacher added successfully');
                closeAddTeacherModal();
                loadTeachers();
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Error adding teacher');
            });
        });

        function deleteTeacher(id) {
            if (confirm('Are you sure you want to delete this teacher?')) {
                fetch(`/api/teachers/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => response.json())
                .then(data => {
                    showSuccess('Teacher deleted successfully');
                    loadTeachers();
                })
                .catch(error => showError('Error deleting teacher'));
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSchedules();
            loadFacultyLoads();
            loadRooms();
            loadTeachers();
        });
    </script>
@endsection
