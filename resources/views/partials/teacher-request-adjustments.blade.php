@php
    $apiBase = $apiBase ?? '/api/teacher/adjustment-requests';
    $leaveApiBase = $leaveApiBase ?? (str_contains($apiBase, 'grade-school-teacher')
        ? '/api/grade-school-teacher/leave-requests'
        : '/api/teacher/leave-requests');
    $schedulesApi = $schedulesApi ?? (str_contains($apiBase, 'grade-school-teacher')
        ? '/api/grade-school-teacher/adjustment-schedules'
        : '/api/teacher/adjustment-schedules');
    $availableTeachersApi = $availableTeachersApi ?? (str_contains($apiBase, 'grade-school-teacher')
        ? '/api/grade-school-teacher/adjustment-available-teachers'
        : '/api/teacher/adjustment-available-teachers');
    $divisionLabel = $divisionLabel ?? 'Teacher Portal';
    $gradeLevels = $gradeLevels ?? ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
    $schoolLevel = $schoolLevel ?? 'junior_high';
    $scheduleSlots = $scheduleSlots ?? \App\Support\SchoolScheduleSlots::classSlotsForSchoolLevel($schoolLevel);
    $classSlotsByDay = $classSlotsByDay ?? \App\Support\SchoolScheduleSlots::classSlotsByDayForSchoolLevel($schoolLevel);
@endphp

