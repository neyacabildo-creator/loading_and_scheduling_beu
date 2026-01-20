{{-- resources/views/admin/class-schedule.blade.php --}}
@extends('layouts.admin')

@section('title', 'Class Schedule')

@section('content')
    <style>
        .schedule-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .schedule-nav { display: flex; gap: 1rem; }
        .schedule-btn { padding: 0.5rem 1rem; border: 1px solid #e8dcc8; background: white; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; }
        .schedule-btn:hover, .schedule-btn.active { background: #2d7a50; color: white; border-color: #2d7a50; }
        
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
        
        /* Rooms Section */
        .rooms-section { margin-top: 2rem; }
        .rooms-title { font-size: 1.25rem; font-weight: 600; color: #2d3436; margin-bottom: 1rem; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; }
        .room-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
        .room-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .room-name { font-size: 1.125rem; font-weight: 600; color: #2d3436; }
        .room-status { display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .room-status.active { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .room-status.inactive { background: rgba(150,150,150,0.15); color: #6b6b6b; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; }
        .room-info { display: flex; flex-direction: column; gap: 0.75rem; }
        .room-info-item { font-size: 0.875rem; color: #7a7a6e; }
        .room-info-label { font-weight: 500; color: #2d3436; }
        .room-schedule { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e8dcc8; }
        .schedule-item { display: flex; justify-content: space-between; align-items: center; padding: 0.5rem 0; font-size: 0.875rem; }
        .schedule-time { color: #7a7a6e; font-weight: 500; }
        .schedule-subject { color: #2d3436; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <button class="search-btn">
                <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <h1 class="page-title">Class Schedule Management</h1>
        </div>
        <div class="header-right">
            <a href="{{ route('admin.schedule.create') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem; transition: all 0.2s;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Create Schedule
            </a>
        </div>
    </div>

    <!-- Pending Schedules for Review -->
    <div class="table-card" style="margin-bottom: 2rem;">
        <div class="table-header">
            <h2 class="table-title">Pending Schedules for Approval</h2>
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
                        <th>Time</th>
                        <th>Room</th>
                        <th>Students</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="pendingTableBody">
                    <tr>
                        <td colspan="10" style="text-align: center; padding: 2rem;">Loading schedules...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Approved Schedules -->
    <div class="table-card" style="margin-bottom: 2rem;">
        <div class="table-header">
            <h2 class="table-title">Approved Schedules</h2>
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
                        <th>Time</th>
                        <th>Room</th>
                        <th>Students</th>
                        <th>Approved</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="approvedTableBody">
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 2rem;">Loading schedules...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Approve/Reject -->
    <div id="approvalModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 0.75rem; padding: 2rem; max-width: 500px; width: 90%; box-shadow: 0 20px 25px rgba(0,0,0,0.15);">
            <h3 id="modalTitle" style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: #2d3436;"></h3>
            <div id="scheduleDetails" style="background: #f5f3ed; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; font-size: 0.875rem;"></div>
            <div id="rejectReasonSection" style="display: none; margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3436;">Reason:</label>
                <textarea id="rejectReason" style="width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem; resize: vertical; min-height: 100px;" placeholder="Enter reason for rejection..."></textarea>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button id="confirmBtn" style="flex: 1; padding: 0.75rem; background: #2d7a50; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">Confirm</button>
                <button onclick="closeModal()" style="flex: 1; padding: 0.75rem; background: #e8dcc8; color: #2d3436; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Modal for Edit -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; overflow-y: auto;">
        <div style="background: white; border-radius: 0.75rem; padding: 2rem; max-width: 600px; width: 90%; box-shadow: 0 20px 25px rgba(0,0,0,0.15); margin: 2rem auto;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: #2d3436;">Edit Schedule</h3>
            <form id="editForm">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3436;">Subject:</label>
                    <input id="editSubject" type="text" style="width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem;" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3436;">Grade/Section:</label>
                    <input id="editGradeSection" type="text" style="width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem;" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3436;">Day:</label>
                    <select id="editDay" style="width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem;" required>
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3436;">Start Time:</label>
                        <input id="editStartTime" type="time" style="width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem;" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3436;">End Time:</label>
                        <input id="editEndTime" type="time" style="width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem;" required>
                    </div>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #2d3436;">Student Count:</label>
                    <input id="editStudentCount" type="number" min="1" style="width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem;">
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" style="flex: 1; padding: 0.75rem; background: #2d7a50; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">Save Changes</button>
                    <button type="button" onclick="closeEditModal()" style="flex: 1; padding: 0.75rem; background: #e8dcc8; color: #2d3436; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .action-btn {
            padding: 0.5rem 0.75rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        .action-btn.approve {
            background: rgba(45, 122, 80, 0.15);
            color: #2d7a50;
        }
        .action-btn.approve:hover {
            background: #2d7a50;
            color: white;
        }
        .action-btn.edit {
            background: rgba(100, 100, 100, 0.15);
            color: #666;
        }
        .action-btn.edit:hover {
            background: #666;
            color: white;
        }
        .action-btn.delete {
            background: rgba(200, 50, 50, 0.15);
            color: #c83232;
        }
        .action-btn.delete:hover {
            background: #c83232;
            color: white;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
    </style>

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
                <div class="schedule-event">Math - G5A (09:00)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">7</div>
                <div class="schedule-event">English - G6B (08:30)</div>
                <div class="schedule-event">Science - G7A (10:45)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">8</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">9</div>
                <div class="schedule-event">Filipino - G8C (11:00)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">10</div>
            </div>
            <div class="calendar-day today">
                <div class="day-number">11</div>
                <div class="schedule-event">Math - G5A (09:00)</div>
                <div class="schedule-event">PE - G6B (14:00)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">12</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">13</div>
                <div class="schedule-event">Science - G7A (10:45)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">14</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">15</div>
                <div class="schedule-event pending">Review - Pending</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">16</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">17</div>
                <div class="schedule-event">English - G6B (08:30)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">18</div>
                <div class="schedule-event">Math - G5A (09:00)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">19</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">20</div>
                <div class="schedule-event">Science - G7A (10:45)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">21</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">22</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">23</div>
                <div class="schedule-event">Filipino - G8C (11:00)</div>
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
                <div class="schedule-event">Math - G5A (09:00)</div>
            </div>
            <div class="calendar-day">
                <div class="day-number">28</div>
                <div class="schedule-event">English - G6B (08:30)</div>
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

    <!-- Active Rooms Section -->
    <div class="rooms-section">
        <h2 class="rooms-title">Active Rooms & Schedules</h2>
        <div class="rooms-grid">
            <!-- Room 1 -->
            <div class="room-card">
                <div class="room-header">
                    <div class="room-name">Room 101</div>
                    <div class="room-status active">
                        <div class="status-dot" style="background: #2d7a50;"></div>
                        Active
                    </div>
                </div>
                <div class="room-info">
                    <div class="room-info-item">
                        <div class="room-info-label">Building:</div>
                        <div>Main Building - 1st Floor</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Capacity:</div>
                        <div>45 students</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Facilities:</div>
                        <div>Projector, AC, White Board</div>
                    </div>
                </div>
                <div class="room-schedule">
                    <div style="font-weight: 600; margin-bottom: 0.75rem; color: #2d3436;">Today's Schedule:</div>
                    <div class="schedule-item">
                        <span class="schedule-time">08:30 - 09:30</span>
                        <span class="schedule-subject">Math (G5A)</span>
                    </div>
                    <div class="schedule-item">
                        <span class="schedule-time">10:00 - 11:00</span>
                        <span class="schedule-subject">English (G6B)</span>
                    </div>
                    <div class="schedule-item">
                        <span class="schedule-time">14:00 - 15:00</span>
                        <span class="schedule-subject">PE (G6B)</span>
                    </div>
                </div>
            </div>

            <!-- Room 2 -->
            <div class="room-card">
                <div class="room-header">
                    <div class="room-name">Room 102</div>
                    <div class="room-status active">
                        <div class="status-dot" style="background: #2d7a50;"></div>
                        Active
                    </div>
                </div>
                <div class="room-info">
                    <div class="room-info-item">
                        <div class="room-info-label">Building:</div>
                        <div>Main Building - 1st Floor</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Capacity:</div>
                        <div>50 students</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Facilities:</div>
                        <div>Smart Board, AC, Lab Equipment</div>
                    </div>
                </div>
                <div class="room-schedule">
                    <div style="font-weight: 600; margin-bottom: 0.75rem; color: #2d3436;">Today's Schedule:</div>
                    <div class="schedule-item">
                        <span class="schedule-time">09:00 - 10:00</span>
                        <span class="schedule-subject">Science (G7A)</span>
                    </div>
                    <div class="schedule-item">
                        <span class="schedule-time">10:45 - 11:45</span>
                        <span class="schedule-subject">Science Lab (G7B)</span>
                    </div>
                </div>
            </div>

            <!-- Room 3 -->
            <div class="room-card">
                <div class="room-header">
                    <div class="room-name">Room 201</div>
                    <div class="room-status active">
                        <div class="status-dot" style="background: #2d7a50;"></div>
                        Active
                    </div>
                </div>
                <div class="room-info">
                    <div class="room-info-item">
                        <div class="room-info-label">Building:</div>
                        <div>Main Building - 2nd Floor</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Capacity:</div>
                        <div>40 students</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Facilities:</div>
                        <div>Projector, AC, Digital Board</div>
                    </div>
                </div>
                <div class="room-schedule">
                    <div style="font-weight: 600; margin-bottom: 0.75rem; color: #2d3436;">Today's Schedule:</div>
                    <div class="schedule-item">
                        <span class="schedule-time">11:00 - 12:00</span>
                        <span class="schedule-subject">Filipino (G8C)</span>
                    </div>
                    <div class="schedule-item">
                        <span class="schedule-time">13:00 - 14:00</span>
                        <span class="schedule-subject">History (G8A)</span>
                    </div>
                </div>
            </div>

            <!-- Room 4 -->
            <div class="room-card">
                <div class="room-header">
                    <div class="room-name">Room 202</div>
                    <div class="room-status inactive">
                        <div class="status-dot" style="background: #999;"></div>
                        Inactive
                    </div>
                </div>
                <div class="room-info">
                    <div class="room-info-item">
                        <div class="room-info-label">Building:</div>
                        <div>Main Building - 2nd Floor</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Capacity:</div>
                        <div>35 students</div>
                    </div>
                    <div class="room-info-item">
                        <div class="room-info-label">Facilities:</div>
                        <div>Projector, AC</div>
                    </div>
                </div>
                <div class="room-schedule">
                    <div style="font-weight: 600; margin-bottom: 0.75rem; color: #7a7a6e;">No schedules today</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentScheduleId = null;
        let currentAction = null;

        // Load schedules on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPendingSchedules();
            loadApprovedSchedules();
            // Refresh every 30 seconds
            setInterval(() => {
                loadPendingSchedules();
                loadApprovedSchedules();
            }, 30000);
        });

        // Load pending schedules
        function loadPendingSchedules() {
            fetch('{{ route("admin.schedules.pending") }}', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('pendingTableBody');
                if (data.data && data.data.length > 0) {
                    tbody.innerHTML = data.data.map(schedule => `
                        <tr>
                            <td class="id-badge">#${schedule.id}</td>
                            <td>${schedule.faculty.name}</td>
                            <td>${schedule.subject}</td>
                            <td>${schedule.grade_section}</td>
                            <td>${schedule.day_of_week}</td>
                            <td>${schedule.start_time} - ${schedule.end_time}</td>
                            <td>${schedule.room?.room_number || 'N/A'}</td>
                            <td>${schedule.student_count || 0}</td>
                            <td>${new Date(schedule.created_at).toLocaleDateString()}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn approve" onclick="openApprovalModal(${schedule.id}, 'approve')">Approve</button>
                                    <button class="action-btn edit" onclick="openEditModal(${schedule.id})">Edit</button>
                                    <button class="action-btn delete" onclick="openApprovalModal(${schedule.id}, 'reject')">Reject</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="10" style="text-align: center; padding: 2rem;">No pending schedules</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading pending schedules:', error);
                document.getElementById('pendingTableBody').innerHTML = '<tr><td colspan="10" style="text-align: center; color: red;">Error loading schedules</td></tr>';
            });
        }

        // Load approved schedules
        function loadApprovedSchedules() {
            fetch('{{ route("admin.schedules.index") }}', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('approvedTableBody');
                const approved = data.data.filter(s => s.admin_approved && s.status === 'active');
                if (approved.length > 0) {
                    tbody.innerHTML = approved.map(schedule => `
                        <tr>
                            <td class="id-badge">#${schedule.id}</td>
                            <td>${schedule.faculty.name}</td>
                            <td>${schedule.subject}</td>
                            <td>${schedule.grade_section}</td>
                            <td>${schedule.day_of_week}</td>
                            <td>${schedule.start_time} - ${schedule.end_time}</td>
                            <td>${schedule.room?.room_number || 'N/A'}</td>
                            <td>${schedule.student_count || 0}</td>
                            <td>${new Date(schedule.approved_at).toLocaleDateString()}</td>
                            <td><span class="badge badge-active">Active</span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn edit" onclick="openEditModal(${schedule.id})">Edit</button>
                                    <button class="action-btn delete" onclick="openApprovalModal(${schedule.id}, 'delete')">Delete</button>
                                    <button class="action-btn" style="background: rgba(100, 150, 200, 0.15); color: #4a90e2;" onclick="viewHistory(${schedule.id})">History</button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 2rem;">No approved schedules</td></tr>';
                }
            })
            .catch(error => {
                console.error('Error loading approved schedules:', error);
                document.getElementById('approvedTableBody').innerHTML = '<tr><td colspan="11" style="text-align: center; color: red;">Error loading schedules</td></tr>';
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

            // Load schedule details
            fetch(`/api/admin/schedules/${scheduleId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(schedule => {
                scheduleDetails.innerHTML = `
                    <strong>${schedule.subject}</strong> - ${schedule.grade_section}<br>
                    ${schedule.day_of_week} | ${schedule.start_time} - ${schedule.end_time}<br>
                    Teacher: ${schedule.faculty.name}<br>
                    Students: ${schedule.student_count || 0}
                `;
            });

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
            fetch(`{{ route('schedule.approve', ':id') }}`.replace(':id', scheduleId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('Schedule approved successfully!');
                closeModal();
                loadPendingSchedules();
                loadApprovedSchedules();
            })
            .catch(error => {
                alert('Error approving schedule: ' + error);
                console.error(error);
            });
        }

        // Reject schedule
        function rejectSchedule(scheduleId) {
            const reason = document.getElementById('rejectReason').value;
            if (!reason.trim()) {
                alert('Please enter a reason for rejection');
                return;
            }

            fetch(`{{ route('schedule.reject', ':id') }}`.replace(':id', scheduleId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                alert('Schedule rejected successfully!');
                closeModal();
                loadPendingSchedules();
                loadApprovedSchedules();
            })
            .catch(error => {
                alert('Error rejecting schedule: ' + error);
                console.error(error);
            });
        }

        // Delete schedule
        function deleteSchedule(scheduleId) {
            const reason = document.getElementById('rejectReason').value;
            if (!reason.trim()) {
                alert('Please enter a reason for deletion');
                return;
            }

            fetch(`{{ route('schedule.destroy', ':id') }}`.replace(':id', scheduleId), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ reason: reason })
            })
            .then(response => response.json())
            .then(data => {
                alert('Schedule deleted successfully!');
                closeModal();
                loadPendingSchedules();
                loadApprovedSchedules();
            })
            .catch(error => {
                alert('Error deleting schedule: ' + error);
                console.error(error);
            });
        }

        // Open edit modal
        function openEditModal(scheduleId) {
            currentScheduleId = scheduleId;
            const modal = document.getElementById('editModal');

            fetch(`/api/admin/schedules/${scheduleId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(schedule => {
                document.getElementById('editSubject').value = schedule.subject;
                document.getElementById('editGradeSection').value = schedule.grade_section;
                document.getElementById('editDay').value = schedule.day_of_week;
                document.getElementById('editStartTime').value = schedule.start_time;
                document.getElementById('editEndTime').value = schedule.end_time;
                document.getElementById('editStudentCount').value = schedule.student_count || 0;
                modal.style.display = 'flex';
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
            const data = {
                subject: document.getElementById('editSubject').value,
                grade_section: document.getElementById('editGradeSection').value,
                day_of_week: document.getElementById('editDay').value,
                start_time: document.getElementById('editStartTime').value,
                end_time: document.getElementById('editEndTime').value,
                student_count: document.getElementById('editStudentCount').value || 0
            };

            fetch(`{{ route('schedule.update', ':id') }}`.replace(':id', scheduleId), {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                alert('Schedule updated successfully!');
                closeEditModal();
                loadPendingSchedules();
                loadApprovedSchedules();
            })
            .catch(error => {
                alert('Error updating schedule: ' + error);
                console.error(error);
            });
        }

        // View change history
        function viewHistory(scheduleId) {
            fetch(`{{ route('schedule.history', ':id') }}`.replace(':id', scheduleId), {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                alert('Schedule History:\n\n' + (data.change_log || 'No changes recorded'));
            })
            .catch(error => {
                alert('Error fetching history: ' + error);
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
