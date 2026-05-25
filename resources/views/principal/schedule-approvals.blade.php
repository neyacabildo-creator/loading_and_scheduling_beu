{{-- resources/views/principal/schedule-approvals.blade.php --}}
@extends('layouts.principal')

@section('title', 'Schedule Approvals — Principal Review')

@section('content')
<style>
    .sa-banner { background: #fefce8; border: 1px solid #ca8a04; border-radius: 0.625rem; padding: 0.875rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem; color: #854d0e; }
    .school-section { margin-bottom: 2.5rem; }
    .school-section-title { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }
    .school-badge { display: inline-block; padding: 0.2rem 0.75rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; }
    .school-badge-jh { background: rgba(26,58,92,0.12); color: #1a3a5c; }
    .school-badge-gs { background: rgba(79,70,229,0.12); color: #4338ca; }
    .sa-filter-bar { display: flex; flex-wrap: wrap; gap: 0.65rem; align-items: center; padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-color); background: var(--bg-primary); }
    .sa-filter-bar input, .sa-filter-bar select { padding: 0.45rem 0.7rem; border: 1px solid var(--border-color); border-radius: 0.375rem; background: var(--bg-secondary); color: var(--text-primary); font-size: 0.82rem; min-width: 0; }
    .sa-filter-search { flex: 1; min-width: 200px; }
    .sa-filter-bar select { max-width: 160px; }
    .sa-filter-meta { font-size: 0.75rem; color: var(--text-secondary); margin-left: auto; white-space: nowrap; }
    .sa-filter-clear { padding: 0.45rem 0.75rem; border: 1px solid var(--border-color); border-radius: 0.375rem; background: transparent; color: var(--text-secondary); font-size: 0.78rem; font-weight: 600; cursor: pointer; }
    .sa-filter-clear:hover { border-color: var(--text-secondary); color: var(--text-primary); }
    .approvals-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .approvals-table th { background: var(--bg-primary); padding: 0.75rem 1rem; text-align: left; font-weight: 600; font-size: 0.78rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.03em; border-bottom: 1px solid var(--border-color); }
    .approvals-table td { padding: 0.875rem 1rem; border-bottom: 1px solid var(--border-color); color: var(--text-primary); }
    .approvals-table tr:last-child td { border-bottom: none; }
    .approvals-table tbody tr.sa-data-row:hover td { background: var(--bg-tertiary); }
    .approvals-table tbody tr.sa-filter-hidden { display: none; }
    .empty-state { text-align: center; padding: 2.5rem; color: var(--text-secondary); font-size: 0.875rem; }
    .empty-state svg { opacity: 0.35; margin-bottom: 0.75rem; }
    .sa-no-match { display: none; text-align: center; padding: 2rem; color: var(--text-secondary); font-size: 0.875rem; }
    .sa-no-match.visible { display: block; }
    .action-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .btn-approve { padding: 0.35rem 0.875rem; background: rgba(34,197,94,0.1); color: #166534; border: 1px solid #22c55e; border-radius: 0.375rem; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-approve:hover { background: #22c55e; color: #fff; }
    .btn-reject { padding: 0.35rem 0.875rem; background: rgba(239,68,68,0.1); color: #991b1b; border: 1px solid #ef4444; border-radius: 0.375rem; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .btn-reject:hover { background: #ef4444; color: #fff; }
</style>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <svg width="22" height="22" fill="none" stroke="#1a3a5c" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <h1 class="page-title">Schedule Approvals — Principal Review</h1>
    </div>
    <div class="header-right">
        <button class="btn btn-outline btn-sm" onclick="window.location.reload()">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Refresh
        </button>
    </div>
</div>

<!-- Info banner -->
<div class="sa-banner">
    <svg width="18" height="18" fill="none" stroke="#ca8a04" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span>These schedules have been <strong>approved by the admin</strong> but are pending your final (principal) approval before teachers are notified of full confirmation.</span>
</div>

@php
    $jhCount = count($jhSchedules);
    $gsCount = count($gsSchedules);
@endphp

<!-- Totals summary -->
<div style="display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
    <div class="card" style="flex:1;min-width:160px;padding:1rem;text-align:center;">
        <div style="font-size:1.75rem;font-weight:700;color:#1a3a5c;">{{ $jhCount }}</div>
        <div style="font-size:0.75rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Junior High Pending</div>
    </div>
    <div class="card" style="flex:1;min-width:160px;padding:1rem;text-align:center;">
        <div style="font-size:1.75rem;font-weight:700;color:#4338ca;">{{ $gsCount }}</div>
        <div style="font-size:0.75rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Grade School Pending</div>
    </div>
    <div class="card" style="flex:1;min-width:160px;padding:1rem;text-align:center;">
        <div style="font-size:1.75rem;font-weight:700;color:#166534;">{{ $jhCount + $gsCount }}</div>
        <div style="font-size:0.75rem;color:var(--text-secondary);font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Total Pending</div>
    </div>
</div>

<!-- ── Junior High ── -->
<div class="card school-section">
    <div class="card-header">
        <div class="card-title">
            Junior High School
            <span class="school-badge school-badge-jh" id="jh-pending-badge" style="margin-left:0.5rem;">{{ $jhCount }} pending</span>
        </div>
    </div>
    @if($jhCount > 0)
    <div class="sa-filter-bar" data-school="jh">
        <input type="search" id="jh-sa-search" class="sa-filter-search" placeholder="Search teacher, subject, grade, day, room…" autocomplete="off">
        <select id="jh-sa-day" aria-label="Filter by day">
            <option value="">All days</option>
        </select>
        <select id="jh-sa-grade" aria-label="Filter by grade or section">
            <option value="">All grades/sections</option>
        </select>
        <select id="jh-sa-subject" aria-label="Filter by subject">
            <option value="">All subjects</option>
        </select>
        <button type="button" class="sa-filter-clear" data-school="jh">Clear</button>
        <span class="sa-filter-meta" id="jh-sa-meta">Showing {{ $jhCount }} of {{ $jhCount }}</span>
    </div>
    @endif
    <div class="card-body" style="padding:0;overflow-x:auto;">
        @if($jhCount > 0)
        <table class="approvals-table" id="jh-approvals-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Teacher</th>
                    <th>Subject</th>
                    <th>Grade/Section</th>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Room</th>
                    <th>Admin Approved By</th>
                    <th>Admin Approved At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($jhSchedules as $s)
                @php
                    $jhSearch = strtolower(implode(' ', array_filter([
                        (string) $s->id,
                        $s->faculty_name ?? '',
                        $s->subject ?? '',
                        $s->grade_section ?? '',
                        $s->day_of_week ?? '',
                        $s->room_label ?? '',
                        $s->approver_name ?? '',
                    ])));
                @endphp
                <tr id="jh-row-{{ $s->id }}" class="sa-data-row"
                    data-teacher="{{ strtolower($s->faculty_name ?? '') }}"
                    data-subject="{{ strtolower($s->subject ?? '') }}"
                    data-grade="{{ strtolower($s->grade_section ?? '') }}"
                    data-day="{{ strtolower($s->day_of_week ?? '') }}"
                    data-search="{{ $jhSearch }}">
                    <td><span class="badge badge-principal">#{{ $s->id }}</span></td>
                    <td>{{ $s->faculty_name }}</td>
                    <td>{{ $s->subject ?? 'N/A' }}</td>
                    <td>{{ $s->grade_section ?? 'N/A' }}</td>
                    <td>{{ $s->day_of_week ?? 'N/A' }}</td>
                    <td>{{ $s->start_time ? substr($s->start_time,0,5) : 'N/A' }} – {{ $s->end_time ? substr($s->end_time,0,5) : 'N/A' }}</td>
                    <td>{{ ($s->room_label !== '—' ? $s->room_label : null) ?? $s->grade_section ?? 'N/A' }}</td>
                    <td>{{ $s->approver_name }}</td>
                    <td>{{ $s->approved_at ? \Carbon\Carbon::parse($s->approved_at)->format('M d, Y') : 'N/A' }}</td>
                    <td>
                        <div class="action-group">
                            <button class="btn-approve" onclick="doApprove('jh', {{ $s->id }})">Approve</button>
                            <button class="btn-reject"  onclick="doReject('jh', {{ $s->id }})">Reject</button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sa-no-match" id="jh-sa-no-match">No Junior High schedules match your search or filters.</div>
        @else
        <div class="empty-state">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>No Junior High schedules pending principal approval.</p>
        </div>
        @endif
    </div>
</div>

<!-- ── Grade School ── -->
<div class="card school-section">
    <div class="card-header">
        <div class="card-title">
            Grade School
            <span class="school-badge school-badge-gs" id="gs-pending-badge" style="margin-left:0.5rem;">{{ $gsCount }} pending</span>
        </div>
    </div>
    @if($gsCount > 0)
    <div class="sa-filter-bar" data-school="gs">
        <input type="search" id="gs-sa-search" class="sa-filter-search" placeholder="Search teacher, subject, grade, day, room…" autocomplete="off">
        <select id="gs-sa-day" aria-label="Filter by day">
            <option value="">All days</option>
        </select>
        <select id="gs-sa-grade" aria-label="Filter by grade or section">
            <option value="">All grades/sections</option>
        </select>
        <select id="gs-sa-subject" aria-label="Filter by subject">
            <option value="">All subjects</option>
        </select>
        <button type="button" class="sa-filter-clear" data-school="gs">Clear</button>
        <span class="sa-filter-meta" id="gs-sa-meta">Showing {{ $gsCount }} of {{ $gsCount }}</span>
    </div>
    @endif
    <div class="card-body" style="padding:0;overflow-x:auto;">
        @if($gsCount > 0)
        <table class="approvals-table" id="gs-approvals-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Teacher</th>
                    <th>Subject</th>
                    <th>Grade/Section</th>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Room</th>
                    <th>Admin Approved By</th>
                    <th>Admin Approved At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gsSchedules as $s)
                @php
                    $gsSearch = strtolower(implode(' ', array_filter([
                        (string) $s->id,
                        $s->faculty_name ?? '',
                        $s->subject ?? '',
                        $s->grade_section ?? '',
                        $s->day_of_week ?? '',
                        $s->room_label ?? '',
                        $s->approver_name ?? '',
                    ])));
                @endphp
                <tr id="gs-row-{{ $s->id }}" class="sa-data-row"
                    data-teacher="{{ strtolower($s->faculty_name ?? '') }}"
                    data-subject="{{ strtolower($s->subject ?? '') }}"
                    data-grade="{{ strtolower($s->grade_section ?? '') }}"
                    data-day="{{ strtolower($s->day_of_week ?? '') }}"
                    data-search="{{ $gsSearch }}">
                    <td><span class="badge badge-principal">#{{ $s->id }}</span></td>
                    <td>{{ $s->faculty_name }}</td>
                    <td>{{ $s->subject ?? 'N/A' }}</td>
                    <td>{{ $s->grade_section ?? 'N/A' }}</td>
                    <td>{{ $s->day_of_week ?? 'N/A' }}</td>
                    <td>{{ $s->start_time ? substr($s->start_time,0,5) : 'N/A' }} – {{ $s->end_time ? substr($s->end_time,0,5) : 'N/A' }}</td>
                    <td>{{ ($s->room_label !== '—' ? $s->room_label : null) ?? $s->grade_section ?? 'N/A' }}</td>
                    <td>{{ $s->approver_name }}</td>
                    <td>{{ $s->approved_at ? \Carbon\Carbon::parse($s->approved_at)->format('M d, Y') : 'N/A' }}</td>
                    <td>
                        <div class="action-group">
                            <button class="btn-approve" onclick="doApprove('gs', {{ $s->id }})">Approve</button>
                            <button class="btn-reject"  onclick="doReject('gs', {{ $s->id }})">Reject</button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="sa-no-match" id="gs-sa-no-match">No Grade School schedules match your search or filters.</div>
        @else
        <div class="empty-state">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p>No Grade School schedules pending principal approval.</p>
        </div>
        @endif
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const SA_DAY_ORDER = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

function saInitFilters(school) {
    const rows = document.querySelectorAll('#' + school + '-approvals-table tbody tr.sa-data-row');
    const days = new Set();
    const grades = new Set();
    const subjects = new Set();

    rows.forEach(function (row) {
        if (row.dataset.day) days.add(row.dataset.day);
        if (row.dataset.grade) grades.add(row.dataset.grade);
        if (row.dataset.subject) subjects.add(row.dataset.subject);
    });

    saFillSelect(school + '-sa-day', days, function (v) {
        return v.charAt(0).toUpperCase() + v.slice(1);
    }, SA_DAY_ORDER);
    saFillSelect(school + '-sa-grade', grades, function (v) {
        const parts = v.split(' - ');
        return parts.map(function (p) {
            return p.replace(/\b\w/g, function (c) { return c.toUpperCase(); });
        }).join(' - ');
    });
    saFillSelect(school + '-sa-subject', subjects, function (v) {
        return v.replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    });

    ['search', 'day', 'grade', 'subject'].forEach(function (kind) {
        const el = document.getElementById(school + '-sa-' + kind);
        if (el) el.addEventListener(kind === 'search' ? 'input' : 'change', function () {
            saApplyFilters(school);
        });
    });

    document.querySelectorAll('.sa-filter-clear[data-school="' + school + '"]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ['search', 'day', 'grade', 'subject'].forEach(function (kind) {
                const el = document.getElementById(school + '-sa-' + kind);
                if (el) el.value = '';
            });
            saApplyFilters(school);
        });
    });

    saApplyFilters(school);
}

