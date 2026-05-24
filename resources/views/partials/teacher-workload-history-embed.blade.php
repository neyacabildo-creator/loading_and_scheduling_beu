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
@php
    $historyApi = $historyApi ?? '/api/grade-school-teacher/workload-history';
@endphp
<!-- Stats -->
<div class="stats-row">
    <div class="stat-box"><div class="stat-num" id="total-records">0</div><div class="stat-lbl">Total Records</div></div>
    <div class="stat-box"><div class="stat-num" id="total-subjects">0</div><div class="stat-lbl">Subjects</div></div>
    <div class="stat-box"><div class="stat-num" id="avg-units">0</div><div class="stat-lbl">Avg Units/Record</div></div>
    <div class="stat-box"><div class="stat-num" id="max-units">0</div><div class="stat-lbl">Highest Load</div></div>
</div>

<!-- Filters -->
<div class="filter-card">
    <div class="filter-group">
        <label>Search:</label>
        <input type="text" id="search-input" placeholder="Subject, day, or grade..." oninput="applyFilters()">
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
                <tr><th>#</th><th>Day</th><th>Subject</th><th>Grade Level</th><th>Units</th><th>Load</th><th>School Year</th><th>Status</th></tr>
            </thead>
            <tbody id="hist-tbody">
                <tr><td colspan="8" class="empty-state">Loading history...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
@php
    $historyApi = $historyApi ?? '/api/grade-school-teacher/workload-history';
    $facultyLoadFallback = $facultyLoadFallback ?? null;
@endphp
const historyApi = @json($historyApi);
const facultyLoadFallback = @json($facultyLoadFallback);
let allHistory = [];

function fmtTime(t) {
    if (!t) return '';
    try {
        const [h, m] = String(t).split(':');
        const hour = parseInt(h, 10);
        return `${hour % 12 || 12}:${m} ${hour < 12 ? 'AM' : 'PM'}`;
    } catch (e) { return t; }
}

function gradeSectionLabel(s) {
    if (s.grade_section && s.grade_section !== '—') return s.grade_section;
    const parts = [s.grade_level, s.section_name || s.section].filter(Boolean);
    return parts.length ? parts.join(' – ') : '—';
}

function mapFacultyLoadRows(schedules) {
    return (schedules || []).map((s, idx) => ({
        id: s.id ?? idx,
        subject: s.subject || s.subject_name || '—',
        grade_level: s.grade_level || null,
        section: s.section_name || s.section || null,
        grade_section: gradeSectionLabel(s),
        room: s.room || gradeSectionLabel(s),
        day_of_week: s.day_of_week || '—',
        start_time: s.start_time,
        end_time: s.end_time,
        units: parseInt(s.units, 10) || parseInt(s.classes_assigned, 10) || 0,
        load_hours: parseFloat(s.load_hours) || 0,
        school_year: s.school_year || s.academic_year || '',
        status: s.status || 'active',
    }));
}

function isBlankField(value) {
    const v = String(value ?? '').trim();
    return v === '' || v === '—' || v.toLowerCase() === 'unassigned';
}

function pickRicherRow(a, b) {
    const out = { ...a };
    ['subject', 'subject_name', 'grade_level', 'section', 'section_name', 'grade_section', 'day_of_week', 'start_time', 'end_time', 'school_year', 'status'].forEach((field) => {
        if (isBlankField(out[field]) && !isBlankField(b[field])) {
            out[field] = b[field];
        }
    });
    out.units = Math.max(parseInt(out.units, 10) || 0, parseInt(b.units, 10) || 0);
    out.load_hours = Math.max(parseFloat(out.load_hours) || 0, parseFloat(b.load_hours) || 0);
    if (!out.grade_section || out.grade_section === '—') {
        out.grade_section = gradeSectionLabel(out);
    }
    return out;
}

function mergeWorkloadHistory(primary, fallback) {
    const merged = [...(primary || [])];
    const indexByKey = new Map();
    merged.forEach((row, idx) => {
        indexByKey.set(`${row.id ?? idx}|${(row.subject || '').toLowerCase()}|${(row.day_of_week || '').toLowerCase()}`, idx);
    });
    (fallback || []).forEach((row, idx) => {
        const key = `${row.id ?? idx}|${(row.subject || '').toLowerCase()}|${(row.day_of_week || '').toLowerCase()}`;
        if (indexByKey.has(key)) {
            merged[indexByKey.get(key)] = pickRicherRow(merged[indexByKey.get(key)], row);
            return;
        }
        indexByKey.set(key, merged.length);
        merged.push(row);
    });
    return merged;
}

