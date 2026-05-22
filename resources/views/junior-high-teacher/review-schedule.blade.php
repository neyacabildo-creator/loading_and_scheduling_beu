{{-- Junior High: Review Schedule --}}
@extends('layouts.teacher')

@section('title', 'Review Schedule')

@section('content')
<style>
    .header-bar{background:linear-gradient(135deg,var(--green-primary) 0%,#0d3d20 100%);color:white;padding:2rem;border-radius:.75rem;margin-bottom:2rem}
    .header-title{font-size:1.875rem;font-weight:bold;margin:0 0 .5rem}
    .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
    .stat-box{background:var(--bg-secondary);padding:1.25rem;border-radius:.5rem;border:1px solid var(--border-color)}
    .stat-num{font-size:1.75rem;font-weight:bold;color:var(--green-primary)}
    .stat-lbl{font-size:.8rem;color:var(--text-secondary);margin-top:.25rem}
    .filter-card{background:var(--bg-secondary);border-radius:.75rem;padding:1.25rem 1.5rem;border:1px solid var(--border-color);margin-bottom:2rem;display:flex;gap:1.25rem;align-items:center;flex-wrap:wrap}
    .filter-group{display:flex;align-items:center;gap:.5rem}
    .filter-group label{font-weight:600;font-size:.875rem;color:var(--text-primary)}
    .filter-group select,.filter-group input{padding:.45rem .75rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-primary);color:var(--text-primary);font-size:.875rem}
    .filter-group select:focus,.filter-group input:focus{outline:none;border-color:var(--green-primary)}
    .btn-primary{background:var(--green-primary);color:white;padding:.5rem 1.25rem;border:none;border-radius:.375rem;cursor:pointer;font-weight:600;font-size:.875rem;margin-left:auto;transition:all .2s}
    .btn-primary:hover{background:#1a5c3a}
    .table-card{background:var(--bg-secondary);border-radius:.75rem;padding:1.5rem;border:1px solid var(--border-color)}
    .card-title{font-size:1.1rem;font-weight:600;color:var(--text-primary);margin-bottom:1.25rem}
    .sched-table{width:100%;border-collapse:collapse}
    .sched-table thead{background:linear-gradient(135deg,var(--green-primary) 0%,#0d3d20 100%);color:white}
    .sched-table th,.sched-table td{padding:.8rem 1rem;text-align:left;font-size:.875rem}
    .sched-table th{font-weight:600}
    .sched-table tbody tr{border-top:1px solid var(--border-color)}
    .sched-table tbody tr:hover{background:rgba(45,122,80,.04)}
    .badge{display:inline-block;padding:.2rem .6rem;border-radius:.25rem;font-size:.75rem;font-weight:600}
    .badge-active{background:rgba(76,175,80,.12);color:#2e7d32}
    .badge-pending{background:rgba(255,152,0,.12);color:#e65100}
    .badge-inactive{background:rgba(158,158,158,.15);color:#616161}
    .empty-state{text-align:center;padding:3rem;color:var(--text-secondary)}
    .info-box{background:rgba(45,122,80,.07);border-left:4px solid var(--green-primary);padding:1rem;border-radius:.5rem;margin-bottom:1.5rem;font-size:.875rem;color:var(--text-primary)}
    .shared-tag{display:inline-block;margin-top:.25rem;padding:.15rem .55rem;border-radius:9999px;font-size:.68rem;font-weight:700;background:rgba(59,130,246,.12);color:#1d4ed8}
    .faculty-sub{font-size:.72rem;color:var(--text-secondary);margin-top:.15rem}
</style>



<div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;">
    <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">Review Schedule</h1>
    <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Review and validate class schedules for your subject team</p>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-box"><div class="stat-num" id="total-count">0</div><div class="stat-lbl">Total Schedules</div></div>
    <div class="stat-box"><div class="stat-num" id="active-count">0</div><div class="stat-lbl">Active</div></div>
    <div class="stat-box"><div class="stat-num" id="pending-count">0</div><div class="stat-lbl">Pending Review</div></div>
    <div class="stat-box"><div class="stat-num" id="faculty-count">0</div><div class="stat-lbl">Faculty Assigned</div></div>
</div>

<!-- Filters -->
<div class="filter-card">
    <div class="filter-group">
        <label>Status:</label>
        <select id="filter-status" onchange="applyFilters()">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    <div class="filter-group">
        <label>Search:</label>
        <input type="text" id="filter-search" placeholder="Subject, section, faculty..." oninput="applyFilters()">
    </div>
    <button class="btn-primary" onclick="loadSchedules()"> Refresh</button>
</div>

<!-- Table -->
<div class="table-card">
    <p class="card-title"> Schedule Listing</p>
    <div style="overflow-x:auto">
        <table class="sched-table">
            <thead>
                <tr><th>Subject</th><th>Faculty</th><th>Grade/Section</th><th>Day &amp; Date</th><th>Time</th><th>Room</th><th>Status</th></tr>
            </thead>
            <tbody id="sched-tbody">
                <tr><td colspan="7" class="empty-state">Loading schedules...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let allSchedules = [];

async function loadSchedules() {
    try {
        const res = await fetch('/api/stl/schedules-for-review');
        const json = await res.json();
        // Handle both array and object response formats
        allSchedules = Array.isArray(json) ? json : (json.data ?? json.schedules ?? []);
        updateStats(allSchedules);
        renderTable(allSchedules);
    } catch {
        document.getElementById('sched-tbody').innerHTML = '<tr><td colspan="7" class="empty-state">No schedule data available.</td></tr>';
    }
}

function updateStats(data) {
    document.getElementById('total-count').textContent = data.length;
    document.getElementById('active-count').textContent = data.filter(s => s.status === 'active').length;
    document.getElementById('pending-count').textContent = data.filter(s => s.status === 'pending').length;
    document.getElementById('faculty-count').textContent = [...new Set(data.map(s => s.faculty_id).filter(Boolean))].length;
}

function applyFilters() {
    const status = document.getElementById('filter-status').value;
    const search = document.getElementById('filter-search').value.toLowerCase();
    let data = allSchedules;
    if (status) data = data.filter(s => s.status === status);
    if (search) data = data.filter(s =>
        (s.subject ?? '').toLowerCase().includes(search) ||
        (s.grade_section ?? s.grade_level ?? s.section_name ?? '').toLowerCase().includes(search) ||
        ((s.faculty?.first_name ?? '') + ' ' + (s.faculty?.last_name ?? '')).toLowerCase().includes(search)
    );
    renderTable(data);
}

function formatSchedDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return '';
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function facultyCell(s) {
    const name = s.faculty
        ? (s.faculty.name || `${s.faculty.first_name || ''} ${s.faculty.last_name || ''}`.trim())
        : (s.faculty_id ? `#${s.faculty_id}` : '–');
    if (!s.is_shared_teacher) {
        return `<div style="font-weight:600;">${name}</div>`;
    }
    const dept = s.shared_from_department || 'Cross-department';
    return `<div style="font-weight:600;">${name}</div>
        <span class="shared-tag">Shared Teacher</span>
        <div class="faculty-sub">From: ${dept}</div>`;
}

function renderTable(data) {
    if (!data.length) {
        document.getElementById('sched-tbody').innerHTML = '<tr><td colspan="7" class="empty-state">No schedules found.</td></tr>';
        return;
    }
    document.getElementById('sched-tbody').innerHTML = data.map(s => {
        const cls = s.status === 'active' ? 'badge-active' : s.status === 'pending' ? 'badge-pending' : 'badge-inactive';
        const gradeSection = s.grade_section || (s.grade_level ? `${s.grade_level}${s.section_name ? ' – ' + s.section_name : ''}` : (s.section_name || '–'));
        const time = s.start_time && s.end_time ? `${s.start_time} – ${s.end_time}` : '–';
        const dateLabel = formatSchedDate(s.schedule_date);
        const dayCell = `${s.day_of_week ?? '–'}${dateLabel ? '<br><small style="color:var(--text-secondary);font-size:.75rem;">' + dateLabel + '</small>' : ''}`;
        return `
            <tr>
                <td>${s.subject ?? '–'}</td>
                <td>${facultyCell(s)}</td>
                <td>${gradeSection}</td>
                <td>${dayCell}</td>
                <td>${time}</td>
                <td>${s.room_label ?? '—'}</td>
                <td><span class="badge ${cls}">${s.status ?? '–'}</span></td>
            </tr>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', loadSchedules);
</script>
@endsection