<style>
    .tabs{display:flex;gap:0;border-bottom:2px solid var(--border-color);margin-bottom:2rem}
    .tab{padding:.75rem 1.5rem;cursor:pointer;font-weight:600;color:var(--text-secondary);border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s;font-size:.9rem}
    .tab.active{color:var(--green-primary);border-bottom-color:var(--green-primary)}
    .tab-pane{display:none}
    .tab-pane.active{display:block}
    .form-card{background:var(--bg-secondary);border-radius:.75rem;padding:1.75rem;border:1px solid var(--border-color);margin-bottom:2rem}
    .card-title{font-size:1.1rem;font-weight:600;color:var(--text-primary);margin-bottom:1.25rem;border-bottom:2px solid var(--green-primary);padding-bottom:.75rem}
    .form-group{margin-bottom:1.25rem}
    .form-group label{display:block;font-weight:600;margin-bottom:.35rem;color:var(--text-primary);font-size:.875rem}
    .form-group select,.form-group input,.form-group textarea{width:100%;padding:.65rem .75rem;border:1px solid var(--border-color);border-radius:.5rem;background:var(--bg-primary);color:var(--text-primary);font-family:inherit;font-size:.875rem;box-sizing:border-box}
    .form-group select:focus,.form-group input:focus,.form-group textarea:focus{outline:none;border-color:var(--green-primary)}
    .form-group textarea{resize:vertical;min-height:100px}
    .btn-primary{background:var(--green-primary);color:white;padding:.7rem 1.5rem;border:none;border-radius:.5rem;cursor:pointer;font-weight:600;font-size:.875rem;transition:all .2s}
    .btn-primary:hover{background:#1a5c3a;transform:translateY(-1px)}
    .req-card{background:var(--bg-secondary);border-radius:.75rem;padding:1.5rem;border:1px solid var(--border-color);border-left:4px solid var(--green-primary);margin-bottom:1rem}
    .req-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.75rem}
    .req-type{font-weight:700;color:var(--text-primary);font-size:.95rem}
    .badge{display:inline-block;padding:.2rem .65rem;border-radius:.25rem;font-size:.75rem;font-weight:600}
    .badge-pending{background:rgba(255,152,0,.12);color:#e65100}
    .badge-approved{background:rgba(76,175,80,.12);color:#2e7d32}
    .badge-rejected{background:rgba(244,67,54,.12);color:#c62828}
    .req-reason{background:var(--bg-primary);padding:.85rem;border-left:3px solid var(--green-primary);border-radius:.375rem;font-size:.875rem;color:var(--text-primary);margin:.5rem 0}
    .req-meta{font-size:.8rem;color:var(--text-secondary)}
    .toast{position:fixed;top:1.25rem;right:1.25rem;padding:.85rem 1.25rem;border-radius:.5rem;font-size:.875rem;font-weight:600;z-index:9999;display:none}
    .toast-success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
    .toast-error{background:#ffebee;color:#c62828;border:1px solid #ef9a9a}
    .empty-state{text-align:center;padding:2.5rem;color:var(--text-secondary)}
</style>

@include('partials.teacher-page-banner', [
    'eyebrow' => $divisionLabel,
    'pageTitle' => 'Request Schedule Adjustments',
    'pageSubtitle' => 'Submit schedule change requests to the administration',
    'notificationsApi' => str_contains($apiBase, 'grade-school-teacher')
        ? '/api/grade-school-teacher/notifications'
        : '/api/teacher/notifications',
])

<div class="tabs">
    <div class="tab active" data-tab="new" onclick="switchTab('new')">Schedule Adjustment</div>
    <div class="tab" data-tab="leave" onclick="switchTab('leave')">Absence / Leave</div>
    <div class="tab" data-tab="history" onclick="switchTab('history')">My Requests</div>
</div>

<div id="tab-new" class="tab-pane active">
    <div class="form-card">
        <p class="card-title">Create Adjustment Request</p>
        <form id="reqForm" onsubmit="submitRequest(event)">
            @csrf
            <div class="form-group">
                <label>Adjustment Type *</label>
                <select name="request_type" id="adjRequestType" required>
                    <option value="">-- Select Type --</option>
                    <option value="schedule_change">Schedule Change</option>
                    <option value="room_change">Room Change</option>
                    <option value="teacher_reassignment">Teacher Reassignment</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div id="adjBlockContext">
                <div class="form-group">
                    <label>Subject *</label>
                    <select name="subject" id="adjSubject">
                        <option value="">-- Select subject from your schedule --</option>
                    </select>
                    <small style="color:var(--text-secondary);font-size:.75rem;">Only subjects from your approved class schedules are listed.</small>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Grade Level *</label>
                        <select name="grade_level" id="adjGradeLevel">
                            <option value="">-- Select --</option>
                            @foreach($gradeLevels as $g)
                                <option value="{{ $g }}">{{ $g }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Section</label>
                        <input type="text" name="section_name" id="adjSection" placeholder="Auto-filled from schedule" readonly>
                    </div>
                </div>
            </div>

            <div id="adjBlockSchedulePrefs" style="display:none;margin-top:1rem;">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Preferred Day</label>
                        <select name="day_of_week" id="adjDay">
                            <option value="">-- Day --</option>
                            @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                                <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;grid-column:span 2;">
                        <label>Preferred Class Period *</label>
                        <select id="adjPeriod" required>
                            <option value="">-- Select official period --</option>
                            @foreach($scheduleSlots as $slot)
                                <option value="{{ $slot['start'] }}|{{ $slot['end'] }}">{{ $slot['label'] }}</option>
                            @endforeach
                        </select>
                        <small style="color:var(--text-secondary);font-size:.75rem;display:block;margin-top:.35rem;">
                            Times follow the {{ str_contains($schoolLevel, 'grade') ? 'Grade School' : 'Junior High' }} class schedule (official period slots only).
                        </small>
                        <input type="hidden" name="preferred_start_time" id="adjStart">
                        <input type="hidden" name="preferred_end_time" id="adjEnd">
                    </div>
                </div>
            </div>

            <div id="adjBlockReassignment" style="display:none;margin-top:1rem;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Requested Substitute Teacher *</label>
                    <select name="substitute_faculty_id" id="adjSubstitute">
                        <option value="">-- Select subject and grade first --</option>
                    </select>
                    <input type="hidden" name="substitute_teacher_name" id="adjSubstituteName">
                    <small style="color:var(--text-secondary);font-size:.75rem;display:block;margin-top:.35rem;">Lists regular and shared teachers available for this subject and grade level.</small>
                </div>
            </div>

            <input type="hidden" name="schedule_id" id="adjScheduleId">

            <div id="adjBlockDate" style="display:none;margin-top:1rem;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Preferred Date *</label>
                    <input type="date" name="preferred_date" id="adjPreferredDate">
                    <small style="color:var(--text-secondary);font-size:.75rem;display:block;margin-top:.35rem;">
                        Date when you want this schedule adjustment to take effect.
                    </small>
                </div>
            </div>

            <div id="adjBlockReason" class="form-group" style="display:none;margin-top:1rem;">
                <label>Reason for Adjustment *</label>
                <textarea name="reason" id="adjReason" placeholder="Explain why an adjustment is needed..." minlength="3"></textarea>
            </div>
            <div id="adjBlockExtra" class="form-group" style="display:none;">
                <label>Additional Details (optional)</label>
                <textarea name="proposed_changes" id="adjExtra" placeholder="Any extra notes for the admin..."></textarea>
            </div>
            <button type="submit" class="btn-primary">Submit Request</button>
        </form>
    </div>
</div>

<div id="tab-leave" class="tab-pane">
    <div class="form-card">
        <p class="card-title">Request Absence or Leave</p>
        <p style="font-size:.85rem;color:var(--text-secondary);margin:-0.5rem 0 1.25rem;">Saved to <strong>teacher_leave_requests</strong> and shown in admin <strong>All Requests → Absence / Leave</strong>.</p>
        <form id="leaveForm" onsubmit="submitLeaveRequest(event)">
            @csrf
            <div class="form-group">
                <label>Leave Type *</label>
                <select name="leave_type" required>
                    <option value="">-- Select --</option>
                    <option value="absent">Absent (single day)</option>
                    <option value="sick_leave">Sick Leave</option>
                    <option value="vacation_leave">Vacation Leave</option>
                    <option value="emergency_leave">Emergency Leave</option>
                    <option value="official_business">Official Business</option>
                    <option value="leave_other">Other</option>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Date From *</label>
                    <input type="date" name="date_from" required>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label>Date To *</label>
                    <input type="date" name="date_to" required>
                </div>
            </div>
            <div class="form-group" style="margin-top:1rem;">
                <label>Reason *</label>
                <textarea name="reason" placeholder="Explain your absence or leave..." required minlength="3"></textarea>
            </div>
            <div class="form-group">
                <label>Additional Notes (optional)</label>
                <textarea name="proposed_changes" placeholder="Supporting details for the admin..."></textarea>
            </div>
            <button type="submit" class="btn-primary">Submit Absence / Leave</button>
        </form>
    </div>
</div>

<div id="tab-history" class="tab-pane">
    <div id="reqs-container">
        <p class="empty-state">Loading your requests...</p>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
const ADJUSTMENT_API = @json($apiBase);
const LEAVE_API = @json($leaveApiBase);
const ADJUSTMENT_SCHEDULES_API = @json($schedulesApi);
const ADJ_AVAILABLE_TEACHERS_API = @json($availableTeachersApi);
const ADJ_SCHEDULE_SLOTS = @json($scheduleSlots);
const ADJ_SLOTS_BY_DAY = @json($classSlotsByDay);
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let adjApprovedSchedules = [];
let adjSlotTouched = false;
let adjAvailableTeachers = [];

function adjNormalizeTime(t) {
    if (!t) return '';
    const s = String(t).trim();
    const m = s.match(/^(\d{1,2}):(\d{2})/);
    return m ? `${String(m[1]).padStart(2, '0')}:${m[2]}` : s.slice(0, 5);
}

function adjSlotsForDay(day) {
    if (ADJ_SLOTS_BY_DAY && day && ADJ_SLOTS_BY_DAY[day]) {
        return ADJ_SLOTS_BY_DAY[day];
    }
    if (ADJ_SLOTS_BY_DAY && ADJ_SLOTS_BY_DAY.default) {
        return ADJ_SLOTS_BY_DAY.default;
    }
    return ADJ_SCHEDULE_SLOTS;
}

function adjRebuildPeriodOptions(day) {
    const period = document.getElementById('adjPeriod');
    if (!period) return;
    const slots = adjSlotsForDay(day);
    const prev = period.value;
    period.innerHTML = '<option value="">-- Select official period --</option>';
    slots.forEach(function (slot) {
        const opt = document.createElement('option');
        opt.value = slot.start + '|' + slot.end;
        opt.textContent = slot.label;
        period.appendChild(opt);
    });
    if (prev) {
        period.value = prev;
        if (period.value !== prev) {
            adjSetPeriodFromTimes('', '');
        }
    }
}

function adjSetPeriodFromTimes(start, end) {
    const period = document.getElementById('adjPeriod');
    const startInp = document.getElementById('adjStart');
    const endInp = document.getElementById('adjEnd');
    const ns = adjNormalizeTime(start);
    const ne = adjNormalizeTime(end);
    if (startInp) startInp.value = ns;
    if (endInp) endInp.value = ne;
    if (!period) return;
    const key = ns && ne ? `${ns}|${ne}` : '';
    let matched = false;
    [...period.options].forEach(opt => {
        if (opt.value === key) {
            period.value = key;
            matched = true;
        }
    });
    if (!matched) period.value = '';
}

function adjApplyPeriodSelection() {
    const period = document.getElementById('adjPeriod');
    if (!period || !period.value) {
        const startInp = document.getElementById('adjStart');
        const endInp = document.getElementById('adjEnd');
        if (startInp) startInp.value = '';
        if (endInp) endInp.value = '';
        return;
    }
    const [start, end] = period.value.split('|');
    adjSetPeriodFromTimes(start, end);
}

function adjNorm(s) {
    return String(s || '').trim().toLowerCase();
}

function adjPopulateGradeLevels() {
    const gradeSel = document.getElementById('adjGradeLevel');
    if (!gradeSel) return;
    const fromSchedules = [...new Set(adjApprovedSchedules.map(s => s.grade_level).filter(Boolean))];
    const defaults = @json($gradeLevels);
    const merged = [...new Set([...defaults, ...fromSchedules])];
    const current = gradeSel.value;
    gradeSel.innerHTML = '<option value="">-- Select --</option>';
    merged.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g;
        opt.textContent = g;
        gradeSel.appendChild(opt);
    });
    if (current && merged.includes(current)) gradeSel.value = current;
}

function adjGradeKey(g) {
    const m = String(g || '').match(/\d+/);
    return m ? m[0] : adjNorm(g);
}

async function loadAdjustmentSchedules() {
    const subjSel = document.getElementById('adjSubject');
    if (!subjSel) return;
    try {
        const res = await fetch(ADJUSTMENT_SCHEDULES_API, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.message || 'Failed to load schedules');
        adjApprovedSchedules = json.schedules || [];
        const subjects = json.subjects && json.subjects.length
            ? json.subjects
            : [...new Set(adjApprovedSchedules.map(s => s.subject).filter(Boolean))];
        subjSel.innerHTML = '<option value="">-- Select subject from your schedule --</option>';
        subjects.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub;
            opt.textContent = sub;
            subjSel.appendChild(opt);
        });
        adjPopulateGradeLevels();
    } catch (err) {
        subjSel.innerHTML = '<option value="">Unable to load subjects</option>';
        console.error(err);
    }
}

function adjFillSlotFromSchedule() {
    if (adjSlotTouched) return;
    const subject = document.getElementById('adjSubject')?.value || '';
    const grade = document.getElementById('adjGradeLevel')?.value || '';
    if (!subject || !grade) return;

    const matches = adjApprovedSchedules.filter(s =>
        adjNorm(s.subject) === adjNorm(subject) && adjGradeKey(s.grade_level) === adjGradeKey(grade)
    );
    const slot = matches[0] || null;
    const sec = document.getElementById('adjSection');
    const day = document.getElementById('adjDay');
    const sid = document.getElementById('adjScheduleId');

    if (!slot) {
        if (sec) sec.value = '';
        if (day) day.value = '';
        adjSetPeriodFromTimes('', '');
        if (sid) sid.value = '';
        return;
    }

    if (sec) sec.value = slot.section_name || '';
    if (day) day.value = slot.day_of_week || '';
    adjSetPeriodFromTimes(slot.start_time, slot.end_time);
    if (sid) sid.value = slot.id ? String(slot.id) : '';
    const dateInp = document.getElementById('adjPreferredDate');
    if (dateInp && slot.schedule_date && !dateInp.value) {
        dateInp.value = String(slot.schedule_date).slice(0, 10);
    }
}

function adjMarkSlotTouched() {
    adjSlotTouched = true;
}

function adjNormalizedType() {
    const t = document.getElementById('adjRequestType')?.value || '';
    return t === 'time_change' ? 'schedule_change' : t;
}

function adjSetRequired(el, on) {
    if (!el) return;
    if (on) el.setAttribute('required', 'required');
    else el.removeAttribute('required');
}

function adjUpdateFormLayout() {
    const type = adjNormalizedType();
    const ctx = document.getElementById('adjBlockContext');
    const prefs = document.getElementById('adjBlockSchedulePrefs');
    const reassign = document.getElementById('adjBlockReassignment');
    const reason = document.getElementById('adjBlockReason');
    const extra = document.getElementById('adjBlockExtra');
    const dateBlock = document.getElementById('adjBlockDate');
    const subj = document.getElementById('adjSubject');
    const grade = document.getElementById('adjGradeLevel');
    const sub = document.getElementById('adjSubstitute');
    const reasonFld = document.getElementById('adjReason');
    const period = document.getElementById('adjPeriod');
    const dateFld = document.getElementById('adjPreferredDate');

    const showCtx = ['schedule_change', 'room_change', 'teacher_reassignment', 'other'].includes(type);
    const showPrefs = type === 'schedule_change';
    const showReassign = type === 'teacher_reassignment';
    const showReason = ['schedule_change', 'room_change', 'teacher_reassignment', 'other'].includes(type);
    const showExtra = type === 'schedule_change' || type === 'other';

    if (ctx) ctx.style.display = showCtx ? '' : 'none';
    if (prefs) prefs.style.display = showPrefs ? '' : 'none';
    if (reassign) reassign.style.display = showReassign ? '' : 'none';
    if (reason) reason.style.display = showReason ? '' : 'none';
    if (extra) extra.style.display = showExtra ? '' : 'none';

    adjSetRequired(subj, showCtx && type !== 'other');
    adjSetRequired(grade, showCtx && type !== 'other');
    adjSetRequired(sub, showReassign);
    adjSetRequired(reasonFld, showReason);
    adjSetRequired(period, showPrefs);

    if (showReassign) {
        adjLoadAvailableTeachers();
    }
}

async function adjLoadAvailableTeachers() {
    const subj = document.getElementById('adjSubject')?.value || '';
    const grade = document.getElementById('adjGradeLevel')?.value || '';
    const sel = document.getElementById('adjSubstitute');
    const nameInp = document.getElementById('adjSubstituteName');
    if (!sel) return;

    if (!subj || !grade) {
        sel.innerHTML = '<option value="">-- Select subject and grade first --</option>';
        if (nameInp) nameInp.value = '';
        return;
    }

    sel.innerHTML = '<option value="">Loading teachers...</option>';
    try {
        const params = new URLSearchParams({ subject: subj, grade_level: grade });
        const res = await fetch(ADJ_AVAILABLE_TEACHERS_API + '?' + params, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.message || 'Failed');
        adjAvailableTeachers = json.teachers || [];
        if (!adjAvailableTeachers.length) {
            sel.innerHTML = '<option value="">No available teachers for this subject/grade</option>';
            return;
        }
        sel.innerHTML = '<option value="">-- Select teacher --</option>';
        adjAvailableTeachers.forEach(t => {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name + (t.kind === 'shared_teacher' ? ' (Shared)' : '');
            opt.dataset.name = t.name;
            sel.appendChild(opt);
        });
    } catch (e) {
        sel.innerHTML = '<option value="">Unable to load teachers</option>';
        console.error(e);
    }
}

document.getElementById('adjRequestType')?.addEventListener('change', adjUpdateFormLayout);

document.getElementById('adjSubject')?.addEventListener('change', function() {
    adjSlotTouched = false;
    const subject = this.value || '';
    const gradeSel = document.getElementById('adjGradeLevel');
    if (subject && gradeSel) {
        const grades = [...new Set(adjApprovedSchedules
            .filter(s => adjNorm(s.subject) === adjNorm(subject))
            .map(s => s.grade_level)
            .filter(Boolean))];
        if (grades.length === 1) gradeSel.value = grades[0];
    }
    adjFillSlotFromSchedule();
    if (adjNormalizedType() === 'teacher_reassignment') adjLoadAvailableTeachers();
});
document.getElementById('adjGradeLevel')?.addEventListener('change', function() {
    adjSlotTouched = false;
    adjFillSlotFromSchedule();
    if (adjNormalizedType() === 'teacher_reassignment') adjLoadAvailableTeachers();
});
document.getElementById('adjSubstitute')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const nameInp = document.getElementById('adjSubstituteName');
    if (nameInp) nameInp.value = opt?.dataset?.name || opt?.textContent?.replace(/\s*\(Shared\)\s*$/, '') || '';
});
document.getElementById('adjDay')?.addEventListener('change', function () {
    adjMarkSlotTouched();
    adjRebuildPeriodOptions(this.value || '');
});
document.getElementById('adjPeriod')?.addEventListener('change', function() {
    adjMarkSlotTouched();
    adjApplyPeriodSelection();
});