async function loadHistory() {
    const tbody = document.getElementById('hist-tbody');
    try {
        const res = await fetch(historyApi, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const json = await res.json();
        if (!res.ok || json.success === false) throw new Error(json.message || 'Request failed');
        allHistory = json.data || [];
        if (facultyLoadFallback) {
            const fr = await fetch(facultyLoadFallback, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const fj = await fr.json();
            if (fr.ok) {
                const fallbackRows = mapFacultyLoadRows(fj.schedules || fj.data || []);
                allHistory = mergeWorkloadHistory(allHistory, fallbackRows);
            }
        }
        updateStats(allHistory);
        renderTable(allHistory);
    } catch (e) {
        if (facultyLoadFallback) {
            try {
                const fr = await fetch(facultyLoadFallback, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                const fj = await fr.json();
                allHistory = mapFacultyLoadRows(fj.schedules || fj.data || []);
                updateStats(allHistory);
                renderTable(allHistory);
                return;
            } catch (e2) {}
        }
        tbody.innerHTML = '<tr><td colspan="8" class="empty-state">Failed to load workload history.</td></tr>';
    }
}

function updateStats(data) {
    document.getElementById('total-records').textContent = data.length;
    const subjects = [...new Set(data.map(d => (d.subject || '').toLowerCase()).filter(s => s && s !== '—'))];
    document.getElementById('total-subjects').textContent = subjects.length;
    const unitVals = data.map(d => parseInt(d.units, 10) || 0);
    const totalUnits = unitVals.reduce((a, b) => a + b, 0);
    document.getElementById('avg-units').textContent = data.length ? (totalUnits / data.length).toFixed(1) : 0;
    document.getElementById('max-units').textContent = unitVals.length ? Math.max(...unitVals).toFixed(1) : 0;
}

function applyFilters() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    let data = allHistory;
    if (search) {
        data = data.filter(d =>
            (d.subject ?? '').toLowerCase().includes(search) ||
            (d.day_of_week ?? '').toLowerCase().includes(search) ||
            (d.grade_level ?? '').toLowerCase().includes(search) ||
            (d.grade_section ?? gradeSectionLabel(d)).toLowerCase().includes(search)
        );
    }
    if (status) data = data.filter(d => String(d.status ?? 'active').toLowerCase() === status);
    renderTable(data);
}

function renderTable(data) {
    if (!data.length) {
        document.getElementById('hist-tbody').innerHTML = '<tr><td colspan="8" class="empty-state">No workload records yet.</td></tr>';
        return;
    }
    const maxLoad = Math.max(...data.map(d => parseFloat(d.load_hours) || 0), 1);
    document.getElementById('hist-tbody').innerHTML = data.map((d, i) => {
        const day = d.day_of_week && d.day_of_week !== '—'
            ? d.day_of_week + (d.start_time ? '<br><small style="color:var(--text-secondary)">' + fmtTime(d.start_time) + (d.end_time ? ' – ' + fmtTime(d.end_time) : '') + '</small>' : '')
            : '—';
        const unitCount = parseInt(d.units, 10) || 0;
        const loadHrs = parseFloat(d.load_hours) || 0;
        const pct = Math.min(100, Math.round((loadHrs / maxLoad) * 100));
        const st = String(d.status ?? 'active').toLowerCase();
        const badgeClass = ['active', 'approved', 'available'].includes(st) ? 'active' : (st === 'inactive' ? 'inactive' : 'active');
        const color = loadHrs > 6 ? '#d32f2f' : loadHrs > 4 ? '#f57f17' : 'var(--green-primary)';
        const subjectLabel = (d.subject && d.subject !== '—') ? d.subject : (d.subject_name && d.subject_name !== '—' ? d.subject_name : '—');
        const gradeSection = (d.grade_section && d.grade_section !== '—') ? d.grade_section : gradeSectionLabel(d);
        return `
            <tr>
                <td>${i + 1}</td>
                <td>${day}</td>
                <td>${subjectLabel}</td>
                <td>${gradeSection}</td>
                <td><strong>${unitCount}</strong></td>
                <td>
                    <div class="load-bar-wrap">
                        <div class="load-bar" style="width:${pct}%;background:${color}"></div>
                    </div>
                    <small style="color:var(--text-secondary)">${loadHrs.toFixed(1)} hrs</small>
                </td>
                <td>${d.school_year || d.academic_year || '—'}</td>
                <td><span class="badge badge-${badgeClass}">${st}</span></td>
            </tr>
        `;
    }).join('');
}

document.addEventListener('DOMContentLoaded', loadHistory);
</script>