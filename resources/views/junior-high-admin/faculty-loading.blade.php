{{-- resources/views/admin/faculty-loading.blade.php --}}
@extends('layouts.admin')

@section('title', 'Faculty Loading')

@section('content')
    <style>
        .filter-section { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .filter-input { padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; min-width: 200px; background: var(--bg-secondary); color: var(--text-primary); font-size: 0.875rem; }
        .filter-btn { padding: 0.75rem 1.5rem; background: var(--green-primary); color: white; border: none; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s; font-weight: 600; font-size: 0.875rem; }
        .filter-btn:hover { background: var(--green-dark); }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
        .page-wrapper { max-width: 1400px; margin: 0 auto; }
        
        /* Table Card Responsive */
        .table-card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow-x: auto; }
        .table-header { padding: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
        .table-title { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 1.5rem; background: var(--bg-primary); text-align: left; font-weight: 600; color: var(--text-primary); border-bottom: 1px solid var(--border-color); font-size: 0.875rem; white-space: nowrap; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); font-size: 0.875rem; color: var(--text-tertiary); }
        tr:hover { background: var(--bg-tertiary); }
        
        /* Modal and Form Centering */
        .modal { display: flex; align-items: center; justify-content: center; }
        .modal-content { background: var(--bg-secondary); border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto; }
        .form-group input, .form-group select, .form-group textarea { 
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--green-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(45, 122, 80, 0.1);
        }
        .form-group input:disabled, .form-group select:disabled {
            background: var(--bg-tertiary);
            opacity: 0.7;
        }

        /* Pagination Responsive */
        .pagination { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-top: 1px solid var(--border-color); flex-wrap: wrap; gap: 1rem; }
        .page-numbers { display: flex; gap: 0.25rem; flex-wrap: wrap; }
        .page-btn { padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); border-radius: 0.375rem; cursor: pointer; font-weight: 500; transition: all 0.2s; font-size: 0.875rem; }
        .page-btn:hover { border-color: var(--green-primary); color: var(--green-primary); }

        /* Responsive Breakpoints */
        @media (max-width: 1200px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .filter-input { min-width: 150px; }
        }

        @media (max-width: 768px) {
            .stats-row { grid-template-columns: 1fr; gap: 1rem; }
            .filter-section { flex-direction: column; }
            .filter-input { width: 100%; min-width: auto; }
            .filter-btn { width: 100%; }
            th { padding: 0.75rem; font-size: 0.75rem; }
            td { padding: 0.75rem; font-size: 0.75rem; }
            .table-header { padding: 1rem; }
            .table-title { font-size: 1rem; }
            .pagination { flex-direction: column; align-items: flex-start; }
            .page-numbers { justify-content: flex-start; }
        }

        @media (max-width: 640px) {
            table { font-size: 0.7rem; }
            th { padding: 0.5rem; }
            td { padding: 0.5rem; }
            .filter-input { font-size: 0.75rem; padding: 0.5rem; }
            .filter-btn { padding: 0.5rem 1rem; font-size: 0.75rem; }
            .table-card { border-radius: 0.5rem; }
            .table-header { padding: 1rem 0.75rem; }
        }
        .btn-edit { flex: 1; padding: 0.5rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; background: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color); }
        .btn-edit:hover { background: var(--text-secondary); color: white; }
        .btn-delete { flex: 1; padding: 0.5rem; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; background: transparent; color: #c83232; border: 1px solid #c83232; }
        .btn-delete:hover { background: #c83232; color: white; }
        /* Modal styles */
        .modal { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: var(--bg-secondary); border: 1px solid var(--border-color); padding: 2rem; border-radius: 0.75rem; width: 90%; max-width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        .modal-header h2 { color: var(--text-primary); margin: 0; font-size: 1.125rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary); font-size: 0.875rem; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); border-radius: 0.375rem; font-size: 0.875rem; font-family: inherit; box-sizing: border-box; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--green-primary); box-shadow: 0 0 0 3px rgba(45,122,80,0.1); }
        .form-actions { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .form-actions button { flex: 1; }
        .fl-add-subject-btn {
            margin-top: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0.55rem 1rem;
            background: var(--green-primary);
            color: #fff;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.8125rem;
            font-family: inherit;
            transition: background 0.2s;
        }
        .fl-add-subject-btn:hover { background: var(--green-dark); }
    </style>


    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Faculty Loading</h1>
        </div>
        <button onclick="openAddFacultyLoadModal()" style="background: var(--green-primary); color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; border: none; font-weight: 600; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s; cursor: pointer;">
            <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/></svg>
            Add Faculty Load
        </button>
    </div>

    @include('partials.admin-teacher-absence-banner')

    <!-- Filter Section -->
    <div class="filter-section">
        <input type="text" id="searchInput" class="filter-input" placeholder="Search teacher name...">
        <select id="statusFilter" class="filter-input">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="overloaded">Overloaded</option>
        </select>
        <button class="filter-btn" onclick="applyFilters()">Apply Filters</button>
    </div>

    <!-- Faculty Loading Stats -->
    <div class="stats-row">
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Faculty</p>
                    <p class="stat-value">{{ $totalFaculty ?? 0 }}</p>
                    <p class="stat-change">Active teachers</p>
                </div>
                <div class="stat-icon success">
                    <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Classes</p>
                    <p class="stat-value">{{ $totalClasses ?? 0 }}</p>
                    <p class="stat-change">Assigned classes</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Avg Load</p>
                    <p class="stat-value">{{ $avgLoad ?? 0 }}</p>
                    <p class="stat-change">Classes per teacher</p>
                </div>
                <div class="stat-icon warning">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Overloaded</p>
                    <p class="stat-value">{{ $overloaded ?? 0 }}</p>
                    <p class="stat-change">Teachers with more than 6 hours</p>
                </div>
                <div class="stat-icon default">
                    <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C10.34 2 9 3.34 9 5c0 1.66 1.34 3 3 3s3-1.34 3-3c0-1.66-1.34-3-3-3zm9 7h-6v13h-2v-6h-2v6H9V9H3V7h18v2z"/></svg>
                </div>
            </div>
        </div>
    </div>

        <div class="table-card">
        <div class="table-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;">
            <h2 class="table-title">Faculty Load Details</h2>
        </div>
        <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <table>
                <thead>
                    <tr>
                        <th>Teacher Name</th>
                        <th>Grade Level</th>
                        <th>Subject</th>
                        <th>Classes Assigned</th>
                        <th>Load (Hours/Week)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="facultyTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">Loading faculty data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="pagination" style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-top: 1px solid var(--border-color);">
            <div class="page-numbers" style="display: flex; gap: 0.5rem;">
                <button class="page-btn" onclick="previousPage()" style="padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); border-radius: 0.375rem; cursor: pointer; font-weight: 500; transition: all 0.2s;">‹</button>
                <div id="pageNumbers" style="display: flex; gap: 0.25rem;"></div>
                <button class="page-btn" onclick="nextPage()" style="padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); background: var(--bg-secondary); color: var(--text-primary); border-radius: 0.375rem; cursor: pointer; font-weight: 500; transition: all 0.2s;">›</button>
            </div>
        </div>
    </div>

    <!-- ── Shared Teachers ───────────────────────────────── -->
    <div id="jhSharedPanel" style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:0.75rem;margin-top:1.5rem;overflow:hidden;">
        <div onclick="jhToggleSharedPanel()" style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem;cursor:pointer;user-select:none;">
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color:#f59e0b;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span style="font-weight:700;font-size:0.95rem;color:var(--text-primary);">Shared Teachers — JH &amp; GS</span>
                <span id="jhSpBadge" style="display:inline-flex;align-items:center;padding:0.15rem 0.6rem;border-radius:9999px;font-size:0.72rem;font-weight:700;background:rgba(245,158,11,0.12);color:#b45309;"></span>
            </div>
            <svg id="jhSpChevron" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition:transform 0.2s;color:var(--text-secondary);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </div>
        <div id="jhSpBody" style="display:none;border-top:1px solid var(--border-color);padding:1rem 1.25rem;">
            <div id="jhSpLoading" style="text-align:center;padding:1.5rem;color:var(--text-secondary);font-size:0.85rem;">Fetching shared teacher data…</div>
            <div id="jhSpContent" style="display:none;"></div>
        </div>
    </div>

    <!-- Add Faculty Load Modal -->
    <div id="addFacultyLoadModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Faculty Load</h2>
                <button onclick="document.getElementById('addFacultyLoadModal').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-secondary);">&times;</button>
            </div>
            <form id="addFacultyLoadForm">
                <div class="form-group">
                    <label>Grade Level *</label>
                    <select id="addFacultyGradeLevel" required>
                        <option value="">-- Select Grade Level --</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Faculty *</label>
                    <select id="addFacultyTeacherId" required onchange="jhFetchFacultySchedules()">
                        <option value="">-- Select Teacher --</option>
                        @foreach($teachers as $teacher)
                            @php $isShared = in_array((string)$teacher->id, $sharedTeacherUserIds ?? []); @endphp
                            <option value="{{ $teacher->id }}" @if($isShared) data-shared="1" @endif>{{ trim($teacher->first_name . ' ' . $teacher->last_name) ?: $teacher->name }}{{ $isShared ? ' (Shared Teacher)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Subjects *</label>
                    <div id="jhAddSubjectList">
                        <p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Select a grade level, then add each subject this teacher handles.</p>
                    </div>
                    <button type="button" id="jhAddSubjectBtn" class="fl-add-subject-btn" onclick="jhAddSubjectRow()">+ Add Subject</button>
                    <script id="jh-subjects-data" type="application/json">{!! json_encode($subjects) !!}</script>
                </div>
                <div class="form-group">
                    <label>Classes Assigned <span style="font-size:0.78rem;font-weight:400;color:var(--text-secondary);">(auto — ongoing now)</span></label>
                    <input type="number" id="addFacultyClasses" required min="0" value="0" readonly style="background:var(--bg-tertiary);cursor:not-allowed;">
                    <small style="color:var(--text-secondary);font-size:0.75rem;margin-top:0.25rem;display:block;">Counted from this teacher&apos;s approved schedules that are in session right now (today&apos;s day and time).</small>
                </div>
                <div class="form-group">
                    <label>Load Hours <span style="font-size:0.78rem;font-weight:400;color:var(--text-secondary);">(auto-calculated)</span></label>
                    <input type="number" id="addFacultyHours" required min="0" step="0.01" placeholder="Auto-calculated from schedules" readonly style="background:var(--bg-tertiary);cursor:not-allowed;">
                    <small style="color:var(--text-secondary);font-size:0.75rem;margin-top:0.25rem;display:block;">&#9432; Calculated from the teacher's approved class schedule durations.</small>
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select id="addFacultyStatus" required>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                    <small style="color:var(--text-secondary);font-size:0.75rem;margin-top:0.25rem;display:block;">&#9432; Final status is automatically recalculated from the teacher's current schedule availability.</small>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="addFacultyNotes" placeholder="Additional notes" rows="2"></textarea>
                </div>
                <div class="form-actions" style="display:flex;gap:0.75rem;align-items:center;">
                    <button type="submit" style="flex:1;background:var(--green-primary);color:white;border:none;padding:0.75rem;border-radius:0.375rem;cursor:pointer;font-weight:600;">Add Load</button>
                    <button type="button" onclick="document.getElementById('addFacultyLoadModal').style.display='none'" style="flex:1;background:var(--bg-tertiary);color:var(--text-primary);border:1px solid var(--border-color);padding:0.75rem;border-radius:0.375rem;cursor:pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Faculty Load Modal -->
    <div id="editFacultyLoadModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Faculty Load</h2>
                <button onclick="document.getElementById('editFacultyLoadModal').style.display='none'" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:var(--text-secondary);">&times;</button>
            </div>
            <form id="editFacultyLoadForm">
                <input type="hidden" id="editFacultyLoadId">
                <div class="form-group">
                    <label>Teacher Name</label>
                    <input type="text" id="editFacultyTeacherName" readonly style="background:var(--bg-tertiary);cursor:not-allowed;color:var(--text-secondary);">
                    <input type="hidden" id="editFacultyTeacherId">
                </div>
                <div class="form-group">
                    <label>Grade Level</label>
                    <select id="editFacultyGradeLevel" onchange="jhOnEditLoadFieldChange()">
                        <option value="">-- Select Grade Level --</option>
                        <option value="Grade 7">Grade 7</option>
                        <option value="Grade 8">Grade 8</option>
                        <option value="Grade 9">Grade 9</option>
                        <option value="Grade 10">Grade 10</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Subjects *</label>
                    <div id="jhEditSubjectList"><p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Add each subject this teacher handles.</p></div>
                    <button type="button" class="fl-add-subject-btn" onclick="jhAddEditSubjectRow()">+ Add Subject</button>
                </div>
                <div class="form-group">
                    <label>Classes Assigned <span style="font-size:0.78rem;font-weight:400;color:var(--text-secondary);">(auto — ongoing now)</span></label>
                    <input type="number" id="editFacultyClasses" min="0" value="0" readonly style="background:var(--bg-tertiary);cursor:not-allowed;">
                    <small style="color:var(--text-secondary);font-size:0.75rem;margin-top:0.25rem;display:block;">Auto-count from ongoing approved schedules for this teacher and grade.</small>
                </div>
                <div class="form-group">
                    <label>Load Hours <span style="font-size:.75rem;font-weight:400;color:var(--text-secondary);">(auto-computed from approved schedules)</span></label>
                    <input type="number" id="editFacultyHours" min="0" step="0.5" readonly style="background:var(--bg-tertiary);cursor:not-allowed;color:var(--text-secondary);">
                </div>
                <div class="form-group">
                    <label>Status <span style="font-size:.75rem;font-weight:400;color:var(--text-secondary);">(automatic)</span></label>
                    <input type="text" id="editFacultyStatusDisplay" readonly style="background:var(--bg-tertiary);cursor:not-allowed;color:var(--text-secondary);text-transform:capitalize;">
                    <input type="hidden" id="editFacultyStatus" value="available">
                    <small style="color:var(--text-secondary);font-size:0.75rem;margin-top:0.25rem;display:block;">&#9432; Not Available when the teacher has an ongoing class right now; otherwise Available.</small>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="editFacultyNotes" rows="2"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" style="flex:1;background:var(--green-primary);color:white;border:none;padding:0.75rem;border-radius:0.375rem;cursor:pointer;font-weight:600;">Update</button>
                    <button type="button" onclick="document.getElementById('editFacultyLoadModal').style.display='none'" style="flex:1;background:var(--bg-tertiary);color:var(--text-primary);border:1px solid var(--border-color);padding:0.75rem;border-radius:0.375rem;cursor:pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="{{ asset('js/admin-faculty-load-form.js') }}"></script>
    <script>
        const masterScheduleBaseUrl = '{{ url("admin/master-schedule") }}';
        let allFacultyLoads = [];
        let currentPage = 1;
        const itemsPerPage = 5;
        let filteredFacultyLoads = [];

        function formatLoadHoursLabel(hours) {
            if (hours === null || hours === undefined || hours === '') return '0 hour/s';
            const v = parseFloat(hours);
            if (isNaN(v)) return '0 hour/s';
            const formatted = (Math.round(v * 100) / 100).toString().replace(/\.?0+$/, '');
            return formatted + ' hour/s';
        }

        // Load faculty loads on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadFacultyLoads();
            setupFilterListeners();
        });

        function setupFilterListeners() {
            document.getElementById('searchInput')?.addEventListener('keyup', applyFilters);
            document.getElementById('statusFilter')?.addEventListener('change', applyFilters);
        }

        function applyFilters() {
            const searchValue = (document.getElementById('searchInput')?.value || '').toLowerCase();
            const statusValue = (document.getElementById('statusFilter')?.value || '').toLowerCase();

            filteredFacultyLoads = allFacultyLoads.filter(load => {
                const teacherName = (load.teacher_name || load.faculty?.name || '').toLowerCase();
                const subjectName = (load.subject || '').toLowerCase();
                const nameMatch   = !searchValue || teacherName.includes(searchValue) || subjectName.includes(searchValue);
                const normalS     = (load.status || '').toLowerCase();
                const resolvedS   = (normalS === 'overload') ? 'overloaded' : normalS;
                const statusMatch = !statusValue || resolvedS === statusValue;
                return nameMatch && statusMatch;
            });

            currentPage = 1;
            displayFacultyLoads(currentPage, filteredFacultyLoads);
            updatePagination(filteredFacultyLoads);
        }

        function loadFacultyLoads() {
            fetch('/api/faculty-loads', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                allFacultyLoads = (data.data || data || []);
                if (!Array.isArray(allFacultyLoads)) {
                    allFacultyLoads = [];
                }
                filteredFacultyLoads = [];
                currentPage = 1;
                displayFacultyLoads(currentPage);
                updatePagination();
            })
            .catch(error => {
                console.error('Error loading faculty loads:', error);
                const tbody = document.getElementById('facultyTableBody');
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-secondary);">No faculty loads available yet. <br><small>' + error.message + '</small></td></tr>'; // colspan 7
                }
            });
        }

        function displayFacultyLoads(page, dataToDisplay = null) {
            const tbody = document.getElementById('facultyTableBody');
            const data = dataToDisplay || allFacultyLoads;
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageData = data.slice(start, end);

            if (pageData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No faculty loads found</td></tr>';
                return;
            }

            tbody.innerHTML = pageData.map(load => {
                const normalizedStatus = (load.status || 'available').toLowerCase();
                const isOnLeave = !!load.presence_status;
                const isAvailable = normalizedStatus === 'available';
                const isNotAvailable = isOnLeave || normalizedStatus === 'not_available' || normalizedStatus === 'unavailable';
                const isOverloaded = !isOnLeave && (normalizedStatus === 'overloaded' || normalizedStatus === 'overload');
                let statusBadge, statusText;
                if (isOnLeave) {
                    statusBadge = 'badge-danger';
                    statusText  = load.availability_note || ('Unavailable — ' + (load.presence_label || 'On Leave'));
                } else if (isAvailable) {
                    statusBadge = 'badge-active';
                    statusText  = 'Available';
                } else if (isNotAvailable) {
                    statusBadge = 'badge-danger';
                    statusText  = load.availability_note || 'Not Available';
                } else if (isOverloaded) {
                    statusBadge = 'badge-warning';
                    if (load.shared_load_conflict) {
                        statusText = `Overloaded – ${load.shared_load_count || 0} loads (max 5 for shared teachers)`;
                    } else {
                        const dayDetail = load.overloaded_day ? ` – ${load.max_day_count} on ${load.overloaded_day}` : '';
                        statusText = `Overloaded${dayDetail}`;
                    }
                } else {
                    statusBadge = 'badge-warning';
                    statusText  = load.status ? load.status.charAt(0).toUpperCase() + load.status.slice(1) : 'Available';
                }
                const sharedBadge = load.is_shared_teacher
                    ? ' <span style="background:#2563eb;color:white;border-radius:9999px;font-size:0.65rem;padding:1px 7px;font-weight:700;vertical-align:middle;white-space:nowrap;">SHARED</span>'
                    : '';
                const presenceBadge = load.presence_label
                    ? ` <span style="background:${load.presence_status === 'absent' ? '#c62828' : '#e65100'};color:white;border-radius:9999px;font-size:0.65rem;padding:1px 7px;font-weight:700;vertical-align:middle;white-space:nowrap;">${load.presence_label}</span>`
                    : '';

                return `
                    <tr>
                        <td><strong>${load.teacher_name || load.faculty?.name || ''}</strong>${sharedBadge}${presenceBadge}</td>
                        <td>${load.grade_level || 'N/A'}</td>
                        <td>${load.subject || ''}</td>
                        <td>${load.classes_assigned || 0}</td>
                        <td>${load.load_hours_label || formatLoadHoursLabel(load.load_hours)}</td>
                        <td><span class="badge ${statusBadge}">${statusText}</span></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:nowrap;">
                                <button onclick="editFacultyLoad(${load.id})" style="padding:0.4rem 0.75rem;border:1px solid var(--border-color);border-radius:0.375rem;cursor:pointer;font-size:0.75rem;font-weight:500;background:var(--bg-secondary);color:var(--text-primary);white-space:nowrap;transition:all 0.2s;" onmouseover="this.style.background='#666';this.style.color='white'" onmouseout="this.style.background='var(--bg-secondary)';this.style.color='var(--text-primary)'">Edit</button>
                                <button onclick="deleteFacultyLoad(${load.id})" style="padding:0.4rem 0.75rem;border:1px solid #c83232;border-radius:0.375rem;cursor:pointer;font-size:0.75rem;font-weight:500;background:transparent;color:#c83232;white-space:nowrap;transition:all 0.2s;" onmouseover="this.style.background='#c83232';this.style.color='white'" onmouseout="this.style.background='transparent';this.style.color='#c83232'">Delete</button>
                                ${load.faculty_id && load.has_user_account ? `<a href="${masterScheduleBaseUrl}/${load.faculty_id}" style="padding:0.4rem 0.75rem;border:1px solid var(--green-primary);border-radius:0.375rem;font-size:0.75rem;font-weight:500;background:var(--green-primary);color:white;text-decoration:none;display:inline-block;white-space:nowrap;">Schedule</a>` : ''}
                                ${load.faculty_id && load.has_user_account ? `<button onclick="openScheduleCard(${load.faculty_id})" style="padding:0.4rem 0.75rem;border:1px solid var(--green-primary);border-radius:0.375rem;cursor:pointer;font-size:0.75rem;font-weight:500;background:transparent;color:var(--green-primary);white-space:nowrap;transition:all 0.2s;">View Card</button>` : ''}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function updatePagination(dataToUse = null) {
            const data = dataToUse || allFacultyLoads;
            const totalPages = Math.ceil(data.length / itemsPerPage);
            const pageNumbers = document.getElementById('pageNumbers');
            let html = '';
            
            // Get computed styles for dark mode
            const root = document.documentElement;
            const bgSecondary = getComputedStyle(root).getPropertyValue('--bg-secondary').trim();
            const textPrimary = getComputedStyle(root).getPropertyValue('--text-primary').trim();
            const greenPrimary = getComputedStyle(root).getPropertyValue('--green-primary').trim();
            const borderColor = getComputedStyle(root).getPropertyValue('--border-color').trim();
            
            for (let i = 1; i <= totalPages; i++) {
                const isActive = i === currentPage;
                html += `<button class="page-btn ${isActive ? 'active' : ''}" onclick="goToPage(${i})" style="min-width: 2rem; padding: 0.5rem; border: 1px solid ${borderColor}; background: ${isActive ? greenPrimary : bgSecondary}; color: ${isActive ? 'white' : textPrimary}; border-radius: 0.375rem; cursor: pointer; font-weight: 500; transition: all 0.2s;">${i}</button>`;
            }
            
            pageNumbers.innerHTML = html;
        }

        function goToPage(page) {
            currentPage = page;
            const data = filteredFacultyLoads.length > 0 ? filteredFacultyLoads : allFacultyLoads;
            displayFacultyLoads(currentPage, data);
            updatePagination(data);
        }

        function previousPage() {
            if (currentPage > 1) {
                currentPage--;
                const data = filteredFacultyLoads.length > 0 ? filteredFacultyLoads : allFacultyLoads;
                displayFacultyLoads(currentPage, data);
                updatePagination(data);
            }
        }

        function nextPage() {
            const data = filteredFacultyLoads.length > 0 ? filteredFacultyLoads : allFacultyLoads;
            const totalPages = Math.ceil(data.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                displayFacultyLoads(currentPage, data);
                updatePagination(data);
            }
        }

        function editFacultyLoad(id) {
            const load = allFacultyLoads.find(l => l.id === id);
            if (!load) return;
            document.getElementById('editFacultyLoadId').value = id;
            document.getElementById('editFacultyGradeLevel').value  = load.grade_level || '';
            document.getElementById('editFacultyClasses').value    = load.classes_assigned ?? 0;
            document.getElementById('editFacultyHours').value      = load.load_hours ?? 0;
            // Render dynamic subject rows pre-populated with saved subjects
            const preselectedSubjects = load.subject ? load.subject.split(',').map(s => s.trim()) : [];
            jhEditLoadSnapshot = { subjects: preselectedSubjects.slice() };
            jhRenderEditSubjectRows(preselectedSubjects);
            document.getElementById('editFacultyNotes').value      = load.notes || '';

            // Auto-fill teacher name (display only) and store faculty_id in hidden input
            const teacherName = (load.faculty && (load.faculty.first_name || load.faculty.last_name))
                ? (load.faculty.first_name + ' ' + load.faculty.last_name).trim()
                : (load.faculty?.name || load.teacher_name || 'Teacher #' + (load.faculty_id || '?'));
            document.getElementById('editFacultyTeacherName').value = teacherName;
            document.getElementById('editFacultyTeacherId').value   = load.faculty_id || '';
            jhFetchEditFacultySchedules(load.faculty_id || '');

            document.getElementById('editFacultyLoadModal').style.display = 'flex';
        }

        document.getElementById('editFacultyLoadForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const id = document.getElementById('editFacultyLoadId').value;
            const facultyId = document.getElementById('editFacultyTeacherId').value;
            if (!facultyId) {
                alert('Teacher information is missing. Please reload and try again.');
                return;
            }
            const editSubjects = jhCollectEditSubjects();
            if (!editSubjects.length) {
                alert('Please add at least one subject.');
                return;
            }
            const data = {
                faculty_id:       parseInt(facultyId),
                grade_level:      document.getElementById('editFacultyGradeLevel').value,
                subject:          editSubjects.join(', '),
                classes_assigned: parseInt(document.getElementById('editFacultyClasses').value) || 0,
                load_hours:       parseFloat(document.getElementById('editFacultyHours').value) || 0,
                status:           document.getElementById('editFacultyStatus')?.value || 'available',
                notes:            document.getElementById('editFacultyNotes').value,
            };
            fetch(`/api/faculty-loads/${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => {
                if (!r.ok) {
                    return r.json().then(err => {
                        const msg = err.errors ? Object.values(err.errors).flat().join(', ') : (err.message || 'Failed to update');
                        throw new Error(msg);
                    });
                }
                return r.json();
            })
            .then(res => {
                if (res.success !== false) {
                    alert(' Faculty load updated successfully');
                    document.getElementById('editFacultyLoadModal').style.display = 'none';
                    loadFacultyLoads();
                } else {
                    alert(' ' + (res.message || 'Error updating faculty load'));
                }
            })
            .catch(err => alert(' ' + err.message));
        });

        // ── Helpers for dynamic subject rows (JH) ────────────────────────────
        const JH_ALL_SUBJECTS = JSON.parse(document.getElementById('jh-subjects-data')?.textContent || '[]');

        const JH_GRADE_SUBJECTS = {
            'Grade 7':  ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
            'Grade 8':  ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
            'Grade 9':  ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
            'Grade 10': ['MAPEH','Araling Panlipunan','Computer Education','Advanced Science','Christian Living/Values Education','Mathematics','Advanced Mathematics','Filipino','English','Science','Technology and Livelihood Education'],
        };
        let jhFacultySchedulesCache = [];
        let jhEditFacultySchedulesCache = [];
        let jhFacultyTeachersCache = [];
        let jhEditLoadSnapshot = { subjects: [] };

        function jhGetEditSubjectPreserve() {
            const fromDom = typeof jhCollectEditSubjects === 'function' ? jhCollectEditSubjects() : [];
            const snap = jhEditLoadSnapshot.subjects || [];
            const n = Math.max(fromDom.length, snap.length, 1);
            const out = [];
            for (let i = 0; i < n; i++) {
                out.push(fromDom[i] || snap[i] || '');
            }
            return out.filter((s, i, arr) => s || i < fromDom.length || i < snap.length);
        }

        function jhOnEditLoadFieldChange() {
            jhRenderEditSubjectRows(jhGetEditSubjectPreserve());
        }

        function jhSubjectsForGrade(gradeLevel) {
            return JH_GRADE_SUBJECTS[gradeLevel] || JH_ALL_SUBJECTS;
        }

        AdminFacultyLoadForm.init({
            rowClass: 'jh-subject-row',
            editRowClass: 'jh-edit-subject-row',
            listAddId: 'jhAddSubjectList',
            listEditId: 'jhEditSubjectList',
            classesAddId: 'addFacultyClasses',
            classesEditId: 'editFacultyClasses',
            gradeAddId: 'addFacultyGradeLevel',
            gradeEditId: 'editFacultyGradeLevel',
            globalAddSubject: 'jhAddSubjectRow',
            globalAddEditSubject: 'jhAddEditSubjectRow',
            globalRenderAdd: 'jhRenderSubjectRows',
            globalRenderEdit: 'jhRenderEditSubjectRows',
            globalRecalcOngoing: 'jhRecalculateOngoingClasses',
            globalCollectAdd: 'jhCollectSubjects',
            globalCollectEdit: 'jhCollectEditSubjects',
            subjectsForGrade: jhSubjectsForGrade,
            getScheduleCache: function () { return jhFacultySchedulesCache; },
            getEditScheduleCache: function () { return jhEditFacultySchedulesCache; },
            onRecalculateAddHours: function () { jhRecalculateAddHours(true); },
            onRecalculateEditHours: jhRecalculateEditHours
        });

        function jhNormalizeSubject(v) {
            return String(v || '').trim().toLowerCase();
        }

        function jhComputeHoursFromSchedules(schedules, gradeLevel, selectedSubjects) {
            const normalizedGrade = String(gradeLevel || '').trim().toLowerCase();
            const normalizedSubjects = (selectedSubjects || []).map(jhNormalizeSubject).filter(Boolean);
            let totalMins = 0;

            (schedules || []).forEach(s => {
                const scheduleGrade = String(s.grade_level || '').trim().toLowerCase();
                if (normalizedGrade && scheduleGrade && scheduleGrade !== normalizedGrade) {
                    return;
                }

                const scheduleSubject = jhNormalizeSubject(s.subject);
                if (normalizedSubjects.length > 0 && !normalizedSubjects.includes(scheduleSubject)) {
                    return;
                }

                const start = jhTimeToMinsLoad(s.start_time);
                const end = jhTimeToMinsLoad(s.end_time);
                const dur = end - start;
                if (dur > 0) totalMins += dur;
            });

            return parseFloat((totalMins / 60).toFixed(2));
        }

        function jhRecalculateAddHours(skipValidation) {
            const gradeLevel = document.getElementById('addFacultyGradeLevel')?.value || '';
            const subjects = typeof jhCollectSubjects === 'function' ? jhCollectSubjects() : [];
            if (!skipValidation && !subjects.length) {
                document.getElementById('addFacultyHours').value = 0;
                return;
            }
            const totalHours = jhComputeHoursFromSchedules(jhFacultySchedulesCache, gradeLevel, subjects);
            document.getElementById('addFacultyHours').value = totalHours || 0;
        }

        function jhRecalculateEditHours() {
            const gradeLevel = document.getElementById('editFacultyGradeLevel')?.value || '';
            const subjects = jhCollectEditSubjects();
            const approved = jhGetApprovedSchedules(jhEditFacultySchedulesCache);
            const totalHours = jhComputeHoursFromSchedules(approved, gradeLevel, subjects);
            document.getElementById('editFacultyHours').value = totalHours || 0;
            jhRecalculateEditStatus();
        }

        function jhGetApprovedSchedules(list) {
            return (list || []).filter(s =>
                s && s.admin_approved && ['active', 'approved'].includes(String(s.status || '').toLowerCase())
            );
        }

        function jhRecalculateEditStatus() {
            const hidden = document.getElementById('editFacultyStatus');
            const display = document.getElementById('editFacultyStatusDisplay');
            if (!hidden || !display) return;

            const now = new Date();
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const today = dayNames[now.getDay()];
            const nowMins = now.getHours() * 60 + now.getMinutes();

            const inClass = jhGetApprovedSchedules(jhEditFacultySchedulesCache).some(s => {
                if (String(s.day_of_week || '').toLowerCase() !== today.toLowerCase()) return false;
                const start = jhTimeToMinsLoad(s.start_time);
                const end = jhTimeToMinsLoad(s.end_time);
                return start <= nowMins && end > nowMins;
            });

            const status = inClass ? 'not_available' : 'available';
            hidden.value = status;
            display.value = status === 'not_available' ? 'Not Available' : 'Available';
        }

        function jhTimeToMinsLoad(t) {
            if (!t) return 0;
            const [h, m] = String(t).split(':').map(Number);
            return (h || 0) * 60 + (m || 0);
        }

        function jhApplySharedTeacherSubjects() {
            const facultyId = document.getElementById('addFacultyTeacherId')?.value;
            const teacher = jhFacultyTeachersCache.find(t => String(t.id) === String(facultyId));
            const roleName = teacher?.role_name || teacher?.role?.name || '';
            const subjects = Array.isArray(teacher?.assigned_subjects) ? teacher.assigned_subjects : [];

            if (roleName === 'shared_teacher' && subjects.length && typeof jhRenderSubjectRows === 'function') {
                jhRenderSubjectRows(subjects);
                jhRecalculateAddHours();
                jhRecalculateOngoingClasses(false);
                return true;
            }

            return false;
        }

        function jhFetchFacultySchedules() {
            const facultyId = document.getElementById('addFacultyTeacherId').value;
            if (!facultyId) {
                document.getElementById('addFacultyHours').value = '';
                document.getElementById('addFacultyClasses').value = '0';
                document.getElementById('jhAddSubjectList').innerHTML =
                    '<p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Select a teacher and grade level, then add subjects.</p>';
                return;
            }

            if (jhApplySharedTeacherSubjects()) {
                // Subjects pre-filled from User Accounts; still load schedules for hours/classes.
            } else {
                document.getElementById('jhAddSubjectList').innerHTML =
                    '<p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Select a grade level, then add each subject.</p>';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch(`/api/admin/schedules?faculty_id=${facultyId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(r => r.json())
            .then(res => {
                jhFacultySchedulesCache = (res.data || []).filter(s => s.admin_approved);
                jhRecalculateAddHours();
                jhRecalculateOngoingClasses(false);
            })
            .catch(() => {
                jhFacultySchedulesCache = [];
                document.getElementById('addFacultyHours').value = 0;
            });
        }

        function jhFetchEditFacultySchedules(facultyId) {
            if (!facultyId) {
                jhEditFacultySchedulesCache = [];
                jhRecalculateEditHours();
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch(`/api/admin/schedules?faculty_id=${facultyId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            })
            .then(r => r.json())
            .then(res => {
                jhEditFacultySchedulesCache = res.data || res || [];
                jhRecalculateEditHours();
                jhRecalculateOngoingClasses(true);
            })
            .catch(() => {
                jhEditFacultySchedulesCache = [];
                jhRecalculateEditHours();
            });
        }

        function openAddFacultyLoadModal() {
            document.getElementById('addFacultyLoadForm').reset();
            jhFacultySchedulesCache = [];
            document.getElementById('addFacultyHours').value = '';
            document.getElementById('jhAddSubjectList').innerHTML =
                '<p style="color:var(--text-secondary);font-size:0.85rem;margin:0;">Select a grade level, then add each subject.</p>';
            document.getElementById('addFacultyClasses').value = '0';
            // Hide designation guard until a teacher is selected
            // Reload teachers fresh from DB so newly-created users appear
            const sel = document.getElementById('addFacultyTeacherId');
            sel.innerHTML = '<option value="">Loading teachers…</option>';
            fetch('/api/teachers?context=faculty', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            })
            .then(r => r.json())
            .then(data => {
                const teachers = Array.isArray(data) ? data : (data.data || []);
                jhFacultyTeachersCache = teachers;
                sel.innerHTML = '<option value="">-- Select Teacher --</option>';
                teachers.forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    const roleName = t.role_name || t.role?.name || '';
                    if (roleName === 'shared_teacher') {
                        opt.dataset.shared = '1';
                    }
                    opt.textContent = ((t.first_name || t.last_name)
                        ? (t.first_name + ' ' + t.last_name).trim()
                        : (t.name || '')) + (roleName === 'shared_teacher' ? ' (Shared Teacher)' : '');
                    sel.appendChild(opt);
                });
            })
            .catch(() => {
                sel.innerHTML = '<option value="">-- Select Teacher --</option>';
            });
            document.getElementById('addFacultyLoadModal').style.display = 'flex';
        }

        document.getElementById('addFacultyLoadForm')?.addEventListener('submit', function (e) {
            e.preventDefault();
            const facultyId = document.getElementById('addFacultyTeacherId').value;
            if (!facultyId) {
                alert('Please select a teacher.');
                return;
            }
            const subjects = jhCollectSubjects();
            if (!subjects.length) {
                alert('Please add at least one subject.');
                return;
            }
            const data = {
                faculty_id:       parseInt(facultyId),
                grade_level:      document.getElementById('addFacultyGradeLevel').value,
                subject:          subjects.join(', '),
                classes_assigned: parseInt(document.getElementById('addFacultyClasses').value) || 0,
                load_hours:       parseFloat(document.getElementById('addFacultyHours').value) || 0,
                status:           document.getElementById('addFacultyStatus').value,
                notes:            document.getElementById('addFacultyNotes').value,
            };
            fetch('/api/faculty-loads', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success !== false) {
                    alert(' Faculty load added successfully');
                    document.getElementById('addFacultyLoadModal').style.display = 'none';
                    loadFacultyLoads();
                } else {
                    const msg = res.errors ? Object.values(res.errors).flat().join(', ') : res.message;
                    alert(' ' + (msg || 'Error adding faculty load'));
                }
            })
            .catch(() => alert(' Error adding faculty load'));
        });



        function deleteFacultyLoad(id) {
                fetch(`/api/faculty-loads/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    console.log('Delete response:', response.status, response.statusText);
                    if (response.status === 200 || response.status === 204) {
                        // Remove from local data and refresh display
                        allFacultyLoads = allFacultyLoads.filter(l => l.id !== id);
                        currentPage = 1;
                        displayFacultyLoads(currentPage);
                        updatePagination();
                        alert('Faculty load deleted successfully');
                        return;
                    }
                    return response.json().then(data => {
                        alert('Error: ' + (data.message || 'Failed to delete'));
                    });
                })
                .catch(error => {
                    console.error('Delete error:', error);
                    alert('Error deleting faculty load: ' + error.message);
                });
        }

        /* ===== Schedule Card Modal ===== */
        function openScheduleCard(teacherId) {
            const modal    = document.getElementById('scheduleCardModal');
            const frame    = document.getElementById('scheduleCardFrame');
            const spinner  = document.getElementById('scheduleCardSpinner');
            const cardUrl  = `{{ url('admin/master-schedule') }}/${teacherId}/card`;

            modal.style.display   = 'flex';
            frame.style.display   = 'none';
            spinner.style.display = 'flex';
            frame.src = cardUrl;
            frame.onload = () => {
                spinner.style.display = 'none';
                frame.style.display   = 'block';
            };
        }
        function closeScheduleCard() {
            const modal  = document.getElementById('scheduleCardModal');
            const frame  = document.getElementById('scheduleCardFrame');
            modal.style.display = 'none';
            frame.src = 'about:blank';
        }
        // Close on backdrop click
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('scheduleCardModal');
            if (modal) modal.addEventListener('click', e => { if (e.target === modal) closeScheduleCard(); });
        });

        // ── Shared Teachers Panel ──────────────────────────────────────────
        let jhSpLoaded = false;
        function jhToggleSharedPanel() {
            const body    = document.getElementById('jhSpBody');
            const chevron = document.getElementById('jhSpChevron');
            const open    = body.style.display !== 'none';
            body.style.display      = open ? 'none' : 'block';
            chevron.style.transform = open ? '' : 'rotate(180deg)';
            if (!open && !jhSpLoaded) jhLoadSharedPanel();
        }
        function jhLoadSharedPanel() {
            fetch('/api/shared-teachers-panel', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            })
            .then(r => r.json())
            .then(d => {
                jhSpLoaded = true;
                document.getElementById('jhSpLoading').style.display = 'none';
                const content = document.getElementById('jhSpContent');
                content.style.display = 'block';
                const badge = document.getElementById('jhSpBadge');

                if (!d.success || !d.data.length) {
                    badge.textContent = 'No shared teachers';
                    badge.style.background = 'rgba(16,185,129,.1)';
                    badge.style.color = '#065f46';
                    content.innerHTML = '<p style="color:var(--text-secondary);font-size:0.85rem;text-align:center;padding:0.5rem 0;">No teachers are currently assigned in both JH and GS.</p>';
                    return;
                }

                if (d.conflict_count > 0) {
                    badge.textContent = d.conflict_count + ' conflict' + (d.conflict_count > 1 ? 's' : '');
                    badge.style.background = 'rgba(239,68,68,.12)';
                    badge.style.color = '#b91c1c';
                } else {
                    badge.textContent = d.data.length + ' shared';
                    badge.style.background = 'rgba(16,185,129,.1)';
                    badge.style.color = '#065f46';
                }

                // Group all shared teachers together (no department grouping)
                const byDept = { 'All Shared Teachers': d.data };
                const depts = ['All Shared Teachers'];

                let html = '';
                if (d.conflict_count > 0) {
                    html += '<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;padding:.5rem .75rem;background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:.375rem;">'
                        + '<svg width="14" height="14" fill="none" stroke="#ef4444" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>'
                        + `<span style="font-size:.8rem;color:#b91c1c;font-weight:600;">${d.conflict_count} teacher(s) have overlapping JH and GS schedules. Resolve by adjusting class times in either division.</span>`
                        + '</div>';
                }

                depts.forEach(dept => {
                    const teachers = byDept[dept];
                    const deptHasConflict = teachers.some(t => t.conflicts.length > 0);
                    html += `<div style="margin-bottom:1.25rem;">`
                        + `<div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.4rem;padding:.35rem .75rem;background:var(--bg-primary,#f9fafb);border-left:3px solid ${deptHasConflict ? '#ef4444' : '#3b82f6'};border-radius:0 .375rem .375rem 0;">`
                        + `<span style="font-weight:700;font-size:.83rem;color:var(--text-primary);">${dept}</span>`
                        + `<span style="font-size:.72rem;color:var(--text-secondary);">(${teachers.length} teacher${teachers.length > 1 ? 's' : ''})</span>`
                        + `</div>`
                        + '<div style="overflow-x:auto;">'
                        + '<table style="width:100%;border-collapse:collapse;font-size:0.82rem;">'
                        + '<thead><tr style="border-bottom:2px solid var(--border-color);">'
                        + '<th style="padding:0.5rem 0.75rem;text-align:left;color:var(--text-secondary);font-weight:600;white-space:nowrap;">Teacher</th>'
                        + '<th style="padding:0.5rem 0.75rem;text-align:center;color:var(--text-secondary);font-weight:600;">JH Load</th>'
                        + '<th style="padding:0.5rem 0.75rem;text-align:center;color:var(--text-secondary);font-weight:600;">GS Load</th>'
                        + '<th style="padding:0.5rem 0.75rem;text-align:center;color:var(--text-secondary);font-weight:600;">Combined Hrs</th>'
                        + '<th style="padding:0.5rem 0.75rem;text-align:center;color:var(--text-secondary);font-weight:600;">Status</th>'
                        + '<th style="padding:0.5rem 0.75rem;text-align:left;color:var(--text-secondary);font-weight:600;">Conflicts</th>'
                        + '</tr></thead><tbody>';

                    teachers.forEach(t => {
                        const hasConflict = t.conflicts.length > 0;
                        const rowBg = hasConflict ? 'rgba(239,68,68,.04)' : '';
                        const statusBadge = t.status === 'overloaded'
                            ? '<span style="padding:.15rem .5rem;border-radius:9999px;font-size:.7rem;font-weight:700;background:rgba(239,68,68,.12);color:#b91c1c;">Overloaded</span>'
                            : t.status === 'near_limit'
                            ? '<span style="padding:.15rem .5rem;border-radius:9999px;font-size:.7rem;font-weight:700;background:rgba(245,158,11,.12);color:#92400e;">Near Limit</span>'
                            : '<span style="padding:.15rem .5rem;border-radius:9999px;font-size:.7rem;font-weight:700;background:rgba(16,185,129,.1);color:#065f46;">OK</span>';

                        html += `<tr data-tt="${t.id}" style="border-bottom:1px solid var(--border-color);background:${rowBg};cursor:pointer;" title="Click to show/hide weekly timetable">`;
                        html += `<td style="padding:.5rem .75rem;font-weight:600;color:var(--text-primary);">${t.name}<br><span style="font-size:.7rem;font-weight:400;color:#3b82f6;">JH: ${t.jh_subjects}</span>${t.gs_subjects !== '—' ? '<br><span style="font-size:.7rem;font-weight:400;color:#10b981;">GS: ' + t.gs_subjects + '</span>' : ''}</td>`;
                        html += `<td style="padding:.5rem .75rem;text-align:center;">${t.jh_classes} cls / ${t.jh_hours.toFixed(1)} h</td>`;
                        html += `<td style="padding:.5rem .75rem;text-align:center;">${t.gs_classes} cls / ${t.gs_hours.toFixed(1)} h</td>`;
                        const totalColor = t.total_hours > t.max_hours ? '#ef4444' : t.total_hours > t.max_hours * .8 ? '#f59e0b' : '#22c55e';
                        html += `<td style="padding:.5rem .75rem;text-align:center;font-weight:700;color:${totalColor};">${t.total_hours.toFixed(1)} / ${t.max_hours}</td>`;
                        html += `<td style="padding:.5rem .75rem;text-align:center;">${statusBadge}</td>`;

                        if (hasConflict) {
                            let cHtml = '';
                            t.conflicts.forEach(c => {
                                cHtml += `<div style="margin-bottom:.35rem;padding:.35rem .5rem;background:rgba(239,68,68,.08);border-left:3px solid #ef4444;border-radius:0 .25rem .25rem 0;font-size:.75rem;">`
                                    + `<strong style="color:#b91c1c;">${c.day}</strong> — `
                                    + `<span style="color:var(--text-primary);">JH: ${c.jh_subject} (${c.jh_time})</span>`
                                    + ` <span style="color:var(--text-secondary);">vs</span> `
                                    + `<span style="color:var(--text-primary);">GS: ${c.gs_subject} (${c.gs_time})</span>`
                                    + `</div>`;
                            });
                            html += `<td style="padding:.5rem .75rem;min-width:260px;">${cHtml}</td>`;
                        } else {
                            html += `<td style="padding:.5rem .75rem;color:var(--text-secondary);font-size:.78rem;">No conflicts</td>`;
                        }
                        html += '</tr>';

                        // Weekly timetable row (collapsed by default)
                        const DAYS = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
                        const jhByDay = {};
                        const gsByDay = {};
                        DAYS.forEach(d => { jhByDay[d] = []; gsByDay[d] = []; });
                        (t.jh_schedules || []).forEach(s => { if (jhByDay[s.day]) jhByDay[s.day].push(s); });
                        (t.gs_schedules || []).forEach(s => { if (gsByDay[s.day]) gsByDay[s.day].push(s); });

                        // Compute this week's Mon-Fri calendar dates
                        const _today = new Date();
                        const _dow = _today.getDay(); // 0=Sun
                        const _mon = new Date(_today);
                        _mon.setDate(_today.getDate() - (_dow === 0 ? 6 : _dow - 1));
                        const WEEK_DATES = {};
                        DAYS.forEach((dn, i) => {
                            const d = new Date(_mon);
                            d.setDate(_mon.getDate() + i);
                            WEEK_DATES[dn] = d.toLocaleDateString('en-US', {month:'short', day:'numeric'});
                        });

                        const hasSchedules = (t.jh_schedules || []).length > 0 || (t.gs_schedules || []).length > 0;
                        let ttHtml = `<tr id="tt-${t.id}" style="display:${hasSchedules ? 'table-row' : 'none'};"><td colspan="6" style="padding:0;"><div style="padding:.75rem 1rem;background:var(--bg-primary);border-top:1px dashed var(--border-color);">`;
                        ttHtml += `<div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.05em;margin-bottom:.6rem;">Weekly Timetable</div>`;
                        ttHtml += `<div style="overflow-x:auto;"><table style="width:100%;border-collapse:collapse;font-size:.76rem;min-width:520px;"><thead><tr style="border-bottom:1px solid var(--border-color);">`;
                        ttHtml += `<th style="padding:.35rem .6rem;text-align:left;color:var(--text-secondary);font-weight:600;">Day</th>`;
                        ttHtml += `<th style="padding:.35rem .6rem;text-align:left;color:#3b82f6;font-weight:600;">JH Schedule</th>`;
                        ttHtml += `<th style="padding:.35rem .6rem;text-align:left;color:#10b981;font-weight:600;">GS Schedule</th>`;
                        ttHtml += `</tr></thead><tbody>`;
                        DAYS.forEach(day => {
                            const jhCells = jhByDay[day].map(s => `<span style="display:inline-block;margin:.1rem .2rem;padding:.15rem .45rem;background:rgba(59,130,246,.1);border-radius:.25rem;color:#1d4ed8;">${s.start ? s.start.substring(0,5) : ''}–${s.end ? s.end.substring(0,5) : ''} ${s.subject}${s.section ? ' ('+s.section+')' : ''}</span>`).join('') || '<span style="color:var(--text-secondary);">—</span>';
                            const gsCells = gsByDay[day].map(s => `<span style="display:inline-block;margin:.1rem .2rem;padding:.15rem .45rem;background:rgba(16,185,129,.1);border-radius:.25rem;color:#065f46;">${s.start ? s.start.substring(0,5) : ''}–${s.end ? s.end.substring(0,5) : ''} ${s.subject}${s.section ? ' ('+s.section+')' : ''}</span>`).join('') || '<span style="color:var(--text-secondary);">—</span>';
                            const isToday = _today.toLocaleDateString('en-US',{month:'short',day:'numeric'}) === WEEK_DATES[day];
                            const dayLabel = `<span style="font-weight:600;color:var(--text-primary);">${day}</span><br><span style="font-size:.7rem;color:${isToday ? '#2d7a50' : 'var(--text-secondary)'};font-weight:${isToday ? '700' : '400'};">${WEEK_DATES[day]}${isToday ? ' ✦' : ''}</span>`;
                            ttHtml += `<tr style="border-bottom:1px solid var(--border-color);${isToday ? 'background:rgba(45,122,80,.04);' : ''}"><td style="padding:.35rem .6rem;white-space:nowrap;">${dayLabel}</td><td style="padding:.35rem .6rem;">${jhCells}</td><td style="padding:.35rem .6rem;">${gsCells}</td></tr>`;
                        });
                        ttHtml += `</tbody></table></div></div></td></tr>`;
                        html += ttHtml;
                    });
                    html += '</tbody></table></div></div>';
                });

                content.innerHTML = html;
                // Toggle timetable rows on teacher row click
                content.querySelectorAll('tr[data-tt]').forEach(row => {
                    row.style.cursor = 'pointer';
                    row.addEventListener('click', () => {
                        const ttRow = document.getElementById('tt-' + row.dataset.tt);
                        if (ttRow) ttRow.style.display = ttRow.style.display === 'none' ? 'table-row' : 'none';
                    });
                });
            })
            .catch(() => {
                document.getElementById('jhSpLoading').style.display = 'none';
                document.getElementById('jhSpContent').style.display = 'block';
                document.getElementById('jhSpContent').innerHTML = '<p style="color:#ef4444;font-size:.85rem;">Could not load shared teacher data.</p>';
                document.getElementById('jhSpBadge').textContent = 'Error';
            });
        }
        // Auto-badge on page load (panel stays collapsed unless conflicts found)
        document.addEventListener('DOMContentLoaded', function() {
            fetch('/api/shared-teachers-panel', {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
            })
            .then(r => r.json())
            .then(d => {
                const badge = document.getElementById('jhSpBadge');
                if (!d.success || !d.data.length) {
                    badge.textContent = 'No shared teachers';
                    badge.style.background = 'rgba(16,185,129,.1)';
                    badge.style.color = '#065f46';
                } else if (d.conflict_count > 0) {
                    badge.textContent = d.conflict_count + ' conflict' + (d.conflict_count > 1 ? 's' : '');
                    badge.style.background = 'rgba(239,68,68,.12)';
                    badge.style.color = '#b91c1c';
                    // Auto-open if there are conflicts
                    const body = document.getElementById('jhSpBody');
                    const chevron = document.getElementById('jhSpChevron');
                    body.style.display = 'block';
                    chevron.style.transform = 'rotate(180deg)';
                    jhSpLoaded = true;
                    document.getElementById('jhSpLoading').style.display = 'none';
                    jhLoadSharedPanel();
                } else {
                    badge.textContent = d.data.length + ' shared';
                    badge.style.background = 'rgba(16,185,129,.1)';
                    badge.style.color = '#065f46';
                }
            })
            .catch(() => {
                document.getElementById('jhSpBadge').textContent = '—';
            });
        });
    </script>

    {{-- ===== Schedule Card Preview Modal ===== --}}
    <div id="scheduleCardModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:2000;align-items:center;justify-content:center;padding:1rem;">
        <div style="background:#fff;border-radius:.75rem;width:96%;max-width:1000px;max-height:93vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 10px 40px rgba(0,0,0,.35);">
            {{-- Modal header --}}
            <div style="padding:.75rem 1rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e5e7eb;flex-shrink:0;">
                <span style="font-weight:700;font-size:.9rem;color:#1a4731;">Master Loading Schedule &mdash; Card Preview</span>
                <div style="display:flex;gap:.5rem;align-items:center;">
                    <button onclick="document.getElementById('scheduleCardFrame').contentWindow.print()"
                            style="padding:.35rem .85rem;background:#1a4731;color:#fff;border:none;border-radius:.4rem;font-size:.8rem;font-weight:600;cursor:pointer;">
                        Print
                    </button>
                    <button onclick="closeScheduleCard()"
                            style="padding:.35rem .85rem;background:none;border:1px solid #d1d5db;border-radius:.4rem;font-size:.8rem;cursor:pointer;color:#6b7280;">
                        &times; Close
                    </button>
                </div>
            </div>
            {{-- Loading spinner --}}
            <div id="scheduleCardSpinner" style="display:flex;justify-content:center;align-items:center;padding:3rem;flex-shrink:0;">
                <div style="width:2.5rem;height:2.5rem;border:4px solid #e5e7eb;border-top-color:#1a4731;border-radius:50%;animation:spin .7s linear infinite;"></div>
            </div>
            {{-- Iframe content --}}
            <iframe id="scheduleCardFrame" src="about:blank"
                    style="display:none;flex:1;border:none;min-height:660px;"></iframe>
        </div>
    </div>
    <style>@keyframes spin{to{transform:rotate(360deg)}}</style>

@endsection
