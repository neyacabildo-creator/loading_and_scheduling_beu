{{-- resources/views/junior-high-admin/dss-recommendations.blade.php --}}
@extends('layouts.admin')

@section('title', 'DSS Recommendations')

@section('content')
<style>
/* ── Layout ─────────────────────────────────────────────────────────────── */
.dss-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
.dss-header-actions { display:flex; gap:0.75rem; align-items:center; }

/* ── Stats bar ─────────────────────────────────────────────────────────── */
.dss-stats-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.dss-stat-card  { background:#fff; border:1px solid #e8dcc8; border-radius:0.75rem; padding:1.25rem 1rem; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.dss-stat-num   { font-size:2rem; font-weight:700; color:#2d3436; line-height:1; }
.dss-stat-lbl   { font-size:0.8rem; color:#7a7a6e; margin-top:0.35rem; }
.dss-stat-high  .dss-stat-num { color:#c83232; }
.dss-stat-mid   .dss-stat-num { color:#b8860b; }
.dss-stat-low   .dss-stat-num { color:#0c4a6e; }

/* ── Tabs ───────────────────────────────────────────────────────────────── */
.dss-tabs       { display:flex; gap:0; border-bottom:2px solid #e8dcc8; margin-bottom:1.5rem; }
.dss-tab-btn    { padding:0.6rem 1.2rem; border:none; background:transparent; cursor:pointer;
                  font-size:0.875rem; font-weight:500; color:#7a7a6e; border-bottom:3px solid transparent;
                  margin-bottom:-2px; transition:all .2s; }
.dss-tab-btn.active, .dss-tab-btn:hover { color:#2d7a50; border-bottom-color:#2d7a50; }

/* ── Filter bar ─────────────────────────────────────────────────────────── */
.filter-bar { display:flex; gap:0.75rem; margin-bottom:1.5rem; flex-wrap:wrap; }
.filter-btn { padding:0.4rem 1rem; border:1px solid #e8dcc8; background:#fff; border-radius:9999px;
              cursor:pointer; font-size:0.8rem; font-weight:500; transition:all .2s; white-space:nowrap; }
.filter-btn.active { background:#2d7a50; color:#fff; border-color:#2d7a50; }
.filter-btn:hover:not(.active) { border-color:#2d7a50; color:#2d7a50; }

/* ── Recommendation cards ───────────────────────────────────────────────── */
.recommendation-card { background:#fff; padding:1.5rem; border-radius:0.75rem;
                        border:1px solid #e8dcc8; margin-bottom:1.25rem;
                        box-shadow:0 1px 3px rgba(0,0,0,.07); }
.recommendation-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem; }
.recommendation-icon   { width:44px; height:44px; border-radius:0.5rem; display:flex;
                          align-items:center; justify-content:center; flex-shrink:0; }
.icon-conflict { background:rgba(239,68,68,.12);  color:#ef4444; }
.icon-balance  { background:rgba(245,158,11,.12); color:#f59e0b; }
.icon-optimize { background:rgba(34,197,94,.12);  color:#22c55e; }
.icon-improve  { background:rgba(59,130,246,.12); color:#3b82f6; }
.recommendation-title    { font-size:1rem; font-weight:600; color:#2d3436; margin-bottom:0.25rem; }
.recommendation-priority { display:inline-block; padding:0.2rem 0.65rem; border-radius:0.25rem;
                            font-size:0.72rem; font-weight:600; }
.priority-high   { background:#fee2e2; color:#c83232; }
.priority-medium { background:#fef3c7; color:#b8860b; }
.priority-low    { background:#dbeafe; color:#0c4a6e; }
.recommendation-details { font-size:0.875rem; color:#4b5563; line-height:1.65; margin-bottom:0.9rem; }
.rec-solution-box { display:flex; gap:0.6rem; background:rgba(45,122,80,.06);
                    padding:0.75rem; border-radius:0.5rem; font-size:0.875rem;
                    color:#2d3436; margin-bottom:0.9rem; }

/* ── Action buttons ─────────────────────────────────────────────────────── */
.action-btn { padding:0.45rem 1rem; border:none; border-radius:0.375rem; cursor:pointer;
              font-size:0.8rem; font-weight:600; transition:all .2s; text-decoration:none;
              display:inline-flex; align-items:center; gap:0.35rem; }
.btn-primary { background:linear-gradient(135deg,#2d7a50,#1a5336); color:#fff; }
.btn-primary:hover { box-shadow:0 4px 12px rgba(45,122,80,.3); transform:translateY(-1px); }
.btn-secondary { background:#f5f3ed; color:#2d3436; border:1px solid #e8dcc8; }
.btn-secondary:hover { border-color:#2d7a50; color:#2d7a50; }
.btn-dismiss { background:transparent; color:#9ca3af; border:1px solid #e8dcc8; }
.btn-dismiss:hover { background:#fee2e2; color:#c83232; border-color:#fca5a5; }

/* ── Notifications ──────────────────────────────────────────────────────── */
.dss-notification-item { display:flex; gap:0.9rem; align-items:flex-start;
                          padding:0.9rem 1rem; border-radius:0.5rem; margin-bottom:0.75rem; }
.dss-notif-conflict { background:rgba(239,68,68,.08);  border-left:4px solid #ef4444; }
.dss-notif-balance  { background:rgba(245,158,11,.08); border-left:4px solid #f59e0b; }
.dss-notif-optimize { background:rgba(34,197,94,.08);  border-left:4px solid #22c55e; }
.dss-notif-improve  { background:rgba(59,130,246,.08); border-left:4px solid #3b82f6; }
.dss-notif-icon  { flex-shrink:0; margin-top:2px; color:#4b5563; }
.dss-notif-title { font-weight:600; font-size:0.875rem; color:#2d3436; }
.dss-notif-msg   { font-size:0.8rem; color:#7a7a6e; margin-top:0.2rem; }

/* ── Tables ─────────────────────────────────────────────────────────────── */
.dss-table-wrap { overflow-x:auto; }
.dss-table { width:100%; border-collapse:collapse; font-size:0.875rem; }
.dss-table th { background:#f5f3ed; color:#7a7a6e; font-weight:600; padding:0.65rem 1rem;
                text-align:left; border-bottom:2px solid #e8dcc8; white-space:nowrap; }
.dss-table td { padding:0.65rem 1rem; border-bottom:1px solid #f0ece4; vertical-align:middle; }
.dss-table tr:last-child td { border-bottom:none; }
.dss-table tr:hover td { background:#faf9f6; }

/* ── Badges ─────────────────────────────────────────────────────────────── */
.dss-badge        { display:inline-block; padding:0.2rem 0.6rem; border-radius:9999px; font-size:0.72rem; font-weight:600; }
.badge-red    { background:#fee2e2; color:#c83232; }
.badge-yellow { background:#fef3c7; color:#b8860b; }
.badge-green  { background:#d1fae5; color:#065f46; }

/* ── Util bar ────────────────────────────────────────────────────────────── */
.dss-util-bar  { height:6px; background:#e8dcc8; border-radius:3px; min-width:80px; overflow:hidden; display:inline-block; width:80px; vertical-align:middle; margin-right:0.35rem; }
.dss-util-fill { height:100%; border-radius:3px; transition:width .4s; }
.util-high { background:#ef4444; }
.util-mid  { background:#2d7a50; }
.util-low  { background:#e8dcc8; }

/* ── Spinner ─────────────────────────────────────────────────────────────── */
#dss-spinner { display:none; justify-content:center; align-items:center; gap:0.75rem;
               padding:3rem; color:#7a7a6e; font-size:0.9rem; }
.spin { width:28px; height:28px; border:3px solid #e8dcc8; border-top-color:#2d7a50;
        border-radius:50%; animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Placeholder ─────────────────────────────────────────────────────────── */
#dss-placeholder { text-align:center; padding:3rem 1rem; color:#7a7a6e; }
#dss-placeholder svg { margin:0 auto 1rem; display:block; color:#d1d5db; }
#dss-placeholder p { font-size:0.95rem; }

/* ── Empty state ─────────────────────────────────────────────────────────── */
.dss-empty-state { text-align:center; padding:2.5rem; color:#7a7a6e; }
.dss-empty-state svg { margin:0 auto 0.75rem; display:block; }
.dss-empty-state p { font-size:0.9rem; }

/* ── Alert ───────────────────────────────────────────────────────────────── */
.dss-alert-error { background:#fee2e2; color:#c83232; padding:1rem 1.25rem;
                   border-radius:0.5rem; font-size:0.875rem; }

/* ── Modal ───────────────────────────────────────────────────────────────── */
#dss-detail-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5);
                    z-index:9999; align-items:center; justify-content:center; padding:1rem; }
.dss-modal-box    { background:#fff; border-radius:0.75rem; width:100%; max-width:540px;
                    max-height:85vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.dss-modal-header { display:flex; justify-content:space-between; align-items:center;
                    padding:1.25rem 1.5rem; border-bottom:1px solid #e8dcc8; }
.dss-modal-title  { font-size:1.05rem; font-weight:700; color:#2d3436; }
.dss-modal-close  { background:none; border:none; font-size:1.4rem; cursor:pointer;
                    color:#7a7a6e; line-height:1; }
.dss-modal-close:hover { color:#2d3436; }
.dss-modal-body   { padding:1.5rem; }
</style>

<script>
window.DSS_CONFIG = {
    analyzeUrl:       '{{ url("api/admin/dss/analyze") }}',
    workloadUrl:      '{{ url("api/admin/dss/workload") }}',
    roomsUrl:         '{{ url("api/admin/dss/rooms") }}',
    notificationsUrl: '{{ url("api/admin/dss/notifications") }}',
    csrfToken:        '{{ csrf_token() }}',
};
</script>

<div class="dss-header">
    <div>
        <h1 class="page-title">Decision Support Recommendations</h1>
        <p style="color:#7a7a6e;font-size:0.875rem;margin-top:0.25rem;">
            Rule-based scheduling analysis — faculty availability, load balance, room utilisation &amp; conflict detection.
        </p>
    </div>
    <div class="dss-header-actions">
        <button id="dss-run-btn" style="padding:0.7rem 1.4rem;background:linear-gradient(135deg,#2d7a50,#1a5336);color:#fff;border:none;border-radius:0.5rem;cursor:pointer;font-weight:600;font-size:0.875rem;display:flex;align-items:center;gap:0.5rem;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Run Analysis
        </button>
    </div>
</div>

<div class="dss-stats-grid">
    <div class="dss-stat-card">
        <div class="dss-stat-num" id="dss-stat-total">—</div>
        <div class="dss-stat-lbl">Total Recommendations</div>
    </div>
    <div class="dss-stat-card dss-stat-high">
        <div class="dss-stat-num" id="dss-stat-high">—</div>
        <div class="dss-stat-lbl">High Priority</div>
    </div>
    <div class="dss-stat-card dss-stat-mid">
        <div class="dss-stat-num" id="dss-stat-medium">—</div>
        <div class="dss-stat-lbl">Medium Priority</div>
    </div>
    <div class="dss-stat-card dss-stat-low">
        <div class="dss-stat-num" id="dss-stat-low">—</div>
        <div class="dss-stat-lbl">Low Priority</div>
    </div>
</div>

<div class="dss-tabs">
    <button class="dss-tab-btn active" data-tab="recommendations" onclick="DSS.switchTab('recommendations')">Recommendations</button>
    <button class="dss-tab-btn" data-tab="notifications" onclick="DSS.switchTab('notifications')">Notifications</button>
    <button class="dss-tab-btn" data-tab="workload" onclick="DSS.switchTab('workload')">Workload Summary</button>
    <button class="dss-tab-btn" data-tab="rooms" onclick="DSS.switchTab('rooms')">Room Allocation</button>
</div>

<div id="dss-spinner">
    <div class="spin"></div>
    <span>Running decision support analysis…</span>
</div>

<div id="dss-placeholder">
    <svg width="56" height="56" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
    </svg>
    <p>Click <strong>Run Analysis</strong> to evaluate the current schedule and generate recommendations.</p>
    <p style="font-size:0.8rem;margin-top:0.5rem;color:#9ca3af;">The DSS engine checks for time conflicts, faculty workload, room utilisation, and subject–room matches.</p>
</div>

<div id="dss-results" style="display:none;">

    <div id="dss-tab-recommendations" class="dss-tab-content">
        <div class="filter-bar">
            <button class="filter-btn active" data-filter="all">All <span id="dss-count-all"></span></button>
            <button class="filter-btn" data-filter="high">High Priority <span id="dss-count-high"></span></button>
            <button class="filter-btn" data-filter="conflict">Conflict Resolution</button>
            <button class="filter-btn" data-filter="balance">Load Balancing</button>
            <button class="filter-btn" data-filter="optimize">Optimisation</button>
        </div>
        <div id="dss-rec-list"></div>
    </div>

    <div id="dss-tab-notifications" class="dss-tab-content" style="display:none;">
        <h2 style="font-size:1rem;font-weight:600;color:#2d3436;margin-bottom:1rem;">High-Priority Alerts</h2>
        <div id="dss-notifications"></div>
    </div>

    <div id="dss-tab-workload" class="dss-tab-content" style="display:none;">
        <h2 style="font-size:1rem;font-weight:600;color:#2d3436;margin-bottom:1rem;">Faculty Workload Summary</h2>
        <div class="dss-table-wrap">
            <table class="dss-table">
                <thead>
                    <tr>
                        <th>Faculty</th>
                        <th>Subjects</th>
                        <th>Load Hours</th>
                        <th>Classes (Assigned / Scheduled)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="dss-workload-table">
                    <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:2rem;">Run analysis to load workload data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="dss-tab-rooms" class="dss-tab-content" style="display:none;">
        <h2 style="font-size:1rem;font-weight:600;color:#2d3436;margin-bottom:1rem;">Room Allocation &amp; Utilisation</h2>
        <div class="dss-table-wrap">
            <table class="dss-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Building</th>
                        <th>Capacity</th>
                        <th>Features</th>
                        <th>Utilisation</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="dss-rooms-table">
                    <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:2rem;">Run analysis to load room data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div id="dss-detail-modal">
    <div class="dss-modal-box">
        <div class="dss-modal-header">
            <span class="dss-modal-title" id="dss-modal-title">Recommendation Detail</span>
            <button class="dss-modal-close" id="dss-modal-close">&times;</button>
        </div>
        <div class="dss-modal-body" id="dss-modal-body"></div>
    </div>
</div>

<script src="{{ asset('js/dss-admin.js') }}"></script>
<script>
// Auto-run analysis on page load — no manual button click required
(function autoRunDSS() {
    const btn = document.getElementById('dss-run-btn');
    function tryRun() {
        if (window.DSS && typeof window.DSS.runAnalysis === 'function') {
            window.DSS.runAnalysis();
        } else if (btn) {
            btn.click();
        }
    }
    if (document.readyState === 'loading') {
        window.addEventListener('load', tryRun);
    } else {
        // Already loaded — small delay for dss-admin.js to register window.DSS
        setTimeout(tryRun, 50);
    }
})();
</script>
@endsection
