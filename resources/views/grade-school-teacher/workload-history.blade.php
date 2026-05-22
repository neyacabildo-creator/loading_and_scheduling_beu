{{-- Grade School: Faculty Workload History --}}
@extends('layouts.grade-school-teacher')

@section('title', 'Faculty Workload History')

@section('content')
<style>
    .header-bar{background:linear-gradient(135deg,var(--green-primary) 0%,#0d3d20 100%);color:white;padding:2rem;border-radius:.75rem;margin-bottom:2rem}
    .header-title{font-size:1.875rem;font-weight:bold;margin:0 0 .5rem}
    .stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:2rem}
    .stat-box{background:var(--bg-secondary);padding:1.25rem;border-radius:.5rem;border:1px solid var(--border-color)}
    .stat-num{font-size:1.75rem;font-weight:bold;color:var(--green-primary)}
    .stat-lbl{font-size:.8rem;color:var(--text-secondary);margin-top:.25rem}
    .filter-card{background:var(--bg-secondary);border-radius:.75rem;padding:1.25rem 1.5rem;border:1px solid var(--border-color);margin-bottom:2rem;display:flex;gap:1.25rem;align-items:center;flex-wrap:wrap}
    .filter-group{display:flex;align-items:center;gap:.5rem}
    .filter-group label{font-weight:600;font-size:.875rem;color:var(--text-primary)}
    .filter-group select,.filter-group input{padding:.45rem .75rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-primary);color:var(--text-primary);font-size:.875rem}
    .filter-group select:focus,.filter-group input:focus{outline:none;border-color:var(--green-primary)}
    .btn-refresh{background:transparent;color:var(--green-primary);padding:.5rem 1.25rem;border:1.5px solid var(--green-primary);border-radius:.375rem;cursor:pointer;font-weight:600;font-size:.85rem;margin-left:auto;transition:all .2s}
    .btn-refresh:hover{background:var(--green-primary);color:white}
    .table-card{background:var(--bg-secondary);border-radius:.75rem;padding:1.5rem;border:1px solid var(--border-color)}
    .card-title{font-size:1.1rem;font-weight:600;color:var(--text-primary);margin-bottom:1.25rem}
    .hist-table{width:100%;border-collapse:collapse}
    .hist-table thead{background:linear-gradient(135deg,var(--green-primary) 0%,#0d3d20 100%);color:white}
    .hist-table th,.hist-table td{padding:.8rem 1rem;text-align:left;font-size:.875rem}
    .hist-table th{font-weight:600}
    .hist-table tbody tr{border-top:1px solid var(--border-color)}
    .hist-table tbody tr:hover{background:rgba(45,122,80,.04)}
    .badge{display:inline-block;padding:.2rem .65rem;border-radius:.25rem;font-size:.75rem;font-weight:600}
    .badge-active{background:rgba(76,175,80,.12);color:#2e7d32}
    .badge-inactive{background:rgba(158,158,158,.15);color:#616161}
    .load-bar-wrap{background:#e0e0e0;border-radius:4px;height:8px;min-width:80px}
    .load-bar{background:var(--green-primary);height:8px;border-radius:4px;transition:width .4s}
    .empty-state{text-align:center;padding:2.5rem;color:var(--text-secondary)}
</style>

<div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;">
    <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin:0 0 .3rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Grade School Division</p>
    <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">Workload History</h1>
    <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">View workload history records for all Grade School faculty members</p>
</div>

<!-- Stats -->
<div class="stats-row">
    <div class="stat-box"><div class="stat-num" id="total-records">0</div><div class="stat-lbl">Total Records</div></div>
    <div class="stat-box"><div class="stat-num" id="total-faculty">0</div><div class="stat-lbl">Faculty Members</div></div>
    <div class="stat-box"><div class="stat-num" id="avg-units">0</div><div class="stat-lbl">Avg Units/Faculty</div></div>
    <div class="stat-box"><div class="stat-num" id="max-units">0</div><div class="stat-lbl">Highest Load</div></div>
</div>

<!-- Filters -->
<div class="filter-card">
    <div class="filter-group">
        <label>Search Faculty:</label>
        <input type="text" id="search-input" placeholder="Name or subject..." oninput="applyFilters()">
    </div>
    <div class="filter-group">
        <label>Status:</label>
        <select id="status-filter" onchange="applyFilters()">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>
    <button class="btn-refresh" onclick="loadHistory()"> Refresh</button>
</div>

<!-- Table -->
<div class="table-card">
    <p class="card-title"> Workload Records</p>
    <div style="overflow-x:auto">
        <table class="hist-table">
            <thead>
                <tr><th>#</th><th>Faculty</th><th>Subject</th><th>Grade Level</th><th>Units</th><th>Load</th><th>School Year</th><th>Status</th></tr>
            </thead>
            <tbody id="hist-tbody">
                <tr><td colspan="8" class="empty-state">Loading history...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let allHistory = [];

async function loadHistory() {
    try {
        const res = await fetch('/api/grade-school-teacher/workload-history');
        const json = await res.json();
        allHistory = json.data || [];
        updateStats(allHistory);
        renderTable(allHistory);
    } catch {
        document.getElementById('hist-tbody').innerHTML = '<tr><td colspan="8" class="empty-state">Failed to load workload history.</td></tr>';
    }
}

function updateStats(data) {
    document.getElementById('total-records').textContent = data.length;
    const facs = [...new Set(data.map(d => d.faculty_id))];
    document.getElementById('total-faculty').textContent = facs.length;
    const units = data.map(d => parseInt(d.load_hours) || parseInt(d.units) || 0);
    const total = units.reduce((a, b) => a + b, 0);
    document.getElementById('avg-units').textContent = facs.length ? (total / facs.length).toFixed(1) : 0;
    document.getElementById('max-units').textContent = units.length ? Math.max(...units) : 0;
}

function applyFilters() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    let data = allHistory;
    if (search) {
        data = data.filter(d =>
            ((d.faculty?.first_name ?? '') + ' ' + (d.faculty?.last_name ?? '')).toLowerCase().includes(search) ||
            (d.subject ?? '').toLowerCase().includes(search)
        );
    }
    if (status) data = data.filter(d => (d.status ?? 'active') === status);
    renderTable(data);
}

function renderTable(data) {
    if (!data.length) {
        document.getElementById('hist-tbody').innerHTML = '<tr><td colspan="8" class="empty-state">No records found.</td></tr>';
        return;
    }
    const maxLoad = Math.max(...data.map(d => parseInt(d.load_hours) || parseInt(d.units) || 0), 1);
    document.getElementById('hist-tbody').innerHTML = data.map((d, i) => {
        const faculty = d.faculty ? `${d.faculty.first_name} ${d.faculty.last_name}` : `Faculty #${d.faculty_id}`;
        const units = parseInt(d.load_hours) || parseInt(d.units) || 0;
        const pct = Math.round((units / maxLoad) * 100);
        const st = d.status ?? 'active';
        const color = units > 24 ? '#d32f2f' : units > 18 ? '#f57f17' : 'var(--green-primary)';
        return `
            <tr>
                <td>${i + 1}</td>
                <td>${faculty}</td>
                <td>${d.subject ?? '–'}</td>
                <td>${d.grade_level ?? '–'}</td>
                <td><strong>${units}</strong></td>
                <td>
                    <div class="load-bar-wrap">
                        <div class="load-bar" style="width:${pct}%;background:${color}"></div>
                    </div>
                </td>
                <td>${d.academic_year ?? '–'}</td>
                <td><span class="badge badge-${st}">${st}</span></td>
            </tr>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', loadHistory);
</script>
@endsection
