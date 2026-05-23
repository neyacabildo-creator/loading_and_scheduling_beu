{{-- resources/views/grade-school-teacher/loading-schedule.blade.php --}}
@extends('layouts.grade-school-teacher')

@section('title', 'My Loading Schedule')

@section('content')

<style>
/* ── Loading Schedule Page ── */
.ls-page-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; background:var(--bg-secondary); padding:1.5rem; border-radius:.75rem; border:1px solid var(--border-color); margin-bottom:1.5rem; }
.ls-page-title  { font-size:1.6rem; font-weight:700; color:var(--text-primary); margin:0; }
.ls-page-sub    { margin:.2rem 0 0; font-size:.85rem; color:var(--text-secondary); }
.ls-btn-print   { display:inline-flex; align-items:center; gap:.4rem; padding:.55rem 1.1rem; background:var(--green-primary); color:#fff; border:none; border-radius:.45rem; font-size:.85rem; font-weight:600; cursor:pointer; text-decoration:none; transition:background .2s; }
.ls-btn-print:hover { background:var(--green-dark); }
.ls-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(155px,1fr)); gap:1rem; margin-bottom:1.5rem; }
.ls-card  { background:var(--bg-secondary); border-radius:.65rem; padding:1.1rem 1.25rem; border:1px solid var(--border-color); }
.ls-card-label { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-secondary); margin:0 0 .35rem; }
.ls-card-value { font-size:2rem; font-weight:800; margin:0; line-height:1; }
.ls-card-note  { font-size:.75rem; color:var(--text-secondary); margin:.3rem 0 0; }
.ls-info { display:flex; gap:.75rem; align-items:flex-start; background:#eff9f3; border:1px solid #86efac; border-radius:.55rem; padding:.9rem 1.1rem; margin-bottom:1.5rem; }
.ls-info svg { flex-shrink:0; color:#16a34a; margin-top:.1rem; }
.ls-info p  { margin:0; font-size:.84rem; color:#14532d; line-height:1.5; }
.ls-days { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.65rem; padding:1.1rem 1.25rem; margin-bottom:1.5rem; }
.ls-days-title { font-size:.92rem; font-weight:700; color:var(--text-primary); margin:0 0 .9rem; }
.ls-day-row  { display:flex; align-items:center; gap:.75rem; margin-bottom:.55rem; }
.ls-day-name { font-size:.8rem; font-weight:600; color:var(--text-secondary); width:70px; flex-shrink:0; }
.ls-day-bar-wrap { flex:1; background:var(--bg-primary); border-radius:9999px; height:14px; overflow:hidden; }
.ls-day-bar  { height:100%; background:linear-gradient(90deg,#2d7a50,#4ade80); border-radius:9999px; min-width:4px; }
.ls-day-count{ font-size:.78rem; font-weight:700; color:var(--text-primary); width:28px; text-align:right; flex-shrink:0; }
.ls-day-hrs  { font-size:.72rem; color:var(--text-secondary); width:55px; flex-shrink:0; }
.ls-table-wrap { background:var(--bg-secondary); border:1px solid var(--border-color); border-radius:.75rem; overflow:hidden; margin-bottom:1.5rem; }
.ls-table-head { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:.5rem; }
.ls-table-title { font-size:1rem; font-weight:700; color:var(--text-primary); margin:0; }
.ls-legend { display:flex; gap:.75rem; flex-wrap:wrap; }
.ls-leg-item { display:flex; align-items:center; gap:.35rem; font-size:.73rem; color:var(--text-secondary); }
.ls-leg-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.ls-table { width:100%; border-collapse:collapse; min-width:700px; }
.ls-table thead tr { background:var(--bg-primary); }
.ls-table th { padding:.65rem 1rem; text-align:left; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:var(--text-secondary); border-bottom:1px solid var(--border-color); white-space:nowrap; }
.ls-table td { padding:.85rem 1rem; border-bottom:1px solid var(--border-color); font-size:.875rem; vertical-align:top; }
.ls-table tbody tr:last-child td { border-bottom:none; }
.ls-table tbody tr:hover { background:rgba(45,122,80,.04); }
.ls-subject-cell { display:flex; align-items:flex-start; gap:.55rem; }
.ls-subject-dot  { width:8px; height:8px; border-radius:50%; flex-shrink:0; margin-top:.35rem; }
.ls-subject-code { font-weight:700; color:var(--text-primary); margin:0; }
.ls-subject-name { font-size:.78rem; color:var(--text-secondary); margin:.15rem 0 0; }
.ls-time-chip { display:inline-flex; align-items:center; gap:.3rem; font-size:.78rem; background:rgba(45,122,80,.1); color:#166534; border-radius:.35rem; padding:.2rem .55rem; font-weight:600; white-space:nowrap; }
.ls-badge { display:inline-block; padding:.2rem .65rem; border-radius:9999px; font-size:.72rem; font-weight:700; white-space:nowrap; }
.ls-badge-approved  { background:#dcfce7; color:#166534; }
.ls-badge-submitted { background:#fef9c3; color:#854d0e; }
.ls-badge-draft     { background:#f3f4f6; color:#374151; }
.ls-badge-rejected  { background:#fee2e2; color:#991b1b; }
.ls-empty { padding:3.5rem 2rem; text-align:center; }
.ls-empty svg  { display:block; margin:0 auto 1rem; opacity:.35; }
.ls-empty h3   { font-size:1rem; font-weight:600; color:var(--text-primary); margin:0 0 .4rem; }
.ls-empty p    { font-size:.85rem; color:var(--text-secondary); margin:0; }
.ls-btn-adj { display:inline-flex; align-items:center; gap:.3rem; padding:.28rem .7rem; background:transparent; border:1px solid var(--green-primary); color:var(--green-primary); border-radius:.35rem; font-size:.73rem; font-weight:600; cursor:pointer; transition:background .15s,color .15s; }
.ls-btn-adj:hover { background:var(--green-primary); color:#fff; }
.adj-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center; }
.adj-overlay.active { display:flex; }
.adj-modal { background:var(--bg-secondary); border-radius:.75rem; padding:1.75rem; width:100%; max-width:480px; box-shadow:0 20px 60px rgba(0,0,0,.25); }
.adj-modal h3 { font-size:1rem; font-weight:700; color:var(--text-primary); margin:0 0 .25rem; }
.adj-modal .adj-sub { font-size:.82rem; color:var(--text-secondary); margin:0 0 1.25rem; }
.adj-field { margin-bottom:1rem; }
.adj-field label { display:block; font-size:.78rem; font-weight:600; color:var(--text-secondary); margin-bottom:.35rem; }
.adj-field select, .adj-field textarea { width:100%; padding:.55rem .75rem; border:1px solid var(--border-color); border-radius:.45rem; font-size:.875rem; background:var(--bg-primary); color:var(--text-primary); resize:vertical; }
.adj-field textarea { min-height:90px; }
.adj-actions { display:flex; justify-content:flex-end; gap:.65rem; margin-top:1.25rem; }
.adj-btn-cancel { padding:.5rem 1.1rem; background:transparent; border:1px solid var(--border-color); color:var(--text-secondary); border-radius:.45rem; font-size:.85rem; font-weight:600; cursor:pointer; }
.adj-btn-submit { padding:.5rem 1.25rem; background:var(--green-primary); color:#fff; border:none; border-radius:.45rem; font-size:.85rem; font-weight:600; cursor:pointer; }
.adj-btn-submit:disabled { opacity:.5; cursor:not-allowed; }
.adj-success { display:none; text-align:center; padding:1rem 0; }
.adj-success svg { color:#16a34a; margin-bottom:.5rem; }
@media print {
    .ls-btn-print, .header-right, .sidebar { display:none !important; }
    .main-content { margin-left:0 !important; padding:0 !important; }
    .ls-table-wrap, .ls-days, .ls-cards { break-inside:avoid; }
    .adj-overlay { display:none !important; }
}
</style>

    <!-- Page Header -->
    @include('partials.teacher-page-banner', [
        'eyebrow' => 'Grade School Division',
        'pageTitle' => 'My Loading Schedule',
        'pageSubtitle' => 'Your assigned subjects and schedule for the current semester',
        'showPrint' => true,
        'printLabel' => 'Print Schedule',
    ])

@php
    $palette = ['#2d7a50','#0369a1','#7c3aed','#b45309','#be185d','#0f766e','#c2410c','#1d4ed8','#15803d','#9333ea'];
    $subjectColors = [];
    $colorIdx = 0;
    foreach ($schedules->pluck('subject_name')->unique() as $sub) {
        $subjectColors[$sub] = $palette[$colorIdx % count($palette)];
        $colorIdx++;
    }
    $dayOrder  = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $dayTotals = [];
    foreach ($dayOrder as $d) {
        $rows = $schedules->where('day_of_week', $d);
        $dayTotals[$d] = ['count' => $rows->count(), 'hrs' => $rows->sum('load_hours')];
    }
    $maxDayCount  = max(array_column($dayTotals, 'count')) ?: 1;
    $totalHrs     = $schedules->sum('load_hours');
    $approvedCnt  = $schedules->where('status','approved')->count();
    $pendingCnt   = $schedules->whereIn('status',['draft','submitted'])->count();
    $rejectedCnt  = $schedules->where('status','rejected')->count();
@endphp

    <!-- Info Banner -->
    <div class="ls-info">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p>This page shows <strong>your assigned teaching load</strong> for the current semester. Each subject below has been assigned by your department administrator. Contact your admin if you notice any discrepancies.</p>
    </div>

    <!-- Summary Cards -->
    <div class="ls-cards">
        <div class="ls-card">
            <p class="ls-card-label">Total Subjects</p>
            <p class="ls-card-value" style="color:var(--text-primary);">{{ $schedules->count() }}</p>
            <p class="ls-card-note">Assigned this semester</p>
        </div>
        <div class="ls-card">
            <p class="ls-card-label">Total Load Hours</p>
            <p class="ls-card-value" style="color:#2d7a50;">{{ number_format($totalHrs, 1) }}</p>
            <p class="ls-card-note">Across all subjects</p>
        </div>
        <div class="ls-card">
            <p class="ls-card-label">Approved</p>
            <p class="ls-card-value" style="color:#16a34a;">{{ $approvedCnt }}</p>
            <p class="ls-card-note">Confirmed by admin</p>
        </div>
        <div class="ls-card">
            <p class="ls-card-label">Pending Review</p>
            <p class="ls-card-value" style="color:#d97706;">{{ $pendingCnt }}</p>
            <p class="ls-card-note">Awaiting approval</p>
        </div>
        @if($rejectedCnt)
        <div class="ls-card">
            <p class="ls-card-label">Rejected</p>
            <p class="ls-card-value" style="color:#dc2626;">{{ $rejectedCnt }}</p>
            <p class="ls-card-note">Needs correction</p>
        </div>
        @endif
    </div>

    <!-- Day-by-Day Distribution -->
    @if($schedules->isNotEmpty())
    <div class="ls-days">
        <p class="ls-days-title">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:.35rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Classes Per Day of the Week
        </p>
        @foreach($dayTotals as $day => $info)
            @if($info['count'] > 0)
            <div class="ls-day-row">
                <span class="ls-day-name">{{ $day }}</span>
                <div class="ls-day-bar-wrap">
                    <div class="ls-day-bar" style="width:{{ round($info['count'] / $maxDayCount * 100) }}%;"></div>
                </div>
                <span class="ls-day-count">{{ $info['count'] }}</span>
                <span class="ls-day-hrs">{{ number_format($info['hrs'],1) }} hr{{ $info['hrs'] != 1 ? 's' : '' }}</span>
            </div>
            @endif
        @endforeach
    </div>
    @endif

    <!-- Subject Details Table -->
    <div class="ls-table-wrap">
        <div class="ls-table-head">
            <h2 class="ls-table-title">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:.4rem;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                All Assigned Subjects
            </h2>
            <div class="ls-legend">
                <span class="ls-leg-item"><span class="ls-leg-dot" style="background:#16a34a;"></span>Approved</span>
                <span class="ls-leg-item"><span class="ls-leg-dot" style="background:#d97706;"></span>Pending</span>
                <span class="ls-leg-item"><span class="ls-leg-dot" style="background:#6b7280;"></span>Draft</span>
                <span class="ls-leg-item"><span class="ls-leg-dot" style="background:#dc2626;"></span>Rejected</span>
            </div>
        </div>

        @if($schedules->isEmpty())
            <div class="ls-empty">
                <svg width="52" height="52" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <h3>No Schedule Assigned Yet</h3>
                <p>Your loading schedule will appear here once the administrator has assigned your subjects for this semester.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="ls-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Grade &amp; Section</th>
                            <th>Day &amp; Time</th>
                            <th>Room</th>
                            <th style="text-align:center;">Units</th>
                            <th style="text-align:center;">Load Hrs</th>
                            <th>Semester / S.Y.</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $i => $schedule)
                        @php
                            $color = $subjectColors[$schedule->subject_name] ?? '#2d7a50';
                            $status = $schedule->status ?? 'draft';
                            $badgeClass = match($status) {
                                'approved'  => 'ls-badge-approved',
                                'submitted' => 'ls-badge-submitted',
                                'rejected'  => 'ls-badge-rejected',
                                default     => 'ls-badge-draft',
                            };
                        @endphp
                        <tr style="border-left:3px solid {{ $color }};">
                            <td style="color:var(--text-secondary);font-size:.8rem;">{{ $i + 1 }}</td>
                            <td>
                                <div class="ls-subject-cell">
                                    <span class="ls-subject-dot" style="background:{{ $color }};"></span>
                                    <div>
                                        @if($schedule->subject_code)
                                            <p class="ls-subject-code">{{ $schedule->subject_code }}</p>
                                        @endif
                                        <p class="ls-subject-name">{{ $schedule->subject_name ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="font-weight:600;">{{ $schedule->grade_level ?? '—' }}</span>
                                @if($schedule->section) &ndash; {{ $schedule->section }} @endif
                            </td>
                            <td>
                                @if($schedule->day_of_week)
                                    <span style="font-weight:600;font-size:.85rem;">{{ $schedule->day_of_week }}</span><br>
                                    @if($schedule->time_start)
                                        <span class="ls-time-chip" style="margin-top:.3rem;">
                                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            {{ \Carbon\Carbon::parse($schedule->time_start)->format('h:i A') }}
                                            @if($schedule->time_end) &ndash; {{ \Carbon\Carbon::parse($schedule->time_end)->format('h:i A') }} @endif
                                        </span>
                                    @endif
                                @else
                                    <span style="color:var(--text-secondary);font-size:.82rem;">Not yet set</span>
                                @endif
                            </td>
                            <td>
                                <span style="font-size:.85rem;">{{ \App\Support\TeacherPortalSupport::displayRoomFromRow($schedule) }}</span>
                            </td>
                            <td style="text-align:center;font-weight:600;">{{ $schedule->units ?? '—' }}</td>
                            <td style="text-align:center;font-weight:600;color:#2d7a50;">{{ $schedule->load_hours ?? '—' }}</td>
                            <td style="font-size:.8rem;">
                                {{ $schedule->semester ?? '—' }}<br>
                                <span style="color:var(--text-secondary);">{{ $schedule->school_year ?? '' }}</span>
                            </td>
                            <td style="text-align:center;">
                                <span class="ls-badge {{ $badgeClass }}">{{ ucfirst($status) }}</span>
                            </td>
                            <td style="text-align:center;">
                                <button class="ls-btn-adj" onclick="openAdjModal({{ $schedule->id ?? 'null' }}, '{{ addslashes($schedule->subject_name ?? '') }}')">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Request Adjustment
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p style="text-align:center;color:var(--text-secondary);font-size:.8rem;margin:1.5rem 0;">
        Grade School Teacher Portal &mdash; Loading schedule data is managed by your department administrator.
        For changes, please contact your admin.
    </p>

    {{-- Weekly Master Schedule Grid --}}
    @include('master-weekly-schedule._teacher-grid', [
        'timeSlots'        => $timeSlots,
        'days'             => $days,
        'weeklyGrid'       => $weeklyGrid,
        'weeklySchoolYear' => $weeklySchoolYear,
    ])

    {{-- Adjustment Request Modal --}}
    <div class="adj-overlay" id="adj-overlay" onclick="closeAdjModal(event)">
        <div class="adj-modal">
            <div id="adj-form-wrap">
                <h3>Request Schedule Adjustment</h3>
                <p class="adj-sub" id="adj-subject-label">Submit a request to your department administrator.</p>
                <div class="adj-field">
                    <label>Type of Request</label>
                    <select id="adj-type">
                        <option value="schedule_change">Schedule Change</option>
                        <option value="room_change">Room Change</option>
                        <option value="teacher_reassignment">Teacher Reassignment</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="adj-field">
                    <label>Reason <span style="color:#dc2626;">*</span></label>
                    <textarea id="adj-reason" placeholder="Describe the issue and why an adjustment is needed (min. 10 characters)..."></textarea>
                </div>
                <div class="adj-field">
                    <label>Proposed Changes (optional)</label>
                    <textarea id="adj-proposed" placeholder="Suggest the specific changes you would like..."></textarea>
                </div>
                <div class="adj-actions">
                    <button class="adj-btn-cancel" onclick="closeAdjModal()">Cancel</button>
                    <button class="adj-btn-submit" id="adj-submit-btn" onclick="submitAdjRequest()">Submit Request</button>
                </div>
            </div>
            <div class="adj-success" id="adj-success">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 style="font-size:.95rem;font-weight:700;color:var(--text-primary);margin:.5rem 0 .3rem;">Request Submitted!</h3>
                <p style="font-size:.82rem;color:var(--text-secondary);">Your adjustment request has been sent to your administrator.</p>
                <button class="adj-btn-submit" style="margin-top:1rem;" onclick="closeAdjModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
    let adjScheduleId = null;
    function openAdjModal(schedId, subjectName) {
        adjScheduleId = schedId;
        document.getElementById('adj-subject-label').textContent =
            subjectName ? 'Subject: ' + subjectName : 'Submit a request to your department administrator.';
        document.getElementById('adj-reason').value = '';
        document.getElementById('adj-proposed').value = '';
        document.getElementById('adj-type').value = 'time_change';
        document.getElementById('adj-form-wrap').style.display = '';
        document.getElementById('adj-success').style.display = 'none';
        document.getElementById('adj-overlay').classList.add('active');
    }
    function closeAdjModal(e) {
        if (e && e.target !== document.getElementById('adj-overlay')) return;
        document.getElementById('adj-overlay').classList.remove('active');
    }
    async function submitAdjRequest() {
        const reason = document.getElementById('adj-reason').value.trim();
        if (reason.length < 10) {
            alert('Please provide a reason of at least 10 characters.');
            return;
        }
        const btn = document.getElementById('adj-submit-btn');
        btn.disabled = true;
        btn.textContent = 'Submitting...';
        try {
            const res = await fetch('/api/grade-school-teacher/adjustment-requests', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''
                },
                body: JSON.stringify({
                    schedule_id:      adjScheduleId,
                    request_type:     document.getElementById('adj-type').value,
                    reason:           document.getElementById('adj-reason').value.trim(),
                    proposed_changes: document.getElementById('adj-proposed').value.trim() || null,
                })
            });
            const json = await res.json();
            if (json.success) {
                document.getElementById('adj-form-wrap').style.display = 'none';
                document.getElementById('adj-success').style.display = 'block';
            } else {
                alert('Error: ' + (json.message ?? 'Could not submit request.'));
            }
        } catch (err) {
            alert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Submit Request';
        }
    }
    </script>
@endsection