function saFillSelect(id, values, labelFn, sortOrder) {
    const sel = document.getElementById(id);
    if (!sel) return;
    const first = sel.options[0];
    sel.innerHTML = '';
    sel.appendChild(first);

    let list = Array.from(values).filter(Boolean);
    if (sortOrder) {
        list.sort(function (a, b) {
            const ai = sortOrder.indexOf(a);
            const bi = sortOrder.indexOf(b);
            if (ai !== -1 && bi !== -1) return ai - bi;
            if (ai !== -1) return -1;
            if (bi !== -1) return 1;
            return a.localeCompare(b);
        });
    } else {
        list.sort(function (a, b) { return a.localeCompare(b); });
    }

    list.forEach(function (val) {
        const opt = document.createElement('option');
        opt.value = val;
        opt.textContent = labelFn(val);
        sel.appendChild(opt);
    });
}

function saApplyFilters(school) {
    const q = (document.getElementById(school + '-sa-search')?.value || '').toLowerCase().trim();
    const day = (document.getElementById(school + '-sa-day')?.value || '').toLowerCase();
    const grade = (document.getElementById(school + '-sa-grade')?.value || '').toLowerCase();
    const subject = (document.getElementById(school + '-sa-subject')?.value || '').toLowerCase();

    const rows = document.querySelectorAll('#' + school + '-approvals-table tbody tr.sa-data-row');
    let visible = 0;

    rows.forEach(function (row) {
        let show = true;
        if (q && !(row.dataset.search || '').includes(q)) show = false;
        if (day && row.dataset.day !== day) show = false;
        if (grade && row.dataset.grade !== grade) show = false;
        if (subject && row.dataset.subject !== subject) show = false;

        row.classList.toggle('sa-filter-hidden', !show);
        if (show) visible++;
    });

    const total = rows.length;
    const meta = document.getElementById(school + '-sa-meta');
    if (meta) meta.textContent = 'Showing ' + visible + ' of ' + total;

    const badge = document.getElementById(school + '-pending-badge');
    if (badge) badge.textContent = visible + ' shown · ' + total + ' pending';

    const noMatch = document.getElementById(school + '-sa-no-match');
    if (noMatch) noMatch.classList.toggle('visible', total > 0 && visible === 0);
}

function doApprove(school, id) {
    if (!confirm('Approve this schedule as principal? This will notify the admin and teacher that the schedule is fully confirmed.')) return;
    sendAction(school, id, 'approve');
}

function doReject(school, id) {
    if (!confirm('Reject this schedule? It will remain visible to the admin but will stay marked as awaiting principal approval.')) return;
    sendAction(school, id, 'reject');
}

function sendAction(school, id, action) {
    const url = `/principal/schedule-approvals/${school}/${id}/${action}`;
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        credentials: 'same-origin'
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const row = document.getElementById(`${school}-row-${id}`);
            if (row) {
                row.style.transition = 'opacity 0.4s';
                row.style.opacity = '0';
                setTimeout(function () {
                    row.remove();
                    saApplyFilters(school);
                }, 400);
            }
        } else {
            alert('Error: ' + (res.message || 'Unknown error'));
        }
    })
    .catch(() => alert('Network error. Please try again.'));
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('jh-approvals-table')) saInitFilters('jh');
    if (document.getElementById('gs-approvals-table')) saInitFilters('gs');
});
</script>
@endsection
