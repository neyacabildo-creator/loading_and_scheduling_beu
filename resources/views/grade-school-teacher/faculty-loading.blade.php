{{-- resources/views/grade-school-teacher/faculty-loading.blade.php --}}
@extends('layouts.grade-school-teacher')

@section('title', 'Faculty Teaching Load')

@section('content')
<style>
.fl-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; background:var(--bg-secondary); padding:1.5rem; border-radius:.75rem; border:1px solid var(--border-color); margin-bottom:1.5rem; }
.fl-title   { font-size:1.6rem; font-weight:700; color:var(--text-primary); margin:0; }
.fl-sub     { margin:.2rem 0 0; font-size:.85rem; color:var(--text-secondary); }
.fl-btn     { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.1rem; background:var(--green-primary); color:#fff; border:none; border-radius:.45rem; font-size:.85rem; font-weight:600; cursor:pointer; transition:background .2s; }
.fl-btn:hover { background:var(--green-dark); }
.fl-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(155px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.fl-card  { background:var(--bg-secondary); border-radius:.65rem; padding:1.1rem 1.25rem; border:1px solid var(--border-color); display:flex; flex-direction:column; }
.fl-card-icon  { width:38px; height:38px; border-radius:.5rem; display:flex; align-items:center; justify-content:center; margin-bottom:.65rem; flex-shrink:0; }
.fl-card-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin:0 0 .3rem; }
.fl-card-value { font-size:2rem; font-weight:800; margin:0; line-height:1; }
.fl-card-note  { font-size:.73rem; color:var(--text-secondary); margin:.3rem 0 0; }
.fl-meter-wrap { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.75rem; padding:1.25rem 1.5rem; margin-bottom:1.5rem; }
.fl-meter-title { font-size:.95rem; font-weight:700; color:var(--text-primary); margin:0 0 1rem; display:flex; align-items:center; gap:.5rem; }
.fl-meter-bar-bg { background:var(--bg-primary); border-radius:9999px; height:18px; overflow:hidden; position:relative; margin-bottom:.5rem; }
.fl-meter-bar   { height:100%; border-radius:9999px; transition:width .6s ease; }
.fl-meter-labels { display:flex; justify-content:space-between; font-size:.75rem; color:var(--text-secondary); margin-top:.35rem; }
.fl-meter-pct   { font-size:1.4rem; font-weight:800; margin:0; }
.fl-days { display:grid; grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); gap:.75rem; margin-bottom:1.5rem; }
.fl-day-card { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.65rem; padding:1rem; text-align:center; }
.fl-day-name { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin:0 0 .5rem; }
.fl-day-num  { font-size:2rem; font-weight:800; color:var(--green-primary); margin:0; line-height:1; }
.fl-day-lbl  { font-size:.72rem; color:var(--text-secondary); margin:.3rem 0 0; }
.fl-section-title { font-size:1rem; font-weight:700; color:var(--text-primary); margin:0 0 .9rem; display:flex; align-items:center; gap:.5rem; }
.fl-subject-list  { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.fl-subj-card { background:var(--bg-secondary); border-radius:.65rem; border:1px solid var(--border-color); overflow:hidden; }
.fl-subj-top  { padding:.75rem 1rem; display:flex; align-items:center; gap:.65rem; }
.fl-subj-color { width:6px; align-self:stretch; border-radius:3px; flex-shrink:0; }
.fl-subj-name { font-weight:700; font-size:.9rem; color:var(--text-primary); margin:0; }
.fl-subj-code { font-size:.73rem; color:var(--text-secondary); margin:.15rem 0 0; }
.fl-subj-body { padding:.65rem 1rem; border-top:1px solid var(--border-color); display:grid; grid-template-columns:1fr 1fr; gap:.5rem; background:var(--bg-primary); }
.fl-subj-meta-lbl { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--text-secondary); margin:0 0 .1rem; font-weight:600; }
.fl-subj-meta-val { font-size:.82rem; font-weight:600; color:var(--text-primary); margin:0; }
.fl-subj-status { display:inline-block; padding:.15rem .55rem; border-radius:9999px; font-size:.7rem; font-weight:700; }
.fl-loading { display:flex; align-items:center; gap:.6rem; color:var(--text-secondary); font-size:.88rem; padding:1.5rem; }
.fl-spinner { width:20px; height:20px; border:2px solid var(--border-color); border-top-color:var(--green-primary); border-radius:50%; animation:fl-spin .7s linear infinite; flex-shrink:0; }
@keyframes fl-spin { to { transform:rotate(360deg); } }
.fl-empty { text-align:center; padding:3rem 2rem; }
.fl-empty svg { display:block; margin:0 auto 1rem; opacity:.35; }
.fl-empty h3  { font-size:1rem; font-weight:600; color:var(--text-primary); margin:0 0 .4rem; }
.fl-empty p   { font-size:.84rem; color:var(--text-secondary); margin:0; }
@media print {
    .fl-btn, .header-right, .sidebar { display:none !important; }
    .main-content { margin-left:0 !important; padding:0 !important; }
}
</style>

    <!-- Page Header -->
    <div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin:0 0 .3rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Grade School Division</p>
            <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">My Teaching Load</h1>
            <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Summary of subjects and hours assigned to you this semester</p>
        </div>
        <button onclick="window.print()" style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.25rem;background:rgba(255,255,255,.18);color:white;border:1px solid rgba(255,255,255,.35);border-radius:.45rem;font-size:.875rem;font-weight:600;cursor:pointer;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="fl-cards">
        <div class="fl-card">
            <div class="fl-card-icon" style="background:rgba(45,122,80,.12);">
                <svg width="20" height="20" fill="none" stroke="#2d7a50" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="fl-card-label">Total Load Hours</p>
            <p class="fl-card-value" id="stat-total-hrs" style="color:#2d7a50;">—</p>
            <p class="fl-card-note">All subjects combined</p>
        </div>
        <div class="fl-card">
            <div class="fl-card-icon" style="background:rgba(2,132,199,.12);">
                <svg width="20" height="20" fill="none" stroke="#0284c7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <p class="fl-card-label">Total Units</p>
            <p class="fl-card-value" id="stat-total-units" style="color:#0284c7;">—</p>
            <p class="fl-card-note">Academic units assigned</p>
        </div>
        <div class="fl-card">
            <div class="fl-card-icon" style="background:rgba(124,58,237,.12);">
                <svg width="20" height="20" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p class="fl-card-label">Subjects / Classes</p>
            <p class="fl-card-value" id="stat-classes" style="color:#7c3aed;">—</p>
            <p class="fl-card-note">This semester</p>
        </div>
        <div class="fl-card">
            <div class="fl-card-icon" style="background:rgba(217,119,6,.12);">
                <svg width="20" height="20" fill="none" stroke="#d97706" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
            </div>
            <p class="fl-card-label">Teaching Days / Week</p>
            <p class="fl-card-value" id="stat-days" style="color:#d97706;">—</p>
            <p class="fl-card-note">Days with scheduled classes</p>
        </div>
    </div>

    <!-- Load Meter -->
    <div class="fl-meter-wrap" id="fl-meter" style="display:none;">
        <h2 class="fl-meter-title">
            <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Teaching Load Meter (vs. Standard 30 hrs/week)
        </h2>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.75rem;">
            <p class="fl-meter-pct" id="fl-meter-pct" style="color:#2d7a50;">0%</p>
            <div style="flex:1;">
                <div class="fl-meter-bar-bg">
                    <div class="fl-meter-bar" id="fl-meter-fill" style="width:0%;background:linear-gradient(90deg,#2d7a50,#4ade80);"></div>
                </div>
                <div class="fl-meter-labels">
                    <span id="fl-meter-used">0 hrs used</span>
                    <span>Standard: 30 hrs/week</span>
                </div>
            </div>
        </div>
        <p id="fl-meter-msg" style="font-size:.8rem;color:var(--text-secondary);margin:0;"></p>
    </div>

    <!-- Classes per Day -->
    <div id="fl-days-section" style="display:none; margin-bottom:1.5rem;">
        <p class="fl-section-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Your Classes Per Day
        </p>
        <div class="fl-days" id="fl-days"></div>
    </div>

    <!-- Subject Cards -->
    <div id="fl-subjects-section" style="display:none; margin-bottom:1.5rem;">
        <p class="fl-section-title">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Assigned Subjects
        </p>
        <div class="fl-subject-list" id="fl-subjects"></div>
    </div>

    <!-- States -->
    <div id="fl-loading-state" class="fl-loading">
        <div class="fl-spinner"></div>
        <span>Loading your teaching load details&hellip;</span>
    </div>
    <div id="fl-error-state" style="display:none;">
        <div class="fl-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <h3>Could Not Load Schedule</h3>
            <p>Unable to retrieve your teaching load. Please refresh the page or contact your administrator.</p>
        </div>
    </div>
    <div id="fl-empty-state" style="display:none;">
        <div class="fl-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <h3>No Teaching Load Assigned Yet</h3>
            <p>Your teaching load will appear here once the administrator assigns your subjects for this semester.</p>
        </div>
    </div>

