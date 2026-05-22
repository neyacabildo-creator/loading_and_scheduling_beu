{{-- resources/views/principal/shared-teacher-monitor.blade.php --}}
@extends('layouts.principal')

@section('title', 'Cross-Division Shared Teacher Monitor')

@section('extra-styles')
<style>
    /* ── Layout ───────────────────────────────────────────────────────────── */
    .monitor-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
    @media(max-width:900px){ .monitor-grid{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:500px){ .monitor-grid{grid-template-columns:1fr;} }

    /* ── Stat cards ──────────────────────────────────────────────────────── */
    .m-stat { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.75rem;
              padding:1.1rem 1.25rem; display:flex; align-items:center; gap:.9rem; }
    .m-stat-icon { width:44px; height:44px; border-radius:.5rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .m-stat-icon.green { background:rgba(45,122,80,.12); color:#2d7a50; }
    .m-stat-icon.blue  { background:rgba(59,130,246,.12); color:#3b82f6; }
    .m-stat-icon.amber { background:rgba(245,158,11,.12); color:#d97706; }
    .m-stat-icon.red   { background:rgba(239,68,68,.12);  color:#ef4444; }
    .m-stat-label { font-size:.78rem; color:var(--text-secondary); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
    .m-stat-val   { font-size:1.7rem; font-weight:800; color:var(--text-primary); line-height:1.1; }
    .m-stat-sub   { font-size:.75rem; color:var(--text-secondary); }

    /* ── Filters ─────────────────────────────────────────────────────────── */
    .filter-bar { display:flex; gap:.75rem; margin-bottom:1.25rem; flex-wrap:wrap; align-items:center; }
    .filter-bar input, .filter-bar select { padding:.6rem .9rem; border:1px solid var(--border-color); border-radius:.5rem;
        background:var(--bg-secondary); color:var(--text-primary); font-size:.875rem; }
    .filter-bar input:focus, .filter-bar select:focus { outline:none; border-color:#2d7a50; box-shadow:0 0 0 3px rgba(45,122,80,.1); }
    .filter-bar input { min-width:230px; }
    .btn-refresh { display:inline-flex; align-items:center; gap:.4rem; padding:.6rem 1.1rem; background:#2d7a50; color:white;
        border:none; border-radius:.5rem; font-weight:600; font-size:.875rem; cursor:pointer; margin-left:auto; }
    .btn-refresh:hover { background:#1a5336; }

    /* ── Table card ──────────────────────────────────────────────────────── */
    .tcard { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.75rem; overflow:hidden; }
    .tcard-head { padding:1rem 1.5rem; border-bottom:1px solid var(--border-color);
        display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
    .tcard-head h2 { font-size:1rem; font-weight:700; color:var(--text-primary); margin:0; }
    .tscroll { overflow-x:auto; }
    .mt { width:100%; border-collapse:collapse; min-width:900px; }
    .mt th { padding:.75rem 1.1rem; background:var(--bg-primary); text-align:left; font-size:.78rem;
        font-weight:700; color:var(--text-secondary); border-bottom:1px solid var(--border-color); white-space:nowrap;
        text-transform:uppercase; letter-spacing:.04em; }
    .mt td { padding:.85rem 1.1rem; border-bottom:1px solid var(--border-color); font-size:.85rem; color:var(--text-primary); vertical-align:middle; }
    .mt tr:last-child td { border-bottom:none; }
    .mt tr:hover td { background:var(--bg-primary); }

    /* ── Load bar ────────────────────────────────────────────────────────── */
    .load-bar-wrap { display:flex; align-items:center; gap:.5rem; }
    .load-bar-bg { flex:1; height:6px; border-radius:9999px; background:var(--border-color); min-width:60px; }
    .load-bar-fill { height:100%; border-radius:9999px; transition:width .4s; }
    .load-bar-fill.ok     { background:#22c55e; }
    .load-bar-fill.warn   { background:#f59e0b; }
    .load-bar-fill.over   { background:#ef4444; }
    .load-bar-text { font-size:.72rem; color:var(--text-secondary); white-space:nowrap; }

    /* ── Badges ──────────────────────────────────────────────────────────── */
    .badge { display:inline-block; padding:.2rem .65rem; border-radius:9999px; font-size:.72rem; font-weight:700; }
    .badge-ok     { background:rgba(34,197,94,.12); color:#166534; }
    .badge-warn   { background:rgba(245,158,11,.12); color:#92400e; }
    .badge-over   { background:rgba(239,68,68,.12);  color:#991b1b; }
    .badge-both   { background:rgba(59,130,246,.12); color:#1e40af; }
    .badge-jh     { background:rgba(45,122,80,.12);  color:#2d7a50; }
    .badge-gs     { background:rgba(168,85,247,.12); color:#7e22ce; }
    .badge-conflict{ background:rgba(239,68,68,.15); color:#b91c1c; border:1px solid rgba(239,68,68,.3); }

    /* ── Conflict alert row ──────────────────────────────────────────────── */
    .conflict-row td { background:rgba(254,226,226,.35) !important; }
    html[data-theme="dark"] .conflict-row td { background:rgba(127,29,29,.18) !important; }

    /* ── Info cell ───────────────────────────────────────────────────────── */
    .teacher-name { font-weight:700; color:var(--text-primary); }
    .teacher-email { font-size:.72rem; color:var(--text-secondary); }
    .division-sub { font-size:.73rem; color:var(--text-secondary); margin-top:.15rem; }

    /* ── View btn ────────────────────────────────────────────────────────── */
    .btn-view { display:inline-flex; align-items:center; gap:.3rem; padding:.35rem .75rem; background:transparent;
        border:1px solid var(--border-color); color:var(--text-primary); border-radius:.4rem; font-size:.78rem;
        font-weight:600; cursor:pointer; transition:all .15s; }
    .btn-view:hover { border-color:#2d7a50; color:#2d7a50; background:rgba(45,122,80,.06); }

    /* ── Detail Modal ────────────────────────────────────────────────────── */
    .dm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.55); z-index:2000; display:flex; align-items:center; justify-content:center; padding:1rem; }
    .dm-box { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.85rem;
        width:100%; max-width:940px; max-height:90vh; overflow-y:auto; }
    .dm-header { padding:1.25rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; }
    .dm-header h3 { font-size:1.05rem; font-weight:700; color:var(--text-primary); margin:0; }
    .dm-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-secondary); line-height:1; }
    .dm-body { padding:1.25rem 1.5rem; }
    .dm-cols { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:1rem; }
    @media(max-width:640px){ .dm-cols{grid-template-columns:1fr;} }
    .dm-col-head { font-size:.82rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:.5rem .75rem;
        border-radius:.4rem; margin-bottom:.75rem; }
    .dm-col-head.jh { background:rgba(45,122,80,.12); color:#2d7a50; }
    .dm-col-head.gs { background:rgba(168,85,247,.12); color:#7e22ce; }
    .sched-item { padding:.5rem .75rem; border:1px solid var(--border-color); border-radius:.4rem; margin-bottom:.4rem; font-size:.82rem; }
    .sched-item.conflict { border-color:rgba(239,68,68,.5); background:rgba(254,226,226,.25); }
    html[data-theme="dark"] .sched-item.conflict { border-color:rgba(239,68,68,.4); background:rgba(127,29,29,.18); }
    .sched-subject { font-weight:700; color:var(--text-primary); }
    .sched-meta { color:var(--text-secondary); font-size:.75rem; margin-top:.1rem; }
    .conflict-list { background:rgba(254,226,226,.25); border:1px solid rgba(239,68,68,.3); border-radius:.5rem; padding:1rem; margin-top:1rem; }
    html[data-theme="dark"] .conflict-list { background:rgba(127,29,29,.18); }
    .conflict-item { padding:.5rem 0; border-bottom:1px solid rgba(239,68,68,.15); font-size:.82rem; }
    .conflict-item:last-child { border-bottom:none; }
    .no-data { padding:2.5rem; text-align:center; color:var(--text-secondary); font-size:.875rem; }

    /* ── Loading spinner ─────────────────────────────────────────────────── */
    .spinner { display:inline-block; width:1.4rem; height:1.4rem; border:3px solid var(--border-color);
        border-top-color:#2d7a50; border-radius:50%; animation:spin .7s linear infinite; }
    @keyframes spin{ to{ transform:rotate(360deg); } }

    /* ── Problem panel ───────────────────────────────────────────────────── */
    .problem-panel { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.75rem; padding:1.25rem 1.5rem; margin-bottom:1.5rem; }
    .problem-panel h3 { font-size:.95rem; font-weight:700; color:var(--text-primary); margin:0 0 .75rem; }
    .problem-list { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:.65rem; }
    .problem-item { display:flex; gap:.65rem; align-items:flex-start; padding:.75rem; background:var(--bg-primary);
        border-radius:.5rem; border:1px solid var(--border-color); }
    .problem-icon { width:34px; height:34px; border-radius:.4rem; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1rem; }
    .problem-icon.solved { background:rgba(34,197,94,.12); }
    .problem-icon.partial { background:rgba(245,158,11,.12); }
    .problem-title { font-size:.8rem; font-weight:700; color:var(--text-primary); margin:0 0 .15rem; }
    .problem-desc  { font-size:.72rem; color:var(--text-secondary); line-height:1.4; }
</style>
@endsection

@section('content')

{{-- ════════════════════════════════════════════
  PAGE BANNER
══════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div>
        <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin:0 0 .3rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Principal — Cross-Division View</p>
        <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">Shared Teacher Monitor</h1>
        <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Real-time faculty load visibility across Junior High and Grade School — no manual cross-checking needed</p>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
        <div style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:.5rem;padding:.6rem 1rem;color:white;font-size:.8rem;text-align:center;">
            <div style="font-weight:700;font-size:1.1rem;" id="hdrBothCount">—</div>
            <div style="opacity:.8;">Shared Teachers</div>
        </div>
        <div style="background:rgba(239,68,68,.25);border:1px solid rgba(239,68,68,.4);border-radius:.5rem;padding:.6rem 1rem;color:white;font-size:.8rem;text-align:center;">
            <div style="font-weight:700;font-size:1.1rem;" id="hdrConflictCount">—</div>
            <div style="opacity:.8;">Time Conflicts</div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════
  WHAT THIS SOLVES — PROBLEM PANEL
══════════════════════════════════════════════ --}}
<div class="problem-panel">
    <h3>Solutions to Manual Scheduling Challenges</h3>
    <div class="problem-list">
        <div class="problem-item">
            <div class="problem-icon solved">✅</div>
            <div>
                <p class="problem-title">No more manual load-checking</p>
                <p class="problem-desc">See every teacher's JH and GS loads in one screen — no need to ask or wait for responses.</p>
            </div>
        </div>
        <div class="problem-item">
            <div class="problem-icon solved">✅</div>
            <div>
                <p class="problem-title">Instant cross-division conflict detection</p>
                <p class="problem-desc">The system automatically flags teachers scheduled at overlapping times in both divisions, including club time blocks.</p>
            </div>
        </div>
        <div class="problem-item">
            <div class="problem-icon solved">✅</div>
            <div>
                <p class="problem-title">Designation-aware load limits</p>
                <p class="problem-desc">Progress bars show how close each teacher is to their designation limit (Coordinator = 3 classes, Regular = 6 classes).</p>
            </div>
        </div>
        <div class="problem-item">
            <div class="problem-icon solved">✅</div>
            <div>
                <p class="problem-title">Plan JH loading with SHS visibility</p>
                <p class="problem-desc">See shared teacher availability before assigning JH loads — eliminates the 3–5 day wait to finish schedules.</p>
            </div>
        </div>
        <div class="problem-item">
            <div class="problem-icon partial">⚡</div>
            <div>
                <p class="problem-title">Combined total hours per teacher</p>
                <p class="problem-desc">JH + GS hours are summed automatically. Overloaded status shown in red — no manual minute/hour computation needed.</p>
            </div>
        </div>
        <div class="problem-item">
            <div class="problem-icon partial">⚡</div>
            <div>
                <p class="problem-title">Club time awareness</p>
                <p class="problem-desc">Blocked slots (club time, meetings) entered in any division appear as "busy" in conflict checks across both GS and JH.</p>
            </div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════
  STATS ROW
══════════════════════════════════════════════ --}}
<div class="monitor-grid">
    <div class="m-stat">
        <div class="m-stat-icon green">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <div>
            <div class="m-stat-label">Total Teachers Found</div>
            <div class="m-stat-val" id="statTotal">—</div>
            <div class="m-stat-sub">Across JH &amp; GS</div>
        </div>
    </div>
    <div class="m-stat">
        <div class="m-stat-icon blue">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        </div>
        <div>
            <div class="m-stat-label">In Both Divisions</div>
            <div class="m-stat-val" id="statBoth">—</div>
            <div class="m-stat-sub">Shared across JH + GS</div>
        </div>
    </div>
    <div class="m-stat">
        <div class="m-stat-icon amber">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <div class="m-stat-label">Overloaded</div>
            <div class="m-stat-val" id="statOver">—</div>
            <div class="m-stat-sub">Total hrs &gt; 24 hrs/week</div>
        </div>
    </div>
    <div class="m-stat">
        <div class="m-stat-icon red">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <div>
            <div class="m-stat-label">Time Conflicts</div>
            <div class="m-stat-val" id="statConflict">—</div>
            <div class="m-stat-sub">Overlapping JH &amp; GS slots</div>
        </div>
    </div>
</div>

{{-- ════════════════════════════════════════════
  FILTER BAR
══════════════════════════════════════════════ --}}
<div class="filter-bar">
    <input type="text" id="searchName" placeholder="Search teacher name or email…" oninput="renderTable()">
    <select id="filterStatus" onchange="renderTable()">
        <option value="">All Teachers</option>
        <option value="both">Shared (JH + GS)</option>
        <option value="jh_only">JH Only</option>
        <option value="gs_only">GS Only</option>
        <option value="conflict">With Conflicts</option>
        <option value="overloaded">Overloaded</option>
    </select>
    <button class="btn-refresh" onclick="loadData()">
        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Refresh Data
    </button>
</div>

{{-- ════════════════════════════════════════════
  MAIN TABLE
══════════════════════════════════════════════ --}}
<div class="tcard">
    <div class="tcard-head">
        <h2>Faculty Load Overview — Cross Division</h2>
        <span id="tableCount" style="font-size:.8rem;color:var(--text-secondary);"></span>
    </div>
    <div class="tscroll">
        <table class="mt">
            <thead>
                <tr>
                    <th>Teacher</th>
                    <th>Division</th>
                    <th style="min-width:180px;">JH Load (Classes / Hours)</th>
                    <th style="min-width:180px;">GS Load (Classes / Hours)</th>
                    <th>Total Hrs</th>
                    <th>Status</th>
                    <th>Conflicts</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="monitorTableBody">
                <tr>
                    <td colspan="8" class="no-data">
                        <div class="spinner" style="margin:0 auto 1rem;"></div>
                        <div>Loading cross-division data…</div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ════════════════════════════════════════════
  DETAIL MODAL
══════════════════════════════════════════════ --}}
<div class="dm-overlay" id="detailModal" style="display:none;" onclick="if(event.target===this)closeModal()">
    <div class="dm-box">
        <div class="dm-header">
            <h3 id="modalTeacherName">Teacher Detail</h3>
            <button class="dm-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="dm-body" id="modalBody">
            <div class="no-data"><div class="spinner" style="margin:0 auto;"></div></div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
let allData = [];

async function loadData() {
    const tbody = document.getElementById('monitorTableBody');
    tbody.innerHTML = '<tr><td colspan="8" class="no-data"><div class="spinner" style="margin:0 auto 1rem;"></div><div>Loading cross-division data…</div></td></tr>';

    try {
        const res = await fetch('/principal/api/shared-teacher-monitor', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Failed');
        allData = json.data;
        updateStats();
        renderTable();
    } catch(e) {
        tbody.innerHTML = `<tr><td colspan="8" class="no-data" style="color:#ef4444;">Failed to load data: ${e.message}</td></tr>`;
    }
}

function updateStats() {
    const total    = allData.length;
    const both     = allData.filter(t => t.in_jh && t.in_gs).length;
    const over     = allData.filter(t => t.total_hours > 24).length;
    const conflict = allData.filter(t => t.has_conflict).length;

    document.getElementById('statTotal').textContent    = total;
    document.getElementById('statBoth').textContent     = both;
    document.getElementById('statOver').textContent     = over;
    document.getElementById('statConflict').textContent = conflict;
    document.getElementById('hdrBothCount').textContent = both;
    document.getElementById('hdrConflictCount').textContent = conflict;
}

function renderTable() {
    const search = document.getElementById('searchName').value.toLowerCase();
    const filter = document.getElementById('filterStatus').value;

    let rows = allData.filter(t => {
        const name = (t.name + ' ' + t.email).toLowerCase();
        if (search && !name.includes(search)) return false;
        if (filter === 'both'      && !(t.in_jh && t.in_gs)) return false;
        if (filter === 'jh_only'   && !(t.in_jh && !t.in_gs)) return false;
        if (filter === 'gs_only'   && !(!t.in_jh && t.in_gs)) return false;
        if (filter === 'conflict'  && !t.has_conflict) return false;
        if (filter === 'overloaded'&& t.total_hours <= 24) return false;
        return true;
    });

    document.getElementById('tableCount').textContent = `${rows.length} teacher${rows.length !== 1 ? 's' : ''}`;

    if (!rows.length) {
        document.getElementById('monitorTableBody').innerHTML =
            '<tr><td colspan="8" class="no-data">No teachers match the current filters.</td></tr>';
        return;
    }

    document.getElementById('monitorTableBody').innerHTML = rows.map(t => {
        const divBadge = t.in_jh && t.in_gs
            ? '<span class="badge badge-both">JH + GS</span>'
            : t.in_jh
                ? '<span class="badge badge-jh">JH Only</span>'
                : '<span class="badge badge-gs">GS Only</span>';

        const jhBar  = loadBar(t.jh_hours, t.jh_max_hours, t.jh_classes, t.jh_max_classes);
        const gsBar  = loadBar(t.gs_hours, t.gs_max_hours, t.gs_classes, t.gs_max_classes);

        const totalHrs = t.total_hours.toFixed(1);
        const totalColor = t.total_hours > 24 ? '#ef4444' : t.total_hours > 20 ? '#d97706' : '#22c55e';

        const statusBadge = t.total_hours > 24
            ? '<span class="badge badge-over">Overloaded</span>'
            : t.total_hours > 20
                ? '<span class="badge badge-warn">Near Limit</span>'
                : '<span class="badge badge-ok">OK</span>';

        const conflictCell = t.has_conflict
            ? `<span class="badge badge-conflict">⚠ ${t.conflicts.length} conflict${t.conflicts.length > 1 ? 's' : ''}</span>`
            : '<span style="font-size:.8rem;color:var(--text-secondary);">None</span>';

        const rowClass = t.has_conflict ? 'conflict-row' : '';

        return `
        <tr class="${rowClass}">
            <td>
                <div class="teacher-name">${esc(t.name)}</div>
                <div class="teacher-email">${esc(t.email || '—')}</div>
            </td>
            <td>${divBadge}</td>
            <td>
                ${t.in_jh
                    ? `<div style="font-size:.8rem;font-weight:600;margin-bottom:.25rem;">${t.jh_classes} class${t.jh_classes !== 1 ? 'es' : ''} &bull; ${t.jh_hours.toFixed(1)} hrs</div>${jhBar}`
                    : '<span style="font-size:.8rem;color:var(--text-secondary);">Not assigned</span>'
                }
            </td>
            <td>
                ${t.in_gs
                    ? `<div style="font-size:.8rem;font-weight:600;margin-bottom:.25rem;">${t.gs_classes} class${t.gs_classes !== 1 ? 'es' : ''} &bull; ${t.gs_hours.toFixed(1)} hrs</div>${gsBar}`
                    : '<span style="font-size:.8rem;color:var(--text-secondary);">Not assigned</span>'
                }
            </td>
            <td><span style="font-weight:700;font-size:.95rem;color:${totalColor};">${totalHrs}</span> <span style="font-size:.73rem;color:var(--text-secondary);">hrs</span></td>
            <td>${statusBadge}</td>
            <td>${conflictCell}</td>
            <td>
                <button class="btn-view" onclick='openDetail(${JSON.stringify(t).replace(/'/g,"&apos;")})'>
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    View Detail
                </button>
            </td>
        </tr>`;
    }).join('');
}

function loadBar(hours, maxHours, classes, maxClasses) {
    const hPct  = maxHours  > 0 ? Math.min(100, Math.round(hours   / maxHours  * 100)) : 0;
    const cPct  = maxClasses> 0 ? Math.min(100, Math.round(classes  / maxClasses* 100)) : 0;
    const hCls  = hPct >= 100 ? 'over' : hPct >= 80 ? 'warn' : 'ok';
    const cCls  = cPct >= 100 ? 'over' : cPct >= 80 ? 'warn' : 'ok';
    return `
    <div class="load-bar-wrap" title="Hours: ${hours.toFixed(1)} / ${maxHours}">
        <div class="load-bar-bg"><div class="load-bar-fill ${hCls}" style="width:${hPct}%;"></div></div>
        <span class="load-bar-text">${hours.toFixed(1)}/${maxHours}h</span>
    </div>
    <div class="load-bar-wrap" style="margin-top:.2rem;" title="Classes: ${classes} / ${maxClasses}">
        <div class="load-bar-bg"><div class="load-bar-fill ${cCls}" style="width:${cPct}%;"></div></div>
        <span class="load-bar-text">${classes}/${maxClasses} cls</span>
    </div>`;
}

function openDetail(t) {
    document.getElementById('modalTeacherName').textContent = t.name + ' — Full Schedule Detail';
    const body = document.getElementById('modalBody');

    // Conflict IDs for fast lookup (jh schedule index, gs schedule index)
    const conflictedJH = new Set();
    const conflictedGS = new Set();
    t.conflicts.forEach(c => {
        t.jh_schedules.forEach((s, i) => { if (s.day === c.day && s.time === c.jh_time) conflictedJH.add(i); });
        t.gs_schedules.forEach((s, i) => { if (s.day === c.day && s.time === c.gs_time) conflictedGS.add(i); });
    });

    const jhHtml = t.jh_schedules.length
        ? t.jh_schedules.map((s, i) => `
            <div class="sched-item ${conflictedJH.has(i) ? 'conflict' : ''}">
                <div class="sched-subject">${esc(s.subject || '—')}</div>
                <div class="sched-meta">${esc(s.day || '—')} &bull; ${esc(s.time || '—')} ${s.section ? '&bull; ' + esc(s.section) : ''} ${conflictedJH.has(i) ? '<span style="color:#b91c1c;font-weight:700;"> ⚠ CONFLICT</span>' : ''}</div>
            </div>`).join('')
        : '<div style="font-size:.82rem;color:var(--text-secondary);">No JH schedules found.</div>';

    const gsHtml = t.gs_schedules.length
        ? t.gs_schedules.map((s, i) => `
            <div class="sched-item ${conflictedGS.has(i) ? 'conflict' : ''}">
                <div class="sched-subject">${esc(s.subject || '—')}</div>
                <div class="sched-meta">${esc(s.day || '—')} &bull; ${esc(s.time || '—')} ${s.section ? '&bull; ' + esc(s.section) : ''} ${conflictedGS.has(i) ? '<span style="color:#b91c1c;font-weight:700;"> ⚠ CONFLICT</span>' : ''}</div>
            </div>`).join('')
        : '<div style="font-size:.82rem;color:var(--text-secondary);">No GS schedules found.</div>';

    const conflictsHtml = t.has_conflict
        ? `<div class="conflict-list">
            <div style="font-weight:700;color:#b91c1c;margin-bottom:.6rem;font-size:.85rem;">⚠ ${t.conflicts.length} Time Conflict${t.conflicts.length > 1 ? 's' : ''} Detected</div>
            ${t.conflicts.map(c => `
                <div class="conflict-item">
                    <strong>${esc(c.day)}</strong> &mdash;
                    JH: <strong>${esc(c.jh_subject)}</strong> (${esc(c.jh_time)})
                    overlaps with GS: <strong>${esc(c.gs_subject)}</strong> (${esc(c.gs_time)})
                </div>`).join('')}
          </div>` : '';

    body.innerHTML = `
        <div style="display:flex;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:.5rem;padding:.6rem 1rem;flex:1;min-width:140px;">
                <div style="font-size:.73rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">JH Load</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">${t.jh_classes} classes &bull; ${t.jh_hours.toFixed(1)} hrs</div>
            </div>
            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:.5rem;padding:.6rem 1rem;flex:1;min-width:140px;">
                <div style="font-size:.73rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">GS Load</div>
                <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">${t.gs_classes} classes &bull; ${t.gs_hours.toFixed(1)} hrs</div>
            </div>
            <div style="background:var(--bg-primary);border:1px solid var(--border-color);border-radius:.5rem;padding:.6rem 1rem;flex:1;min-width:140px;">
                <div style="font-size:.73rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;">Total Combined</div>
                <div style="font-size:1.1rem;font-weight:700;color:${t.total_hours > 24 ? '#ef4444' : '#22c55e'};">${t.total_hours.toFixed(1)} hrs / week</div>
            </div>
        </div>
        ${conflictsHtml}
        <div class="dm-cols" style="margin-top:1rem;">
            <div>
                <div class="dm-col-head jh">Junior High School Schedules</div>
                ${jhHtml}
            </div>
            <div>
                <div class="dm-col-head gs">Grade School Schedules</div>
                ${gsHtml}
            </div>
        </div>`;

    document.getElementById('detailModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}

function esc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', loadData);
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
@endsection
