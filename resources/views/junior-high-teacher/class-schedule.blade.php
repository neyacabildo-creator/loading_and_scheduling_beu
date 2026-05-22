{{-- resources/views/junior-high-teacher/class-schedule.blade.php --}}
@extends('layouts.teacher')

@section('title', 'My Class Schedule')

@section('content')
<style>
    /* Day filter tabs */
    .day-tabs { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
    .day-tab { padding: 0.45rem 1rem; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 9999px; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); cursor: pointer; transition: all 0.2s; }
    .day-tab:hover { border-color: #2d7a50; color: #2d7a50; }
    .day-tab.active { background: #2d7a50; border-color: #2d7a50; color: #fff; }

    /* Schedule cards */
    .sched-card { display: flex; gap: 1.25rem; align-items: flex-start; padding: 1rem 1.25rem; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.625rem; margin-bottom: 0.75rem; transition: box-shadow 0.2s, border-color 0.2s; }
    .sched-card:hover { border-color: #2d7a50; box-shadow: 0 2px 8px rgba(45,122,80,0.1); }
    .sched-time-col { min-width: 110px; text-align: center; padding: 0.5rem 0.75rem; background: rgba(45,122,80,0.08); border-radius: 0.5rem; }
    .sched-time { font-size: 0.82rem; font-weight: 700; color: #2d7a50; line-height: 1.4; }
    .sched-day-label { font-size: 0.7rem; color: var(--text-secondary); margin-top: 0.2rem; }
    .sched-body { flex: 1; min-width: 0; }
    .sched-subject { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.2rem; }
    .sched-meta { font-size: 0.8rem; color: var(--text-secondary); display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.2rem; }
    .sched-meta span { display: flex; align-items: center; gap: 0.25rem; }
    .sched-approved-on { font-size: 0.72rem; color: #2d7a50; margin-top: 0.4rem; }
    .sched-badge { align-self: center; display: inline-block; padding: 0.25rem 0.8rem; background: rgba(45,122,80,0.12); color: #2d7a50; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; white-space: nowrap; }

    /* Stats row */
    .sched-stats { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
    .sched-stat { flex: 1; min-width: 130px; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.625rem; padding: 0.875rem 1rem; text-align: center; }
    .sched-stat-val { font-size: 1.5rem; font-weight: 700; color: #2d7a50; }
    .sched-stat-lbl { font-size: 0.72rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 0.15rem; }

    /* Empty state */
    .sched-empty { text-align: center; padding: 3rem 1rem; color: var(--text-secondary); }
    .sched-empty svg { opacity: 0.35; margin-bottom: 0.75rem; }

    /* Weekly grid */
    .week-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.75rem; margin-bottom: 1.5rem; }
    @media (max-width: 900px) { .week-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 600px) { .week-grid { grid-template-columns: repeat(2, 1fr); } }
    .week-col { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.625rem; overflow: hidden; }
    .week-col-head { padding: 0.5rem; background: rgba(45,122,80,0.08); text-align: center; font-size: 0.75rem; font-weight: 700; color: #2d7a50; }
    .week-col-body { padding: 0.5rem; min-height: 60px; }
    .week-slot { background: linear-gradient(135deg,rgba(45,122,80,0.85),rgba(45,122,80,0.6)); color: #fff; border-radius: 0.3rem; padding: 0.3rem 0.4rem; font-size: 0.65rem; margin-bottom: 0.3rem; line-height: 1.3; }
    .week-slot-time { opacity: 0.85; font-weight: 600; }
    .week-empty-slot { color: var(--text-secondary); font-size: 0.7rem; text-align: center; padding: 0.5rem 0; opacity: 0.6; }
</style>

<div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;">
    <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">My Class Schedule</h1>
    <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Your admin-approved assigned classes for the current semester</p>
</div>

<!-- Info banner -->
<div style="background:rgba(45,122,80,0.07);border:1px solid rgba(45,122,80,0.25);border-radius:0.625rem;padding:0.875rem 1.25rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.75rem;">
    <svg width="18" height="18" fill="none" stroke="#2d7a50" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p style="margin:0;color:#1a5336;font-size:0.875rem;font-weight:500;">
        Showing only <strong>admin-approved</strong> classes. Any admin changes are reflected here automatically.
    </p>
</div>

<!-- Principal Pending Banner (hidden until schedules load) -->
<div id="principalPendingBanner" style="display:none;background:#fefce8;border:1px solid #ca8a04;border-radius:0.625rem;padding:0.875rem 1.25rem;margin-bottom:1.5rem;align-items:center;gap:0.75rem;flex-wrap:wrap;">
    <svg width="18" height="18" fill="none" stroke="#ca8a04" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span style="color:#854d0e;font-size:0.875rem;font-weight:500;flex:1;">
        Your schedule is visible but awaiting <strong>Principal final confirmation</strong>.
        Some schedules may still be subject to review.
    </span>
</div>

<!-- Stats row -->
<div class="sched-stats" id="statsRow">
    <div class="sched-stat"><div class="sched-stat-val" id="statTotal">—</div><div class="sched-stat-lbl">Total Classes</div></div>
    <div class="sched-stat"><div class="sched-stat-val" id="statDays">—</div><div class="sched-stat-lbl">Days / Week</div></div>
    <div class="sched-stat"><div class="sched-stat-val" id="statSubjects">—</div><div class="sched-stat-lbl">Subjects</div></div>
</div>

<!-- Weekly overview grid -->
<div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:0.75rem;padding:1.25rem;margin-bottom:1.5rem;">
    <div style="font-size:0.9rem;font-weight:600;color:var(--text-primary);margin-bottom:1rem;">Weekly Overview</div>
    <div class="week-grid" id="weekGrid">
        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
        <div class="week-col" id="wc-{{ $d }}">
            <div class="week-col-head">{{ substr($d,0,3) }}</div>
            <div class="week-col-body" id="wb-{{ $d }}">
                <div class="week-empty-slot">Loading…</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Day filter + schedule list -->
<div style="background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:0.75rem;padding:1.25rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.75rem;">
        <div style="font-size:0.9rem;font-weight:600;color:var(--text-primary);">Schedule Details</div>
        <div class="day-tabs" id="dayTabs">
            <button class="day-tab active" data-day="All">All Days</button>
            @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                <button class="day-tab" data-day="{{ $d }}">{{ substr($d,0,3) }}</button>
            @endforeach
        </div>
    </div>
    <div id="scheduleList">
        <div class="sched-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p>Loading schedules…</p>
        </div>
    </div>
</div>

<script>
(function () {
    const DAY_ORDER = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    let allSchedules = [];
    let activeDay = 'All';

    /* ---------- Fetch ---------- */
    function loadSchedules() {
        fetch('/api/teacher/schedules', {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            let raw = Array.isArray(data) ? data : (data.data || []);
            allSchedules = raw.filter(s => s.admin_approved || s.status === 'active');
            allSchedules.sort((a, b) => {
                const di = DAY_ORDER.indexOf(a.day_of_week) - DAY_ORDER.indexOf(b.day_of_week);
                return di !== 0 ? di : (a.start_time || '').localeCompare(b.start_time || '');
            });
            // Show warning banner if any schedule hasn't been principal-approved
            const pendingFinal = allSchedules.filter(s => !s.principal_approved).length;
            const banner = document.getElementById('principalPendingBanner');
            if (banner) banner.style.display = pendingFinal > 0 ? 'flex' : 'none';
            updateStats();
            renderWeekGrid();
            renderList();
        })
        .catch(() => {
            document.getElementById('scheduleList').innerHTML =
                '<div class="sched-empty" style="color:#dc2626;">Failed to load schedules. Please refresh.</div>';
        });
    }

    /* ---------- Stats ---------- */
    function updateStats() {
        const subjects  = new Set(allSchedules.map(s => s.subject)).size;
        const days      = new Set(allSchedules.map(s => s.day_of_week)).size;
        document.getElementById('statTotal').textContent    = allSchedules.length;
        document.getElementById('statDays').textContent     = days;
        document.getElementById('statSubjects').textContent = subjects;
    }

    function roomLabel(s) {
        if (s.room_label) return s.room_label;
        if (s.room && s.room.room_number) return 'Room ' + s.room.room_number;
        if (s.section_name) return s.section_name;
        return 'To be assigned';
    }

    /* ---------- Weekly grid ---------- */
    function renderWeekGrid() {
        const byDay = {};
        DAY_ORDER.forEach(d => { byDay[d] = []; });
        allSchedules.forEach(s => { if (byDay[s.day_of_week]) byDay[s.day_of_week].push(s); });

        ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'].forEach(d => {
            const body = document.getElementById('wb-' + d);
            if (!body) return;
            const slots = byDay[d];
            if (slots.length === 0) {
                body.innerHTML = '<div class="week-empty-slot">No class</div>';
            } else {
                body.innerHTML = slots.map(s =>
                    `<div class="week-slot"><div class="week-slot-time">${s.start_time}–${s.end_time}</div><div>${esc(s.subject)}</div></div>`
                ).join('');
            }
        });
    }

    /* ---------- List ---------- */
    function renderList() {
        const filtered = activeDay === 'All' ? allSchedules : allSchedules.filter(s => s.day_of_week === activeDay);
        const list = document.getElementById('scheduleList');

        if (filtered.length === 0) {
            list.innerHTML = `<div class="sched-empty">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p>${activeDay === 'All' ? 'No approved schedules found.' : 'No classes on ' + activeDay + '.'}</p>
                <p style="font-size:0.82rem;margin-top:0.35rem;">Contact your administrator if you expect classes here.</p>
            </div>`;
            return;
        }

        list.innerHTML = filtered.map(s => {
            const approvedOn = s.approved_at ? `<div class="sched-approved-on">✔ Approved ${new Date(s.approved_at).toLocaleDateString()}</div>` : '';
            return `<div class="sched-card">
                <div class="sched-time-col">
                    <div class="sched-time">${esc(s.start_time)}<br>— ${esc(s.end_time)}</div>
                    <div class="sched-day-label">${esc(s.day_of_week)}</div>
                </div>
                <div class="sched-body">
                    <div class="sched-subject">${esc(s.subject)}</div>
                    <div class="sched-meta">
                        <span><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>${esc(roomLabel(s))}</span>
                        <span><svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>${esc(s.grade_section || '—')}</span>
                    </div>
                    ${approvedOn}
                </div>
                <span class="sched-badge">Approved</span>
            </div>`;
        }).join('');
    }

    /* ---------- Day tabs ---------- */
    document.getElementById('dayTabs').addEventListener('click', function (e) {
        const btn = e.target.closest('.day-tab');
        if (!btn) return;
        document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        activeDay = btn.dataset.day;
        renderList();
    });

    /* ---------- Escape helper ---------- */
    function esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ---------- Init ---------- */
    loadSchedules();
    setInterval(loadSchedules, 60000);
})();
</script>
@endsection
