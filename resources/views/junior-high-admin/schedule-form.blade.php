{{-- resources/views/junior-high-admin/schedule-form.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create Schedule')

@section('content')
<style>
.sf-card{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:.75rem;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--shadow-sm);}
.sf-controls{display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1.5rem;}
.sf-control-group{display:flex;flex-direction:column;gap:.4rem;}
.sf-control-group label{font-size:.8rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;}
.sf-select{padding:.6rem .9rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.875rem;min-width:160px;cursor:pointer;}
.sf-select:focus{outline:none;border-color:var(--green-primary);box-shadow:0 0 0 3px rgba(45,122,80,.12);}
.sf-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.sf-table th{padding:.6rem .5rem;background:var(--bg-tertiary);border:1px solid var(--border-color);text-align:center;font-weight:700;color:var(--text-primary);font-size:.8rem;}
.sf-table .time-col{width:80px;padding:.5rem .4rem;background:var(--bg-tertiary);border:1px solid var(--border-color);text-align:center;font-size:.72rem;color:var(--text-secondary);font-weight:600;white-space:nowrap;}
.sf-table .break-row td{background:rgba(245,158,11,.07);border:1px solid var(--border-color);padding:.45rem;text-align:center;font-size:.72rem;color:#92400e;font-weight:700;letter-spacing:.06em;}
.sf-cell{padding:.35rem;border:1px solid var(--border-color);vertical-align:top;min-width:130px;}
.sf-subject{width:100%;padding:.35rem .4rem;border:1px solid var(--border-color);border-radius:.25rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.75rem;margin-bottom:.3rem;text-transform:uppercase;box-sizing:border-box;}
.sf-subject:focus{outline:none;border-color:var(--green-primary);}
.sf-teacher{width:100%;padding:.3rem .35rem;border:1px solid var(--border-color);border-radius:.25rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.72rem;box-sizing:border-box;}
.sf-teacher:focus{outline:none;border-color:var(--green-primary);}
.sf-submit-btn{padding:.75rem 2rem;background:linear-gradient(135deg,var(--green-primary),#0d3d20);color:#fff;border:none;border-radius:.5rem;cursor:pointer;font-weight:600;font-size:.9rem;transition:all .2s;pointer-events:auto;position:relative;z-index:30;}
.sf-submit-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(45,122,80,.3);}
.sf-add-subject-btn{display:none;}/* removed ? kept for any future use */
.sf-conflict-warn{font-size:.67rem;color:#dc2626;margin-top:.2rem;display:none;line-height:1.3;}
.sf-teacher.sf-conflict{border-color:#dc2626 !important;background:rgba(220,38,38,.05) !important;}
.sf-shared-panel{margin-top:.3rem;border-top:1px dashed var(--border-color);padding-top:.28rem;}
.sf-shared-panel-title{font-size:.62rem;color:var(--text-secondary);font-weight:600;margin-bottom:.2rem;text-transform:uppercase;letter-spacing:.04em;}
.sf-shared-item{display:inline-block;font-size:.65rem;background:rgba(59,130,246,.1);color:#2563eb;border-radius:.2rem;padding:.1rem .38rem;margin:.1rem .1rem .1rem 0;cursor:pointer;border:1px solid rgba(59,130,246,.25);}
.sf-shared-item:hover{background:rgba(59,130,246,.22);}
</style>

<div class="header">
    <div class="header-left">
        <svg width="22" height="22" fill="none" stroke="#2d7a50" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <h1 class="page-title">Create Class Schedule</h1>
    </div>
    <div class="header-right">
    </div>
</div>

<form action="{{ route('admin.schedule.store') }}" method="POST" id="sfForm" novalidate>
    @csrf

    <!-- Controls -->
    <div class="sf-card">
        <div class="sf-controls">
            <div class="sf-control-group">
                <label>Grade Level</label>
                <select name="grade_level" id="sfGrade" class="sf-select" required onchange="sfUpdateSections()">
                    <option value="">Select Grade</option>
                    <option value="Grade 7">Grade 7</option>
                    <option value="Grade 8">Grade 8</option>
                    <option value="Grade 9">Grade 9</option>
                    <option value="Grade 10">Grade 10</option>
                </select>
            </div>
            <div class="sf-control-group">
                <label>Day of Week</label>
                <select name="day_of_week" id="sfDay" class="sf-select" required onchange="sfOnDayChange()">
                    <option value="">Select Day</option>
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                </select>
            </div>
            <div class="sf-control-group">
                <label>Schedule Date</label>
                <input type="date" name="schedule_date" id="sfDate" class="sf-select" required value="{{ old('schedule_date', now()->toDateString()) }}">
            </div>
        </div>
        <p style="font-size:.8rem;color:var(--text-secondary);margin:0;">Select grade, day, and schedule date, then fill subjects and teachers per section/time slot. Duplicate day + time + date + section (or double-booked teacher) is not allowed.</p>
        <div id="sfSlotAssistantStatus" style="margin-top:.75rem;"></div>
    </div>

    <!-- Grid -->
    <div class="sf-card" style="overflow-x:auto;" id="sfGridCard">
        <div style="position:relative;min-height:120px;" id="sfGridTableWrap">
            <div id="sfGridOverlay" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.04);display:flex;align-items:center;justify-content:center;z-index:10;border-radius:.75rem;backdrop-filter:blur(3px);">
                <div style="background:var(--bg-secondary);border:2px dashed var(--border-color);border-radius:.75rem;padding:2rem 3rem;text-align:center;box-shadow:var(--shadow-sm);">
                    <svg width="40" height="40" fill="none" stroke="#2d7a50" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto .75rem;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p style="font-size:1rem;font-weight:700;color:var(--text-primary);margin:0 0 .4rem 0;">Select a Grade Level First</p>
                    <p style="font-size:.82rem;color:var(--text-secondary);margin:0;">Choose a grade level above to unlock the schedule grid.</p>
                </div>
            </div>
        <table class="sf-table">
            <thead>
                <tr>
                    <th style="width:80px;">Time</th>
                    <th id="sfH0"><span class="sf-grade-badge" id="sfBadge0"></span><br>SECTION 1</th>
                    <th id="sfH1"><span class="sf-grade-badge" id="sfBadge1"></span><br>SECTION 2</th>
                    <th id="sfH2"><span class="sf-grade-badge" id="sfBadge2"></span><br>SECTION 3</th>
                    <th id="sfH3"><span class="sf-grade-badge" id="sfBadge3"></span><br>SECTION 4</th>
                    <th id="sfH4"><span class="sf-grade-badge" id="sfBadge4"></span><br>SECTION 5</th>
                </tr>

            </thead>
            @php
                $jhSubjectOptions = ['MAPEH','AP','COMP','ADV SCI','CLVE','MATHEMATICS','ADV MATH','FILIPINO','ENGLISH','SCIENCE','TLE'];
            @endphp
            <tbody id="sf-tbody-weekday">
                @include('partials.schedule-form-grid-rows', [
                    'scheduleFormRows' => $scheduleFormRows,
                    'allTeachersForDropdown' => $allTeachersForDropdown,
                    'subjectOptions' => $jhSubjectOptions,
                    'sectionCount' => 5,
                ])
            </tbody>
            <tbody id="sf-tbody-tuesday" style="display:none;">
                @include('partials.schedule-form-grid-rows', [
                    'scheduleFormRows' => $scheduleFormRowsTuesday ?? [],
                    'allTeachersForDropdown' => $allTeachersForDropdown,
                    'subjectOptions' => $jhSubjectOptions,
                    'sectionCount' => 5,
                ])
            </tbody>
        </table>
        </div>
    </div>

    <!-- Submit -->
    <div style="display:flex;justify-content:flex-end;gap:1rem;">
        <a href="{{ route('admin.class-schedule') }}" style="padding:.75rem 1.5rem;border:1px solid var(--border-color);border-radius:.5rem;background:var(--bg-secondary);color:var(--text-primary);text-decoration:none;font-weight:500;font-size:.9rem;">Cancel</a>
        <button type="button" class="sf-submit-btn" id="sfSubmitBtn">Save Schedule</button>
    </div>
</form>

<script src="{{ asset('js/schedule-form-teacher-filter.js') }}"></script>
<script id="sf-jh-teacher-subjects" type="application/json">{!! json_encode($teacherSubjects ?? []) !!}</script>
<script id="sf-jh-teachers-by-grade" type="application/json">{!! json_encode($teachersByGrade ?? []) !!}</script>
<script id="sf-jh-teachers-by-grade-subject" type="application/json">{!! json_encode($teachersByGradeAndSubject ?? []) !!}</script>
<script id="sf-jh-all-teachers" type="application/json">{!! json_encode($allTeachersForDropdown) !!}</script>
<script id="sf-jh-teacher-conflicts" type="application/json">{!! json_encode($teacherConflicts ?? []) !!}</script>
<script id="sf-jh-shared-teachers" type="application/json">{!! json_encode($sharedTeachers ?? []) !!}</script>
<script id="sf-jh-teachers-by-subject" type="application/json">{!! json_encode($teachersBySubject ?? []) !!}</script>
<script id="sf-jh-unavailable-faculty" type="application/json">{!! json_encode($unavailableFaculty ?? []) !!}</script>
<script>
const JH_SECTIONS = {
    'Grade 7':  ['SERAPHIM','CHERUBIM','MICHAEL','RAPHAEL','GABRIEL'],
    'Grade 8':  ['THERESE','ALOYSIUS','AGNES','JOHN','GORETTI'],
    'Grade 9':  ['CHARTRES','PIAT','FATIMA','CARMEL','LOURDES'],
    'Grade 10': ['PAUL','PLC','MBF','MICHEAU','MARIA'],
};

function sfOnDayChange() {
    const day = (document.getElementById('sfDay') || {}).value || '';
    const isTuesday = day === 'Tuesday';
    const weekday = document.getElementById('sf-tbody-weekday');
    const tuesday = document.getElementById('sf-tbody-tuesday');
    if (weekday) weekday.style.display = isTuesday ? 'none' : '';
    if (tuesday) tuesday.style.display = isTuesday ? '' : 'none';
    [weekday, tuesday].forEach(function (tbody) {
        if (!tbody) return;
        const active = (isTuesday && tbody === tuesday) || (!isTuesday && tbody === weekday);
        tbody.querySelectorAll('input, select, textarea').forEach(function (el) {
            el.disabled = !active;
        });
    });
    document.querySelectorAll('.sf-teacher').forEach(function (s) {
        if (s.value) checkConflict(s);
    });
    if (window.ScheduleFormSubjectOptions && window.JH_SUBJECT_OPTS_CFG) {
        ScheduleFormSubjectOptions.refresh(JH_SUBJECT_OPTS_CFG);
    }
}

function sfUpdateSections() {
    const grade = document.getElementById('sfGrade').value;
    const secs  = JH_SECTIONS[grade] || ['SECTION 1','SECTION 2','SECTION 3','SECTION 4','SECTION 5'];
    for (let i = 0; i < 5; i++) {
        const badge = document.getElementById('sfBadge' + i);
        const hdr   = document.getElementById('sfH' + i);
        if (badge) badge.textContent = grade || '?';
        if (hdr) {
            hdr.innerHTML = '<span class="sf-grade-badge" id="sfBadge' + i + '">' + (grade || '?') + '</span><br>' + secs[i];
        }
        // Update hidden section data on cells (store via data-section on inputs)
        document.querySelectorAll('[name^="slots["][name$="[' + i + '][subject]"]').forEach(inp => {
            inp.dataset.section = secs[i];
        });
    }
    sfFilterTeachersByGrade(grade);

    // Toggle grid overlay and submit button based on grade selection
    const overlay = document.getElementById('sfGridOverlay');
    const submitBtn = document.getElementById('sfSubmitBtn');
    if (overlay) overlay.style.display = grade ? 'none' : 'flex';
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = grade ? '1' : '0.7';
        submitBtn.style.cursor = 'pointer';
    }
    if (window.ScheduleFormSubjectOptions && window.JH_SUBJECT_OPTS_CFG) {
        ScheduleFormSubjectOptions.refresh(JH_SUBJECT_OPTS_CFG);
    }
}

// -- Grade ? teacher filter -------------------------------------------------
var SF_JH_TEACHERS_BY_GRADE = JSON.parse(document.getElementById('sf-jh-teachers-by-grade')?.textContent || '{}');
var SF_JH_TEACHERS_BY_GRADE_SUBJECT = JSON.parse(document.getElementById('sf-jh-teachers-by-grade-subject')?.textContent || '{}');
var SF_JH_ALL_TEACHERS = JSON.parse(document.getElementById('sf-jh-all-teachers')?.textContent || '[]');
var SF_JH_TEACHERS_BY_SUBJECT = JSON.parse(document.getElementById('sf-jh-teachers-by-subject')?.textContent || '{}');
var SF_JH_UNAVAILABLE_FACULTY = JSON.parse(document.getElementById('sf-jh-unavailable-faculty')?.textContent || '{}');

function sfRebuildTeacherSel(teacherSel) {
    if (!window.ScheduleFormTeacherFilter) {
        return;
    }
    ScheduleFormTeacherFilter.rebuild(teacherSel, {
        getGrade: function () {
            var g = document.getElementById('sfGrade');
            return g ? g.value : '';
        },
        byGradeSubject: SF_JH_TEACHERS_BY_GRADE_SUBJECT,
        aliases: null,
        allTeachers: SF_JH_ALL_TEACHERS,
        unavailable: SF_JH_UNAVAILABLE_FACULTY,
        placeholder: '-- Teacher --',
    });
}

function sfFilterTeachersByGrade(grade) {
    document.querySelectorAll('.sf-teacher').forEach(function(sel) {
        delete sel.dataset.lastTeacher;
        sfRebuildTeacherSel(sel);
    });
}

// When subject changes in any cell, refilter that cell's teacher dropdown
document.addEventListener('change', function(e) {
        if (e.target.classList.contains('sf-subject')) {
        var row = e.target.closest('.sf-subject-row');
        var teacherSel = row ? row.querySelector('.sf-teacher') : null;
        if (teacherSel) {
            delete teacherSel.dataset.lastTeacher;
            sfRebuildTeacherSel(teacherSel);
        }
        var cell = e.target.closest('.sf-cell');
        if (cell) sfCheckCellDuplicates(cell);
    }
});

function sfToast(msg, type) {
    if (window.spupToast) {
        (type === 'error' ? window.spupToast.error : window.spupToast.warning)(msg);
    } else {
        alert(msg);
    }
}

function sfActiveTbody() {
    const day = (document.getElementById('sfDay') || {}).value || '';
    const isTuesday = day === 'Tuesday';
    return document.getElementById(isTuesday ? 'sf-tbody-tuesday' : 'sf-tbody-weekday');
}

function sfValidate() {
    const grade = document.getElementById('sfGrade').value;
    const day   = document.getElementById('sfDay').value;
    const dateVal = (document.getElementById('sfDate') || {}).value || '';
    if (!grade) { sfToast('Please select a Grade Level first.', 'error'); return false; }
    if (!day)   { sfToast('Please select a Day of Week first.', 'error'); return false; }
    if (!dateVal) {
        sfToast('Please select a Schedule Date. Duplicates are checked by day, time, and date.', 'error');
        return false;
    }
    const tbody = sfActiveTbody();
    const rows = tbody ? tbody.querySelectorAll('.sf-subject-row') : [];
    for (const row of rows) {
        const subj  = row.querySelector('.sf-subject');
        const teach = row.querySelector('.sf-teacher');
        if (subj && teach && subj.value && !teach.value) {
            sfToast('Cannot save: every subject must have a teacher assigned. Please select a teacher or clear the subject field.', 'error');
            return false;
        }
    }
    if (typeof window.sfDuplicateSubjectTeacherInCell === 'function' && tbody) {
        let hasCellDup = false;
        tbody.querySelectorAll('.sf-cell').forEach(function(cell) {
            if (window.sfDuplicateSubjectTeacherInCell(cell)) hasCellDup = true;
        });
        if (hasCellDup) {
            sfToast('Cannot save: the same subject and teacher cannot be assigned twice in one section slot.', 'error');
            return false;
        }
    }
    const gridCard = document.getElementById('sfGridCard');
    const warnScope = gridCard || document;
    const warnEls = warnScope.querySelectorAll('.sf-conflict-warn');
    for (var w of warnEls) {
        if (w.offsetParent !== null && w.textContent.trim()) {
            sfToast('Cannot save: ' + w.textContent.trim(), 'error');
            return false;
        }
    }
    const secs = JH_SECTIONS[grade] || [];
    let inFormDup = false;
    let hasEntry = false;
    if (tbody && typeof window.sfGetCellAssignmentPairs === 'function') {
        const seenTeachers = {};
        const seenSections = {};
        tbody.querySelectorAll('.sf-cell').forEach(function(cell) {
            if (inFormDup) return;
            const sub = cell.querySelector('.sf-subject');
            if (!sub || !sub.name) return;
            const m = sub.name.match(/slots\[([^\]]+)\]\[([^\]]+)\]/);
            if (!m) return;
            const timeKey = m[1];
            const idx = parseInt(m[2], 10);
            const secName = secs[idx] || ('SECTION ' + (idx + 1));
            window.sfGetCellAssignmentPairs(cell).forEach(function(p) {
                if (!p.subject || !p.facultyId) return;
                hasEntry = true;
                const tKey = p.facultyId + '|' + day + '|' + timeKey + '|' + dateVal;
                if (seenTeachers[tKey]) {
                    inFormDup = true;
                    sfToast('Duplicate: the same teacher cannot teach two sections at the same time on this date.', 'error');
                    return;
                }
                seenTeachers[tKey] = true;
                const sKey = grade + '|' + secName + '|' + day + '|' + timeKey + '|' + dateVal;
                if (seenSections[sKey]) {
                    inFormDup = true;
                    sfToast('Duplicate: ' + secName + ' already has an assignment for this time on the selected date.', 'error');
                    return;
                }
                seenSections[sKey] = true;
            });
        });
    }
    if (inFormDup) return false;
    if (!hasEntry) {
        sfToast('Please fill at least one time slot with both a subject and a teacher before submitting.', 'error');
        return false;
    }
    return true;
}

function sfShowConflictToasts(messages) {
    const list = Array.isArray(messages) ? messages : [String(messages)];
    if (!window.spupToast) {
        alert(list.join('\n'));
        return;
    }
    list.forEach(function (msg, i) {
        if (!msg) return;
        setTimeout(function () {
            window.spupToast.error(msg, 9000);
        }, i * 350);
    });
}

window.sfSubmitScheduleForm = function (clickedBtn) {
    const form = document.getElementById('sfForm');
    if (!form || !sfValidate()) return;
    const btn = clickedBtn || document.getElementById('sfSubmitBtn');
    const checkUrl = @json(route('admin.schedules.check-grid'));
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (btn) {
        if (!btn.dataset.sfOrigLabel) btn.dataset.sfOrigLabel = btn.textContent.trim();
        btn.disabled = false;
        btn.textContent = 'Checking…';
    }
    fetch(checkUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: new FormData(form),
    })
        .then(function (res) { return res.text().then(function (text) {
            let data = {};
            try { data = text ? JSON.parse(text) : {}; } catch (e) { data = {}; }
            return { ok: res.ok, data: data };
        }); })
        .then(function (result) {
            let conflicts = (result.data && result.data.conflicts) || [];
            if (result.data && result.data.errors) {
                Object.keys(result.data.errors).forEach(function (k) {
                    (result.data.errors[k] || []).forEach(function (m) { conflicts.push(m); });
                });
            }
            if (conflicts.length) {
                if (btn) btn.textContent = btn.dataset.sfOrigLabel || 'Save Schedule';
                sfShowConflictToasts(conflicts);
                return;
            }
            if (!result.ok && !(result.data && result.data.ok)) {
                if (btn) btn.textContent = btn.dataset.sfOrigLabel || 'Save Schedule';
                sfShowConflictToasts([(result.data && result.data.message) || 'Could not verify schedule. Please review your entries.']);
                return;
            }
            if (btn) btn.textContent = 'Saving…';
            form.submit();
        })
        .catch(function () {
            if (btn) btn.textContent = 'Saving…';
            form.submit();
        });
};

(function () {
    const form = document.getElementById('sfForm');
    const btn = document.getElementById('sfSubmitBtn');
    if (btn) {
        btn.addEventListener('click', function () { window.sfSubmitScheduleForm(btn); });
    }
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            window.sfSubmitScheduleForm();
        });
    }
})();