loadAdjustmentSchedules();
adjUpdateFormLayout();
adjRebuildPeriodOptions(document.getElementById('adjDay')?.value || '');

function switchTab(name) {
    document.querySelectorAll('.tab').forEach(t => t.classList.toggle('active', t.dataset.tab === name));
    document.getElementById('tab-new').classList.toggle('active', name === 'new');
    document.getElementById('tab-leave').classList.toggle('active', name === 'leave');
    document.getElementById('tab-history').classList.toggle('active', name === 'history');
    if (name === 'history') loadRequests();
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s ?? '';
    return d.innerHTML;
}

function formatProposed(raw) {
    if (!raw) return '';
    try {
        const p = JSON.parse(raw);
        if (typeof p !== 'object' || !p) return escapeHtml(raw);
        const parts = [];
        if (p.subject) parts.push('Subject: ' + p.subject);
        if (p.grade_level || p.section_name) parts.push((p.grade_level || '') + (p.section_name ? ' – ' + p.section_name : ''));
        if (p.preferred_date) parts.push('Date: ' + p.preferred_date);
        if (p.day_of_week) parts.push('Day: ' + p.day_of_week);
        if (p.preferred_start_time) parts.push('Time: ' + p.preferred_start_time + (p.preferred_end_time ? ' – ' + p.preferred_end_time : ''));
        if (p.substitute_teacher_name) parts.push('Substitute: ' + p.substitute_teacher_name);
        if (p.date_from) parts.push('Dates: ' + p.date_from + (p.date_to ? ' – ' + p.date_to : ''));
        if (p.detail) parts.push(p.detail);
        return escapeHtml(parts.filter(Boolean).join(' | ') || raw);
    } catch {
        return escapeHtml(raw);
    }
}

