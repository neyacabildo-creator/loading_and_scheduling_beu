{{-- resources/views/shared-teacher/requests.blade.php --}}
@extends('layouts.shared-teacher')

@section('title', 'Schedule Requests')

@push('styles')
<style>
    .st-req-hero {
        background: linear-gradient(135deg, rgba(26, 83, 54, 0.08) 0%, rgba(240, 192, 64, 0.1) 55%, rgba(99, 102, 241, 0.06) 100%);
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        padding: 1.75rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        position: relative;
        overflow: visible;
    }
    .st-req-hero h1 {
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        margin: 0 0 0.35rem;
        color: var(--text-primary);
    }
    .st-req-hero p {
        margin: 0;
        font-size: 0.9rem;
        color: var(--text-secondary);
        max-width: 32rem;
        line-height: 1.5;
    }
    .st-req-flash {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        border-radius: 0.65rem;
        padding: 0.9rem 1.15rem;
        margin-bottom: 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
    .st-req-flash.success {
        background: rgba(45, 122, 80, 0.1);
        border: 1px solid rgba(45, 122, 80, 0.3);
        color: #2d7a50;
    }
    .st-req-flash.error {
        background: rgba(220, 38, 38, 0.08);
        border: 1px solid rgba(220, 38, 38, 0.3);
        color: #dc2626;
    }
    .st-req-panel {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.75rem;
    }
    .st-req-panel-head {
        padding: 1.15rem 1.5rem;
        background: linear-gradient(90deg, rgba(45, 122, 80, 0.1) 0%, transparent 100%);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .st-req-panel-head.history {
        background: linear-gradient(90deg, rgba(99, 102, 241, 0.08) 0%, transparent 100%);
    }
    .st-req-panel-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .st-req-panel-title svg { color: var(--green-primary); flex-shrink: 0; }
    .st-req-panel-head.history .st-req-panel-title svg { color: #6366f1; }
    .st-req-panel-body { padding: 1.5rem; }
    .st-req-section {
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 1.15rem 1.25rem;
        margin-bottom: 1rem;
    }
    .st-req-section:last-of-type { margin-bottom: 0; }
    .st-req-section-head {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 1rem;
    }
    .st-req-section-num {
        width: 28px;
        height: 28px;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%);
        color: white;
        font-size: 0.75rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .st-req-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-primary);
    }
    .st-req-section-desc {
        font-size: 0.72rem;
        color: var(--text-secondary);
        margin-top: 0.1rem;
    }
    .req-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .req-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; }
    .req-form-grid-4 { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; }
    .st-req-field select:disabled, .st-req-field input:disabled {
        opacity: 0.55;
        cursor: not-allowed;
        background: var(--bg-tertiary);
    }
    .st-req-loading {
        font-size: 0.8rem;
        color: var(--green-primary);
        font-weight: 600;
        margin-top: 0.5rem;
        display: none;
    }
    .st-req-loading.visible { display: block; }
    .st-req-auto-hint {
        font-size: 0.72rem;
        color: var(--text-secondary);
        margin-top: 0.65rem;
        font-style: italic;
    }
    @media (max-width: 768px) {
        .req-form-grid, .req-form-grid-3, .req-form-grid-4 { grid-template-columns: 1fr; }
    }
    .st-req-field { margin-bottom: 0; }
    .st-req-field .form-label {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 0.45rem;
    }
    .st-req-field .form-input {
        background: var(--bg-secondary);
        border-radius: 0.5rem;
    }
    .st-req-field .form-input:focus {
        border-color: var(--green-primary);
        box-shadow: 0 0 0 3px rgba(45, 122, 80, 0.12);
    }
    .st-req-required { color: #dc2626; font-weight: 700; }
    .st-req-room-preview {
        margin-top: 0.85rem;
        padding: 0.65rem 0.9rem;
        background: linear-gradient(135deg, rgba(45, 122, 80, 0.1) 0%, rgba(26, 83, 54, 0.05) 100%);
        border: 1px dashed rgba(45, 122, 80, 0.35);
        border-radius: 0.5rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .st-req-room-preview strong {
        color: var(--green-dark);
        font-weight: 700;
    }
    .st-req-room-preview .st-room-val {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.55rem;
        background: var(--bg-secondary);
        border: 1px solid rgba(45, 122, 80, 0.25);
        border-radius: 0.375rem;
        color: var(--green-primary);
        font-weight: 600;
    }
    .st-req-room-preview .st-room-val.empty { color: var(--text-secondary); font-weight: 500; border-style: dashed; }
    .req-submit-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.35rem;
        border-top: 1px solid var(--border-color);
    }
    .st-req-submit-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.75rem 1.75rem;
        background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%);
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.9rem;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(45, 122, 80, 0.25);
        transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
    }
    .st-req-submit-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(45, 122, 80, 0.35);
        opacity: 0.95;
    }
    .st-req-submit-hint {
        font-size: 0.8rem;
        color: var(--text-secondary);
        line-height: 1.45;
        max-width: 22rem;
    }
    .st-req-history-stats { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .req-history-empty {
        padding: 3rem 1.5rem;
        text-align: center;
        color: var(--text-secondary);
    }
    .req-history-empty svg { margin: 0 auto 1rem; display: block; opacity: 0.35; }
    .st-req-history-table-wrap { overflow-x: auto; }
    .st-req-history-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .st-req-history-table thead th {
        padding: 0.7rem 1rem;
        background: var(--bg-tertiary);
        text-align: left;
        font-weight: 700;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
        white-space: nowrap;
    }
    .st-req-history-table tbody td {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: top;
    }
    .st-req-history-table tbody tr:last-child td { border-bottom: none; }
    .st-req-history-table tbody tr:hover td { background: rgba(45, 122, 80, 0.04); }
    .st-req-subject { font-weight: 700; color: var(--text-primary); }
    .st-req-room-cell {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.55rem;
        background: rgba(45, 122, 80, 0.08);
        border: 1px solid rgba(45, 122, 80, 0.2);
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--green-dark);
        white-space: nowrap;
    }
    .st-req-time-sub {
        display: block;
        font-size: 0.78rem;
        color: var(--text-secondary);
        margin-top: 0.15rem;
        font-variant-numeric: tabular-nums;
    }
    .st-req-notes-cell {
        font-size: 0.82rem;
        color: var(--text-secondary);
        max-width: 160px;
        line-height: 1.4;
    }
    .st-req-admin-response {
        font-size: 0.82rem;
        max-width: 160px;
        line-height: 1.4;
    }
    .st-req-admin-response em {
        color: var(--text-primary);
        font-style: normal;
        background: var(--bg-tertiary);
        padding: 0.2rem 0.45rem;
        border-radius: 0.25rem;
        border-left: 3px solid var(--green-primary);
        display: inline-block;
    }
    .st-req-date-cell {
        font-size: 0.8rem;
        color: var(--text-secondary);
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="st-req-hero">
    <div class="st-dash-hero-inner">
        <div class="st-dash-hero-text">
            <h1>Schedule Requests</h1>
            <p>Submit a new request to your JH or GS admin, and track the status of previous requests.</p>
        </div>
        @include('partials.shared-teacher-header-actions')
    </div>
</div>

{{-- New Request Form --}}
<div class="st-req-panel">
    <div class="st-req-panel-head">
        <div class="st-req-panel-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New Schedule Request
        </div>
    </div>
    <div class="st-req-panel-body">
        <form method="POST" action="{{ route('shared-teacher.requests.store') }}" id="stReqForm">
            @csrf

            <div class="st-req-section">
                <div class="st-req-section-head">
                    <span class="st-req-section-num">1</span>
                    <div>
                        <div class="st-req-section-title">Send To</div>
                        <div class="st-req-section-desc">Choose which school admin will review your request</div>
                    </div>
                </div>
                <div class="form-field st-req-field">
                    <label class="form-label">School Level <span class="st-req-required">*</span></label>
                    <select name="school_level" id="stReqSchoolLevel" class="form-input" required>
                        <option value="">— Select admin —</option>
                        <option value="jh" {{ old('school_level') === 'jh' ? 'selected' : '' }}>Junior High School Admin</option>
                        <option value="gs" {{ old('school_level') === 'gs' ? 'selected' : '' }}>Grade School Admin</option>
                    </select>
                    <p class="st-req-auto-hint">Choosing an admin loads only your approved schedules for that division.</p>
                    <p class="st-req-loading" id="stReqLoading">Loading schedules…</p>
                </div>
            </div>

            <div class="st-req-section" id="stReqClassSection">
                <div class="st-req-section-head">
                    <span class="st-req-section-num">2</span>
                    <div>
                        <div class="st-req-section-title">Class Details</div>
                        <div class="st-req-section-desc">Select from your approved schedules for the chosen school level</div>
                    </div>
                </div>
                <div class="req-form-grid" style="margin-bottom:1rem;">
                    <div class="form-field st-req-field">
                        <label class="form-label">Subject <span class="st-req-required">*</span></label>
                        <select name="subject" id="stReqSubject" class="form-input" required disabled>
                            <option value="">— Select school level first —</option>
                        </select>
                    </div>
                    <div class="form-field st-req-field">
                        <label class="form-label">Grade Level <span class="st-req-required">*</span></label>
                        <select name="grade_level" id="stReqGrade" class="form-input" required disabled>
                            <option value="">— Select subject first —</option>
                        </select>
                    </div>
                    <div class="form-field st-req-field" style="grid-column:1/-1;">
                        <label class="form-label">Section <span class="st-req-required">*</span></label>
                        <select name="section_name" id="stReqSection" class="form-input" required disabled>
                            <option value="">— Select grade first —</option>
                        </select>
                    </div>
                </div>
                <div class="st-req-room-preview" id="stReqRoomPreview">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    <span>Room preview:</span>
                    <span class="st-room-val empty" id="stReqRoomVal">Select grade &amp; section</span>
                </div>
                <input type="hidden" name="schedule_id" id="stReqScheduleId" value="{{ old('schedule_id') }}">
            </div>

            <div class="st-req-section">
                <div class="st-req-section-head">
                    <span class="st-req-section-num">3</span>
                    <div>
                        <div class="st-req-section-title">Schedule Slot</div>
                        <div class="st-req-section-desc">Filled automatically from your existing class schedule</div>
                    </div>
                </div>
                <div class="req-form-grid-4">
                    <div class="form-field st-req-field">
                        <label class="form-label">Day of Week</label>
                        <select name="day_of_week" id="stReqDay" class="form-input" disabled>
                            <option value="">— Select class first —</option>
                            @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day)
                                <option value="{{ $day }}" {{ old('day_of_week') === $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-field st-req-field">
                        <label class="form-label">Date</label>
                        <input type="date" name="schedule_date" id="stReqDate" class="form-input" value="{{ old('schedule_date') }}" disabled>
                    </div>
                    <div class="form-field st-req-field">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="preferred_start_time" id="stReqStart" class="form-input" value="{{ old('preferred_start_time') }}" disabled>
                    </div>
                    <div class="form-field st-req-field">
                        <label class="form-label">End Time</label>
                        <input type="time" name="preferred_end_time" id="stReqEnd" class="form-input" value="{{ old('preferred_end_time') }}" disabled>
                    </div>
                </div>
            </div>

            <div class="st-req-section">
                <div class="st-req-section-head">
                    <span class="st-req-section-num">4</span>
                    <div>
                        <div class="st-req-section-title">Additional Notes</div>
                        <div class="st-req-section-desc">Explain your request or any constraints</div>
                    </div>
                </div>
                <div class="form-field st-req-field">
                    <label class="form-label">Notes / Reason <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Explain your request, constraints, or preferences…" style="resize:vertical;">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="req-submit-row">
                <button type="submit" class="st-req-submit-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Submit Request
                </button>
                <span class="st-req-submit-hint">Your request will be reviewed by the admin of the selected school level.</span>
            </div>
        </form>
    </div>
</div>

{{-- Request History --}}
<div class="st-req-panel">
    <div class="st-req-panel-head history">
        <div class="st-req-panel-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            My Request History
        </div>
        @if(!$requests->isEmpty())
            @php
                $totalPending  = $requests->where('status','pending')->count();
                $totalApproved = $requests->where('status','approved')->count();
                $totalRejected = $requests->where('status','rejected')->count();
            @endphp
            <div class="st-req-history-stats">
                @if($totalPending > 0)<span class="status-pending">{{ $totalPending }} pending</span>@endif
                @if($totalApproved > 0)<span class="status-approved">{{ $totalApproved }} approved</span>@endif
                @if($totalRejected > 0)<span class="status-rejected">{{ $totalRejected }} rejected</span>@endif
            </div>
        @endif
    </div>

    @if($requests->isEmpty())
        <div class="req-history-empty">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p style="font-size:.95rem;font-weight:600;color:var(--text-primary);">No requests submitted yet</p>
            <p style="font-size:.82rem;margin-top:.25rem;">Use the form above to send your first schedule request.</p>
        </div>
    @else
    <div class="st-req-history-table-wrap">
        <table class="st-req-history-table">
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Subject</th>
                    <th>Room</th>
                    <th>Date</th>
                    <th>Day &amp; Time</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Admin Response</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                @php
                    $roomLabel = trim(($req->grade_level ?? '') . ' ' . ($req->section_name ?? ''));
                @endphp
                <tr>
                    <td>
                        <span class="{{ ($req->level ?? '') === 'jh' ? 'badge-jh' : 'badge-gs' }}">
                            {{ ($req->level ?? '') === 'jh' ? 'JH' : 'GS' }}
                        </span>
                    </td>
                    <td class="st-req-subject">{{ $req->subject ?? '—' }}</td>
                    <td>
                        @if($roomLabel !== '')
                            <span class="st-req-room-cell">{{ $roomLabel }}</span>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td class="st-req-date-cell">
                        @if(!empty($req->schedule_date))
                            {{ \Carbon\Carbon::parse($req->schedule_date)->format('M d, Y') }}
                        @elseif(!empty($req->schedule_date_label))
                            {{ $req->schedule_date_label }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        {{ $req->day_of_week ?: '—' }}
                        @if($req->preferred_start_time)
                            <span class="st-req-time-sub">
                                @php
                                    try { echo \Carbon\Carbon::createFromFormat('H:i:s', $req->preferred_start_time)->format('h:i A'); } catch(\Exception $e) { echo $req->preferred_start_time; }
                                @endphp
                                @if($req->preferred_end_time)
                                    –
                                    @php
                                        try { echo \Carbon\Carbon::createFromFormat('H:i:s', $req->preferred_end_time)->format('h:i A'); } catch(\Exception $e) { echo $req->preferred_end_time; }
                                    @endphp
                                @endif
                            </span>
                        @endif
                    </td>
                    <td class="st-req-notes-cell">{{ $req->description ?? '—' }}</td>
                    <td><span class="status-{{ $req->status ?? 'pending' }}">{{ ucfirst($req->status ?? 'pending') }}</span></td>
                    <td class="st-req-admin-response">
                        @if($req->admin_notes)
                            <em>"{{ $req->admin_notes }}"</em>
                        @else
                            <span style="opacity:.5;">—</span>
                        @endif
                    </td>
                    <td class="st-req-date-cell">{{ \Carbon\Carbon::parse($req->created_at)->format('M d, Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    var SCHEDULES_URL = @json(route('shared-teacher.requests.schedules'));
    var schoolEl = document.getElementById('stReqSchoolLevel');
    var subjectEl = document.getElementById('stReqSubject');
    var gradeEl = document.getElementById('stReqGrade');
    var sectionEl = document.getElementById('stReqSection');
    var dayEl = document.getElementById('stReqDay');
    var dateEl = document.getElementById('stReqDate');
    var startEl = document.getElementById('stReqStart');
    var endEl = document.getElementById('stReqEnd');
    var scheduleIdEl = document.getElementById('stReqScheduleId');
    var roomVal = document.getElementById('stReqRoomVal');
    var loadingEl = document.getElementById('stReqLoading');

    if (!schoolEl) return;

    var stSchedules = [];
    var slotTouched = false;

    function norm(s) { return String(s || '').trim().toLowerCase(); }
    function gradeKey(g) {
        var m = String(g || '').match(/\d+/);
        return m ? m[0] : norm(g);
    }

    function setSelect(el, placeholder, options, enabled) {
        if (!el) return;
        el.innerHTML = '<option value="">' + placeholder + '</option>';
        (options || []).forEach(function (opt) {
            var o = document.createElement('option');
            o.value = opt.value;
            o.textContent = opt.label;
            el.appendChild(o);
        });
        el.disabled = !enabled;
    }

    function resetBelow(level) {
        if (level === 'all' || level === 'school') {
            setSelect(subjectEl, '— Select school level first —', [], false);
            setSelect(gradeEl, '— Select subject first —', [], false);
            setSelect(sectionEl, '— Select grade first —', [], false);
            stSchedules = [];
        }
        if (level === 'all' || level === 'school' || level === 'subject') {
            if (level !== 'school') setSelect(gradeEl, '— Select subject first —', [], false);
            if (level !== 'school' && level !== 'subject') setSelect(sectionEl, '— Select grade first —', [], false);
        }
        clearSlot();
        updateRoomPreview();
    }

    function clearSlot() {
        if (!slotTouched) {
            if (dayEl) { dayEl.value = ''; dayEl.disabled = true; }
            if (dateEl) { dateEl.value = ''; dateEl.disabled = true; }
            if (startEl) { startEl.value = ''; startEl.disabled = true; }
            if (endEl) { endEl.value = ''; endEl.disabled = true; }
            if (scheduleIdEl) scheduleIdEl.value = '';
        }
    }

    function filtered(by) {
        return stSchedules.filter(function (s) {
            if (by.subject && norm(s.subject) !== norm(by.subject)) return false;
            if (by.grade && gradeKey(s.grade_level) !== gradeKey(by.grade)) return false;
            if (by.section && norm(s.section_name) !== norm(by.section)) return false;
            return true;
        });
    }

    function uniqueSubjects() {
        var seen = {};
        return stSchedules
            .map(function (s) { return s.subject; })
            .filter(function (sub) {
                if (!sub) return false;
                var k = norm(sub);
                if (seen[k]) return false;
                seen[k] = true;
                return true;
            })
            .sort()
            .map(function (sub) { return { value: sub, label: sub }; });
    }

    function uniqueGrades(subject) {
        var seen = {};
        return filtered({ subject: subject })
            .map(function (s) { return s.grade_level; })
            .filter(function (g) {
                if (!g) return false;
                var k = gradeKey(g);
                if (seen[k]) return false;
                seen[k] = g;
                return true;
            })
            .sort()
            .map(function (g) { return { value: g, label: g }; });
    }

    function uniqueSections(subject, grade) {
        var seen = {};
        return filtered({ subject: subject, grade: grade })
            .map(function (s) { return s.section_name; })
            .filter(function (sec) {
                if (!sec) return false;
                var k = norm(sec);
                if (seen[k]) return false;
                seen[k] = sec;
                return true;
            })
            .sort()
            .map(function (sec) { return { value: sec, label: sec }; });
    }

    function updateRoomPreview() {
        if (!roomVal || !gradeEl || !sectionEl) return;
        var label = (gradeEl.value.trim() + ' ' + sectionEl.value.trim()).trim();
        if (label) {
            roomVal.textContent = label;
            roomVal.classList.remove('empty');
        } else {
            roomVal.textContent = 'Select grade & section';
            roomVal.classList.add('empty');
        }
    }

    function fillSlotFromSchedule() {
        if (slotTouched) return;
        var subject = subjectEl ? subjectEl.value : '';
        var grade = gradeEl ? gradeEl.value : '';
        var section = sectionEl ? sectionEl.value : '';
        if (!subject || !grade || !section) {
            clearSlot();
            return;
        }

        var matches = filtered({ subject: subject, grade: grade, section: section });
        var slot = matches[0] || null;

        if (!slot) {
            clearSlot();
            return;
        }

        if (dayEl) { dayEl.value = slot.day_of_week || ''; dayEl.disabled = false; }
        if (dateEl) { dateEl.value = slot.schedule_date || ''; dateEl.disabled = false; }
        if (startEl) { startEl.value = slot.start_time || ''; startEl.disabled = false; }
        if (endEl) { endEl.value = slot.end_time || ''; endEl.disabled = false; }
        if (scheduleIdEl) scheduleIdEl.value = slot.id ? String(slot.id) : '';
    }

    function markSlotTouched() { slotTouched = true; }

    async function loadSchedulesForLevel(level) {
        resetBelow('school');
        if (!level) return;

        if (loadingEl) loadingEl.classList.add('visible');
        try {
            var res = await fetch(SCHEDULES_URL + '?school_level=' + encodeURIComponent(level), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            var json = await res.json();
            if (!res.ok || !json.success) throw new Error(json.message || 'Failed to load schedules');

            stSchedules = json.schedules || [];
            slotTouched = false;

            if (stSchedules.length === 0) {
                setSelect(subjectEl, '— No approved schedules for this level —', [], false);
                return;
            }

            setSelect(subjectEl, '— Select subject —', uniqueSubjects(), true);
            setSelect(gradeEl, '— Select subject first —', [], false);
            setSelect(sectionEl, '— Select grade first —', [], false);
        } catch (err) {
            console.error(err);
            setSelect(subjectEl, '— Could not load schedules —', [], false);
            alert('Could not load schedules for the selected school level. Please try again.');
        } finally {
            if (loadingEl) loadingEl.classList.remove('visible');
        }
    }

    schoolEl.addEventListener('change', function () {
        slotTouched = false;
        loadSchedulesForLevel(schoolEl.value);
    });

    subjectEl && subjectEl.addEventListener('change', function () {
        slotTouched = false;
        setSelect(gradeEl, '— Select grade —', uniqueGrades(subjectEl.value), !!subjectEl.value);
        setSelect(sectionEl, '— Select grade first —', [], false);
        clearSlot();
        updateRoomPreview();
    });

    gradeEl && gradeEl.addEventListener('change', function () {
        slotTouched = false;
        setSelect(sectionEl, '— Select section —', uniqueSections(subjectEl.value, gradeEl.value), !!gradeEl.value);
        clearSlot();
        updateRoomPreview();
    });

    sectionEl && sectionEl.addEventListener('change', function () {
        slotTouched = false;
        updateRoomPreview();
        fillSlotFromSchedule();
    });

    [dayEl, dateEl, startEl, endEl].forEach(function (el) {
        if (!el) return;
        el.addEventListener('input', markSlotTouched);
        el.addEventListener('change', markSlotTouched);
    });

    if (schoolEl.value) {
        loadSchedulesForLevel(schoolEl.value).then(function () {
            var oldSubject = @json(old('subject'));
            var oldGrade = @json(old('grade_level'));
            var oldSection = @json(old('section_name'));
            if (oldSubject && subjectEl) {
                subjectEl.value = oldSubject;
                subjectEl.dispatchEvent(new Event('change'));
            }
            if (oldGrade && gradeEl) {
                gradeEl.value = oldGrade;
                gradeEl.dispatchEvent(new Event('change'));
            }
            if (oldSection && sectionEl) {
                sectionEl.value = oldSection;
                sectionEl.dispatchEvent(new Event('change'));
            }
            fillSlotFromSchedule();
            if (@json(old('day_of_week')) && dayEl) dayEl.value = @json(old('day_of_week'));
            if (@json(old('schedule_date')) && dateEl) dateEl.value = @json(old('schedule_date'));
            if (@json(old('preferred_start_time')) && startEl) startEl.value = @json(old('preferred_start_time'));
            if (@json(old('preferred_end_time')) && endEl) endEl.value = @json(old('preferred_end_time'));
        });
    }
})();
</script>
@endpush