// Init
sfUpdateSections();

// -- Conflict detection + Shared Teachers panel --------------------------
(function () {
    var SF_JH_TEACHER_CONFLICTS = JSON.parse(document.getElementById('sf-jh-teacher-conflicts')?.textContent || '{}');
    var SF_JH_SHARED_TEACHERS   = JSON.parse(document.getElementById('sf-jh-shared-teachers')?.textContent || '[]');
    var sharedIdSet = new Set(SF_JH_SHARED_TEACHERS.map(function(t){ return String(t.faculty_id || ''); }).filter(Boolean));

    // slotKey "0745_0845" ? "07:45"
    function slotKeyToStart(key) {
        var s = (key || '').split('_')[0];
        return s.length === 4 ? s.slice(0,2) + ':' + s.slice(2,4) : '';
    }

    function sfGetCellAssignmentPairs(cell) {
        var pairs = [];
        var rows = cell.querySelectorAll('.sf-subject-row');
        if (rows.length) {
            rows.forEach(function(row) {
                var sub = row.querySelector('.sf-subject');
                var teach = row.querySelector('.sf-teacher');
                pairs.push({
                    subject: sub ? sub.value.trim() : '',
                    facultyId: teach ? teach.value : ''
                });
            });
        } else {
            var subs = cell.querySelectorAll('.sf-subject');
            var teaches = cell.querySelectorAll('.sf-teacher');
            for (var i = 0; i < subs.length; i++) {
                pairs.push({
                    subject: (subs[i].value || '').trim(),
                    facultyId: teaches[i] ? teaches[i].value : ''
                });
            }
        }
        return pairs;
    }

    function sfDuplicateSubjectTeacherInCell(cell) {
        var seen = {};
        var pairs = sfGetCellAssignmentPairs(cell);
        for (var i = 0; i < pairs.length; i++) {
            var p = pairs[i];
            if (!p.subject || !p.facultyId) continue;
            var key = p.subject.toLowerCase() + '|' + p.facultyId;
            if (seen[key]) {
                return 'Duplicate: same subject and teacher already assigned in this section slot.';
            }
            seen[key] = true;
        }
        return null;
    }

    function sfCheckCellDuplicates(cell) {
        var dup = sfDuplicateSubjectTeacherInCell(cell);
        cell.querySelectorAll('.sf-teacher').forEach(function(teach) {
            var warnEl = teach.parentNode.querySelector('.sf-conflict-warn');
            if (dup) {
                teach.classList.add('sf-conflict');
                if (warnEl) { warnEl.textContent = dup; warnEl.style.display = 'block'; }
            } else if (!teach.classList.contains('sf-conflict') || (warnEl && warnEl.textContent.indexOf('Duplicate:') === 0)) {
                if (warnEl && warnEl.textContent.indexOf('Duplicate:') === 0) {
                    warnEl.textContent = '';
                    warnEl.style.display = 'none';
                }
                if (teach.value) checkConflict(teach);
                else {
                    teach.classList.remove('sf-conflict');
                    if (warnEl) { warnEl.textContent = ''; warnEl.style.display = 'none'; }
                }
            }
        });
        return dup;
    }

    function checkConflict(teacherSel) {
        var teacherId = teacherSel.value;
        var warnEl = teacherSel.parentNode.querySelector('.sf-conflict-warn');
        if (!teacherId) {
            teacherSel.classList.remove('sf-conflict');
            if (warnEl) { warnEl.textContent = ''; warnEl.style.display = 'none'; }
            return;
        }
        var subjectRow = teacherSel.closest('.sf-subject-row');
        var subSel  = subjectRow ? subjectRow.querySelector('.sf-subject') : null;
        var slotKey = subSel ? (subSel.name.match(/slots\[([^\]]+)\]/)?.[1] || '') : '';
        var startTime = slotKeyToStart(slotKey);
        var day = (document.getElementById('sfDay') || {}).value || '';

        var conflictMsg = null;
        var cell = teacherSel.closest('.sf-cell');

        // ? Same subject + teacher twice in one section/time cell
        if (cell) {
            conflictMsg = sfDuplicateSubjectTeacherInCell(cell);
        }

        // ? Same teacher in another section of the same time row
        if (!conflictMsg) {
            var tr = teacherSel.closest('tr');
            if (tr) {
                tr.querySelectorAll('.sf-teacher').forEach(function(other) {
                    if (other === teacherSel) return;
                    if (other.value && other.value === teacherId) {
                        conflictMsg = '\u26a0 Already assigned to another section at this time';
                    }
                });
            }
        }

        // ? Teacher already has an approved schedule for that day + time
        if (!conflictMsg && day && startTime) {
            var existing = SF_JH_TEACHER_CONFLICTS[String(teacherId)] || [];
            for (var i = 0; i < existing.length; i++) {
                var slot = existing[i];
                if ((slot.day || '').toLowerCase() === day.toLowerCase() && slot.start === startTime) {
                    conflictMsg = '\u26a0 Conflict: already scheduled at ' + startTime + ' ' + day + (slot.section ? ' (' + slot.section + ')' : '');
                    break;
                }
            }
        }

        if (conflictMsg) {
            teacherSel.classList.add('sf-conflict');
            if (warnEl) { warnEl.textContent = conflictMsg; warnEl.style.display = 'block'; }
        } else {
            teacherSel.classList.remove('sf-conflict');
            if (warnEl) { warnEl.textContent = ''; warnEl.style.display = 'none'; }
        }
    }

    // Mark shared teachers in a select with "(Shared)"
    function markSharedInSel(sel) {
        Array.from(sel.options).forEach(function(opt) {
            if (sharedIdSet.has(String(opt.value)) && opt.textContent.indexOf('(Shared)') < 0) {
                opt.textContent += ' (Shared)';
            }
        });
    }

    // Patch sfRebuildTeacherSel to re-mark shared after rebuild
    var _orig = window.sfRebuildTeacherSel;
    if (_orig) {
        window.sfRebuildTeacherSel = function(sel) {
            _orig(sel);
            markSharedInSel(sel);
        };
    }

    // Initialize cells
    document.querySelectorAll('.sf-cell').forEach(function(cell) {
        var origSubSel = cell.querySelector('.sf-subject');
        var origTeach  = cell.querySelector('.sf-teacher');
        if (!origSubSel) return;

        // Wrap original pair in sf-subject-row
        var origWrap = document.createElement('div');
        origWrap.className = 'sf-subject-row';
        origWrap.style.cssText = 'margin-bottom:.3rem;';
        cell.insertBefore(origWrap, origSubSel);
        origWrap.appendChild(origSubSel);
        if (origTeach) origWrap.appendChild(origTeach);

        // Conflict warning
        var warnDiv = document.createElement('div');
        warnDiv.className = 'sf-conflict-warn';
        origWrap.appendChild(warnDiv);

        // Mark shared teachers in the dropdown
        if (origTeach) markSharedInSel(origTeach);

        // Shared teachers panel (only if any active shared teachers exist)
        if (SF_JH_SHARED_TEACHERS.length > 0) {
            var panel = document.createElement('div');
            panel.className = 'sf-shared-panel';

            var title = document.createElement('div');
            title.className = 'sf-shared-panel-title';
            title.textContent = 'Shared Teachers:';
            panel.appendChild(title);

            SF_JH_SHARED_TEACHERS.forEach(function(st) {
                // Parse subjects stored as JSON string or array
                var stSubjects = [];
                if (st.subjects) {
                    try { stSubjects = typeof st.subjects === 'string' ? JSON.parse(st.subjects) : st.subjects; } catch(e) {}
                }
                stSubjects = stSubjects.map(function(s){ return s.trim().toUpperCase(); });

                var chip = document.createElement('span');
                chip.className = 'sf-shared-item';
                chip.textContent = st.teacher_name;
                chip.title = (st.department || 'Shared') + ' ? click to assign';
                chip.dataset.stSubjects = JSON.stringify(stSubjects);
                // Hidden by default; shown when subject matches
                chip.style.display = 'none';
                chip.addEventListener('click', function() {
                    if (!origTeach) return;
                    // Try to find matching option by faculty_id
                    var opts = Array.from(origTeach.options);
                    var match = opts.find(function(o){ return String(o.value) === String(st.faculty_id); });
                    if (match) {
                        origTeach.value = match.value;
                    } else if (st.faculty_id) {
                        var opt = document.createElement('option');
                        opt.value = st.faculty_id;
                        opt.textContent = st.teacher_name + ' (Shared)';
                        origTeach.appendChild(opt);
                        origTeach.value = String(st.faculty_id);
                    }
                    origTeach.dispatchEvent(new Event('change', {bubbles:true}));
                });
                panel.appendChild(chip);
            });
            cell.appendChild(panel);

            // Function to refresh chip visibility based on selected subject
            function refreshSharedChips() {
                var selSubject = origSubSel ? origSubSel.value.trim().toUpperCase() : '';
                var anyVisible = false;
                panel.querySelectorAll('.sf-shared-item').forEach(function(chip) {
                    var chipSubjects = [];
                    try { chipSubjects = JSON.parse(chip.dataset.stSubjects || '[]'); } catch(e){}
                    var match = selSubject && chipSubjects.length > 0 && chipSubjects.some(function(s){ return s === selSubject; });
                    chip.style.display = match ? '' : 'none';
                    if (match) anyVisible = true;
                });
                // Hide panel title when no chips are visible
                title.style.display = anyVisible ? '' : 'none';
            }
            // Initial state
            refreshSharedChips();
            // Update on subject change
            if (origSubSel) {
                origSubSel.addEventListener('change', refreshSharedChips);
            }
        }

        // Teacher change ? conflict check
        if (origTeach) {
            origTeach.addEventListener('change', function() {
                checkConflict(origTeach);
                // Re-check siblings in same row
                var tr = origTeach.closest('tr');
                if (tr) tr.querySelectorAll('.sf-teacher').forEach(function(s){ if (s !== origTeach) checkConflict(s); });
            });
        }
    });

    sfOnDayChange();

    window.sfDuplicateSubjectTeacherInCell = sfDuplicateSubjectTeacherInCell;
    window.sfGetCellAssignmentPairs = sfGetCellAssignmentPairs;
}());
</script>
<script>
window.SF_SLOT_ASSISTANT = {
    apiUrl: @json(route('admin.dss.assess-slot')),
    schoolLevel: 'junior_high'
};
</script>
<script src="{{ asset('js/schedule-slot-assistant.js') }}"></script>
<script src="{{ asset('js/schedule-form-subject-options.js') }}?v={{ @filemtime(public_path('js/schedule-form-subject-options.js')) }}"></script>
<script>
window.JH_SUBJECT_OPTS = @json($jhSubjectOptions);
window.JH_SUBJECT_OPTS_CFG = {
    formId: 'sfForm',
    getActiveRoot: function () {
        return typeof sfActiveTbody === 'function' ? sfActiveTbody() : null;
    },
    getSubjects: function () {
        return window.JH_SUBJECT_OPTS || [];
    },
};
if (window.ScheduleFormSubjectOptions) {
    ScheduleFormSubjectOptions.init(window.JH_SUBJECT_OPTS_CFG);
}
</script>
@endsection