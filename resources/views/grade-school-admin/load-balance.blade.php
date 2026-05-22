{{-- resources/views/grade-school-admin/load-balance.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Faculty Load Balance — Grade School')

@section('content')
<style>
    .lb-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
    .btn-primary { padding:.65rem 1.4rem; background:var(--green-primary); color:#fff; border:none; border-radius:.5rem; font-weight:600; font-size:.875rem; cursor:pointer; transition:all .2s; }
    .btn-primary:hover { filter:brightness(1.1); }
    .stat-row4 { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
    @media(max-width:900px){ .stat-row4{grid-template-columns:repeat(2,1fr);} }
    @media(max-width:500px){ .stat-row4{grid-template-columns:1fr;} }
    .panel { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.75rem; box-shadow:var(--shadow-sm); margin-bottom:1.5rem; overflow:hidden; }
    .panel-header { padding:1.1rem 1.5rem; border-bottom:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem; }
    .panel-header h2 { font-size:1rem; font-weight:700; color:var(--text-primary); margin:0; }
    .panel-body { padding:1.25rem 1.5rem; }
    .teacher-list { display:flex; flex-direction:column; gap:.75rem; }
    .teacher-row { display:grid; grid-template-columns:220px 1fr auto auto; gap:.75rem; align-items:center; padding:.6rem .75rem; border-radius:.5rem; border:1px solid var(--border-color); background:var(--bg-primary); transition:background .15s; }
    .teacher-row:hover { background:var(--bg-tertiary); }
    @media(max-width:700px){ .teacher-row{grid-template-columns:1fr; gap:.4rem;} }
    .teacher-name { font-weight:600; font-size:.85rem; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .teacher-dept  { font-size:.72rem; color:var(--text-secondary); margin-top:.15rem; }
    .bar-wrap { position:relative; height:10px; background:var(--border-color); border-radius:9999px; overflow:hidden; }
    .bar-fill { height:100%; border-radius:9999px; transition:width .5s ease; }
    .bar-label { font-size:.72rem; color:var(--text-secondary); margin-top:.2rem; }
    .status-badge { display:inline-block; padding:.2rem .65rem; border-radius:9999px; font-size:.72rem; font-weight:700; white-space:nowrap; }
    .badge-overloaded  { background:rgba(239,68,68,.12);  color:#b91c1c; border:1px solid rgba(239,68,68,.3); }
    .badge-balanced    { background:rgba(16,185,129,.12); color:#065f46; border:1px solid rgba(16,185,129,.3); }
    .badge-underloaded { background:rgba(234,179,8,.12);  color:#92400e; border:1px solid rgba(234,179,8,.3); }
    .hours-text { font-weight:700; font-size:.83rem; color:var(--text-primary); white-space:nowrap; }
    .suggestions-list { display:flex; flex-direction:column; gap:.75rem; }
    .suggestion-card { padding:1rem 1.25rem; border-radius:.5rem; border-left:4px solid; display:flex; gap:1rem; align-items:flex-start; }
    .suggestion-card.high   { background:rgba(239,68,68,.06);  border-color:#ef4444; }
    .suggestion-card.medium { background:rgba(234,179,8,.06);  border-color:#f59e0b; }
    .suggestion-card.low    { background:rgba(16,185,129,.06); border-color:#10b981; }
    .suggestion-icon { flex-shrink:0; width:32px; height:32px; border-radius:.5rem; display:flex; align-items:center; justify-content:center; font-size:1rem; }
    .suggestion-icon.high   { background:rgba(239,68,68,.12); }
    .suggestion-icon.medium { background:rgba(234,179,8,.12); }
    .suggestion-icon.low    { background:rgba(16,185,129,.12); }
    .suggestion-msg { font-size:.83rem; color:var(--text-primary); line-height:1.5; }
    .suggestion-loads { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.5rem; }
    .load-chip { display:inline-block; padding:.15rem .6rem; background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:9999px; font-size:.7rem; color:var(--text-secondary); }
    .legend { display:flex; gap:1.25rem; flex-wrap:wrap; font-size:.75rem; color:var(--text-secondary); }
    .legend span { display:inline-flex; align-items:center; gap:.35rem; }
    .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .placeholder-wrap { padding:3rem; text-align:center; color:var(--text-secondary); }
    .loading-spinner { display:inline-block; width:1.5rem; height:1.5rem; border:3px solid var(--border-color); border-top-color:var(--green-primary); border-radius:50%; animation:spin .7s linear infinite; }
    @keyframes spin{ to{transform:rotate(360deg);} }
</style>

<!-- Page header -->
<div class="header">
    <div class="header-left">
        <div>
            <h1 class="page-title">Faculty Load Balance Optimizer</h1>
            <p style="font-size:.875rem;color:var(--text-secondary);margin:.15rem 0 0;">Identify overloaded and underutilized teachers, then act on smart rebalancing suggestions.</p>
        </div>
    </div>
    <div class="header-right">
        <button class="btn-primary" onclick="refreshData()">↺ Refresh</button>
    </div>
</div>

<div class="stat-row4">
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <p class="stat-label">Total Faculty</p>
                <p class="stat-value" id="statTotal">—</p>
                <p class="stat-change">With load assignments</p>
            </div>
            <div class="stat-icon success">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/></svg>
            </div>
        </div>
    </div>
    <div class="stat-card danger">
        <div class="stat-header">
            <div>
                <p class="stat-label">Overloaded</p>
                <p class="stat-value" id="statOverloaded">—</p>
                <p class="stat-change">&gt;30h or &gt;5 classes</p>
            </div>
            <div class="stat-icon danger">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
        </div>
    </div>
    <div class="stat-card warning">
        <div class="stat-header">
            <div>
                <p class="stat-label">Underutilized</p>
                <p class="stat-value" id="statUnderloaded">—</p>
                <p class="stat-change">&lt;6h assigned</p>
            </div>
            <div class="stat-icon warning">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
            </div>
        </div>
    </div>
    <div class="stat-card success">
        <div class="stat-header">
            <div>
                <p class="stat-label">Avg Load</p>
                <p class="stat-value" id="statAvg">—</p>
                <p class="stat-change">Hours per faculty</p>
            </div>
            <div class="stat-icon success">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Smart Rebalancing Suggestions</h2>
        <span style="font-size:.78rem;color:var(--text-secondary);">DSS-powered · auto-generated from current load data</span>
    </div>
    <div class="panel-body">
        <div id="suggestionsBody">
            <div class="placeholder-wrap">
                <div class="loading-spinner"></div>
                <p style="margin-top:.75rem;font-size:.85rem;">Analyzing load distribution…</p>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <h2>Load Distribution — All Faculty</h2>
        <div class="legend">
            <span><span class="legend-dot" style="background:#ef4444;"></span> Overloaded (&gt;30h)</span>
            <span><span class="legend-dot" style="background:#10b981;"></span> Balanced</span>
            <span><span class="legend-dot" style="background:#f59e0b;"></span> Underutilized (&lt;6h)</span>
        </div>
    </div>
    <div class="panel-body">
        <div id="teacherBarsBody">
            <div class="placeholder-wrap">
                <div class="loading-spinner"></div>
                <p style="margin-top:.75rem;font-size:.85rem;">Loading teacher data…</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const API_URL = '/api/grade-school-admin/load-balance/data';

    async function loadData() {
        try {
            const resp = await fetch(API_URL);
            const json = await resp.json();
            if (!json.success) { showError(); return; }
            render(json.data);
        } catch (e) { showError(); console.error(e); }
    }

    function showError() {
        document.getElementById('suggestionsBody').innerHTML = `<p style="color:#b91c1c;font-size:.85rem;text-align:center;">Failed to load data. Please refresh.</p>`;
        document.getElementById('teacherBarsBody').innerHTML = `<p style="color:#b91c1c;font-size:.85rem;text-align:center;">Failed to load data. Please refresh.</p>`;
    }

    function render(data) {
        const stats = data.stats;
        document.getElementById('statTotal').textContent      = stats.total;
        document.getElementById('statOverloaded').textContent = stats.overloaded;
        document.getElementById('statUnderloaded').textContent= stats.underloaded;
        document.getElementById('statAvg').textContent        = stats.avg_load_hours + 'h';

        const sugg = data.suggestions;
        const suggestionsEl = document.getElementById('suggestionsBody');
        if (!sugg || sugg.length === 0) {
            suggestionsEl.innerHTML = `<div style="text-align:center;padding:2rem;color:var(--text-secondary);">
                <div style="font-size:2rem;margin-bottom:.5rem;">✅</div>
                <p style="font-weight:600;color:var(--text-primary);">Load distribution looks balanced!</p>
                <p style="font-size:.83rem;">No rebalancing needed at this time.</p>
            </div>`;
        } else {
            const icons = { high: '⚠️', medium: '📋', low: 'ℹ️' };
            const list  = sugg.map(s => {
                const loadsHtml = s.loads_to_move && s.loads_to_move.length
                    ? `<div class="suggestion-loads">${s.loads_to_move.map(l =>
                        `<span class="load-chip">${l.subject || 'Load'} (${l.load_hours}h)</span>`
                      ).join('')}</div>` : '';
                return `<div class="suggestion-card ${s.priority || 'medium'}">
                    <div class="suggestion-icon ${s.priority || 'medium'}">${icons[s.priority] || '📋'}</div>
                    <div class="suggestion-msg">${s.message}${loadsHtml}</div>
                </div>`;
            }).join('');
            suggestionsEl.innerHTML = `<div class="suggestions-list">${list}</div>`;
        }

        const maxH = stats.max_hours || 1;
        const overloadPct = (30 / maxH) * 100;
        const teachers = data.teachers;
        if (!teachers || teachers.length === 0) {
            document.getElementById('teacherBarsBody').innerHTML = `<p style="text-align:center;color:var(--text-secondary);font-size:.85rem;">No faculty load records found.</p>`;
            return;
        }
        const barColors = { overloaded: '#ef4444', balanced: '#10b981', underloaded: '#f59e0b' };
        const rows = teachers.map(t => {
            const pct   = Math.min((t.total_hours / maxH) * 100, 100);
            const color = barColors[t.status] || '#10b981';
            const badge = t.status === 'overloaded'
                ? `<span class="status-badge badge-overloaded">Overloaded</span>`
                : t.status === 'underloaded'
                    ? `<span class="status-badge badge-underloaded">Underutilized</span>`
                    : `<span class="status-badge badge-balanced">Balanced</span>`;
            const depts = [...new Set((t.loads || []).map(l => l.department).filter(Boolean))].join(', ');
            return `<div class="teacher-row">
                <div>
                    <div class="teacher-name">${t.name}</div>
                    <div class="teacher-dept">${depts || 'No department'}</div>
                </div>
                <div>
                    <div class="bar-wrap" title="${t.total_hours}h / max ${maxH}h">
                        <div style="position:absolute;top:0;left:${overloadPct.toFixed(1)}%;width:2px;height:100%;background:rgba(239,68,68,.5);z-index:1;" title="Overload threshold (30h)"></div>
                        <div class="bar-fill" style="width:${pct.toFixed(1)}%;background:${color};"></div>
                    </div>
                    <div class="bar-label">${t.total_hours}h · ${t.total_classes} class(es)</div>
                </div>
                ${badge}
                <div class="hours-text">${t.total_hours}h</div>
            </div>`;
        }).join('');
        document.getElementById('teacherBarsBody').innerHTML = `<div class="teacher-list">${rows}</div>`;
    }

    window.refreshData = loadData;
    loadData();
})();
</script>
@endsection