function adjFormatValidationErrors(json) {
    if (!json?.errors || typeof json.errors !== 'object') return json?.message || 'Could not submit request';
    return Object.values(json.errors).flat().filter(Boolean).join(' ');
}

async function submitRequest(e) {
    e.preventDefault();
    adjApplyPeriodSelection();
    const form = e.target;
    const body = Object.fromEntries(new FormData(form).entries());
    delete body._token;
    const uiType = body.request_type;
    if (uiType === 'schedule_change') body.request_type = 'time_change';
    const type = body.request_type;
    if (!['time_change', 'room_change', 'teacher_reassignment', 'other'].includes(type)) {
        showToast('Please select an adjustment type.', 'error');
        return;
    }
    if (type !== 'time_change') {
        delete body.day_of_week;
        delete body.preferred_start_time;
        delete body.preferred_end_time;
    } else if (!body.preferred_start_time || !body.preferred_end_time) {
        showToast('Please select a preferred class period from the official schedule.', 'error');
        return;
    }
    if (type !== 'teacher_reassignment') {
        delete body.substitute_faculty_id;
        delete body.substitute_teacher_name;
    }
    if (!body.schedule_id) delete body.schedule_id;
    if (body.substitute_faculty_id === '') delete body.substitute_faculty_id;
    try {
        const res = await fetch(ADJUSTMENT_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: JSON.stringify(body)
        });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(adjFormatValidationErrors(json));
        form.reset();
        adjSlotTouched = false;
        loadAdjustmentSchedules();
        adjUpdateFormLayout();
        showToast(json.message || 'Your request has been submitted successfully.', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

async function loadRequests() {
    try {
        const [adjRes, leaveRes] = await Promise.all([
            fetch(ADJUSTMENT_API, { headers: { 'Accept': 'application/json' } }),
            fetch(LEAVE_API, { headers: { 'Accept': 'application/json' } }),
        ]);
        const adjJson = await adjRes.json();
        const leaveJson = await leaveRes.json();
        if (!adjRes.ok || !adjJson.success) throw new Error(adjJson.message ?? 'Failed to load adjustments');
        const adjustments = (adjJson.data || []).map(r => ({ ...r, _kind: 'adjustment' }));
        const leaves = (leaveRes.ok && leaveJson.success ? (leaveJson.data || []) : []).map(r => ({
            ...r,
            _kind: 'leave',
            request_type: r.leave_type || r.request_type,
        }));
        const merged = [...adjustments, ...leaves].sort((a, b) => {
            const da = a.created_at ? new Date(a.created_at).getTime() : 0;
            const db = b.created_at ? new Date(b.created_at).getTime() : 0;
            return db - da;
        });
        renderRequests(merged);
    } catch (err) {
        document.getElementById('reqs-container').innerHTML = '<p class="empty-state">' + escapeHtml(err.message) + '</p>';
    }
}

const typeLabels = {
    schedule_change: 'Schedule Change',
    time_change: 'Schedule Change',
    room_change: 'Room Change',
    teacher_reassignment: 'Teacher Reassignment',
    section_change: 'Section Change',
    absent: 'Absent',
    sick_leave: 'Sick Leave',
    vacation_leave: 'Vacation Leave',
    emergency_leave: 'Emergency Leave',
    official_business: 'Official Business',
    leave_other: 'Other Leave',
    other: 'Other'
};

async function submitLeaveRequest(e) {
    e.preventDefault();
    const form = e.target;
    const body = Object.fromEntries(new FormData(form).entries());
    try {
        const res = await fetch(LEAVE_API, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'},
            body: JSON.stringify(body)
        });
        const json = await res.json();
        if (!res.ok || !json.success) throw new Error(json.message ?? 'Could not submit request');
        form.reset();
        showToast(json.message || 'Your absence/leave request has been submitted.', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    }
}

function renderRequests(data) {
    if (!data.length) {
        document.getElementById('reqs-container').innerHTML = '<p class="empty-state">No adjustment requests found.</p>';
        return;
    }
    document.getElementById('reqs-container').innerHTML = data.map(r => `
        <div class="req-card">
            <div class="req-header">
                <span class="req-type">${escapeHtml(typeLabels[r.request_type] ?? r.leave_type ?? r.request_type)}${r._kind === 'leave' ? ' <span style="font-size:.7rem;opacity:.7;">(Leave)</span>' : ''}</span>
                <span class="badge badge-${r.status}">${escapeHtml((r.status || 'pending').toUpperCase())}</span>
            </div>
            ${r.date_from ? `<div class="req-meta"><strong>Dates:</strong> ${escapeHtml(r.date_from)} – ${escapeHtml(r.date_to || '')}${r.total_days ? ' (' + r.total_days + ' day(s))' : ''}</div>` : ''}
            <div class="req-reason"><strong>Reason:</strong> ${escapeHtml(r.reason)}</div>
            ${r.proposed_changes ? `<div class="req-reason"><strong>Details:</strong> ${formatProposed(r.proposed_changes)}</div>` : ''}
            ${r.admin_notes && r.status !== 'pending' ? `<div class="req-reason" style="border-color:#f57f17"><strong>Admin:</strong> ${escapeHtml(r.admin_notes)}</div>` : ''}
            ${r.admin_notes ? `<div class="req-reason" style="border-color:#f57f17"><strong>Admin Notes:</strong> ${escapeHtml(r.admin_notes)}</div>` : ''}
            <p class="req-meta">Submitted: ${r.created_at ? new Date(r.created_at).toLocaleDateString() : '–'}</p>
        </div>
    `).join('');
}

function showToast(msg, type) {
    if (window.spupToast) {
        if (type === 'error') window.spupToast.error(msg);
        else if (type === 'warning') window.spupToast.warning(msg);
        else window.spupToast.success(msg);
        return;
    }
    const t = document.getElementById('toast');
    if (!t) return;
    t.className = 'toast toast-' + type;
    t.textContent = msg;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3000);
}
</script>