<script>
(function () {
    const COLORS = ['#2d7a50','#0369a1','#7c3aed','#b45309','#be185d','#0f766e','#c2410c','#1d4ed8','#15803d','#9333ea'];
    const DAYS   = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const MAX_HRS = 30;

    function show(id) { const e = document.getElementById(id); if(e) e.style.display = ''; }
    function hide(id) { const e = document.getElementById(id); if(e) e.style.display = 'none'; }
    function text(id, v){ const e = document.getElementById(id); if(e) e.textContent = v; }

    function statusBadge(status) {
        const map = { approved:{bg:'#dcfce7',color:'#166534'}, submitted:{bg:'#fef9c3',color:'#854d0e'}, draft:{bg:'#f3f4f6',color:'#374151'}, rejected:{bg:'#fee2e2',color:'#991b1b'} };
        const s = (status||'draft').toLowerCase();
        const c = map[s] || map.draft;
        return `<span class="fl-subj-status" style="background:${c.bg};color:${c.color};">${s.charAt(0).toUpperCase()+s.slice(1)}</span>`;
    }

    function fmt(t) {
        if (!t) return '';
        try { const [h,m] = t.split(':'); const hour = parseInt(h); return `${hour%12||12}:${m} ${hour<12?'AM':'PM'}`; } catch(e) { return t; }
    }

    fetch('/api/grade-school-teacher/faculty-load')
        .then(r => r.json())
        .then(data => {
            hide('fl-loading-state');
            const schedules = data.schedules || data.data || [];
            if (!schedules.length) { show('fl-empty-state'); return; }

            const totalHrs   = schedules.reduce((s,r) => s + parseFloat(r.load_hours || 0), 0);
            const totalUnits = data.total_units || schedules.reduce((s,r) => s + parseInt(r.units || 0), 0);
            const activeDays = new Set(schedules.map(r => r.day_of_week).filter(Boolean)).size;

            text('stat-total-hrs',   totalHrs.toFixed(1) + ' hrs');
            text('stat-total-units', totalUnits);
            text('stat-classes',     schedules.length);
            text('stat-days',        activeDays);

            const pct = Math.min(Math.round(totalHrs / MAX_HRS * 100), 100);
            const mc  = pct > 100 ? '#dc2626' : pct >= 80 ? '#d97706' : '#2d7a50';
            text('fl-meter-pct',   pct + '%');
            text('fl-meter-used',  totalHrs.toFixed(1) + ' hrs used');
            const fill = document.getElementById('fl-meter-fill');
            if (fill) { fill.style.width = pct + '%'; fill.style.background = `linear-gradient(90deg,${mc},${mc}aa)`; }
            document.getElementById('fl-meter-pct').style.color = mc;
            text('fl-meter-msg', pct >= 100
                ? '⚠ You have reached or exceeded the standard weekly load. Please review with your administrator.'
                : pct >= 80 ? 'Your load is approaching the standard limit.' : 'Your current teaching load is within the normal range.');
            show('fl-meter');

            const dayMap = {};
            DAYS.forEach(d => { dayMap[d] = {count:0, hrs:0}; });
            schedules.forEach(s => { const d = s.day_of_week; if(d && dayMap[d]) { dayMap[d].count++; dayMap[d].hrs += parseFloat(s.load_hours||0); } });
            const active = DAYS.filter(d => dayMap[d].count > 0);
            if (active.length) {
                document.getElementById('fl-days').innerHTML = active.map(d => `
                    <div class="fl-day-card">
                        <p class="fl-day-name">${d}</p>
                        <p class="fl-day-num">${dayMap[d].count}</p>
                        <p class="fl-day-lbl">class${dayMap[d].count!==1?'es':''} &bull; ${dayMap[d].hrs.toFixed(1)} hrs</p>
                    </div>`).join('');
                show('fl-days-section');
            }

            const colorMap = {}; let ci = 0;
            schedules.forEach(s => { const k = s.subject||s.subject_name||'Unknown'; if(!colorMap[k]) colorMap[k] = COLORS[ci++%COLORS.length]; });

            function roomLabel(s) {
                const raw = (typeof s.room === 'object' && s.room) ? (s.room.room_name || s.room.room_number || '') : (s.room || '');
                if (raw && !/^tba$/i.test(raw) && !/^room\s*#\d+$/i.test(String(raw).trim())) return raw;
                const gs = s.grade_section || [s.grade_level, s.section].filter(Boolean).join(' – ');
                return gs || '—';
            }

            document.getElementById('fl-subjects').innerHTML = schedules.map(s => {
                const subj = s.subject||s.subject_name||'Unknown Subject';
                const color = colorMap[subj];
                const timeStr = s.start_time && s.end_time ? `${fmt(s.start_time)} – ${fmt(s.end_time)}` : (s.time_start && s.time_end ? `${fmt(s.time_start)} – ${fmt(s.time_end)}` : 'Time TBA');
                return `
                <div class="fl-subj-card">
                    <div class="fl-subj-top">
                        <div class="fl-subj-color" style="background:${color};"></div>
                        <div>
                            <p class="fl-subj-name">${subj}</p>
                            ${s.subject_code ? `<p class="fl-subj-code">${s.subject_code}</p>` : ''}
                        </div>
                        <div style="margin-left:auto;">${statusBadge(s.status)}</div>
                    </div>
                    <div class="fl-subj-body">
                        <div><p class="fl-subj-meta-lbl">Grade &amp; Section</p><p class="fl-subj-meta-val">${s.grade_section||s.grade_level||'—'}${s.section?` – ${s.section}`:''}</p></div>
                        <div><p class="fl-subj-meta-lbl">Room</p><p class="fl-subj-meta-val">${roomLabel(s)}</p></div>
                        <div><p class="fl-subj-meta-lbl">Day</p><p class="fl-subj-meta-val">${s.day_of_week||'TBA'}</p></div>
                        <div><p class="fl-subj-meta-lbl">Time</p><p class="fl-subj-meta-val" style="font-size:.78rem;">${timeStr}</p></div>
                        <div><p class="fl-subj-meta-lbl">Units</p><p class="fl-subj-meta-val">${s.units||'—'}</p></div>
                        <div><p class="fl-subj-meta-lbl">Load Hours</p><p class="fl-subj-meta-val" style="color:${color};">${(s.load_hours != null && s.load_hours !== '') ? parseFloat(s.load_hours).toFixed(2) + ' hrs' : '—'}</p></div>
                    </div>
                </div>`;
            }).join('');
            show('fl-subjects-section');
        })
        .catch(() => { hide('fl-loading-state'); show('fl-error-state'); });
})();
</script>
@endsection
