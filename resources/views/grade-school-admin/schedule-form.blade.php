{{-- resources/views/grade-school-admin/schedule-form.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Create Class Schedule')

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
.sf-cell{padding:.35rem;border:1px solid var(--border-color);vertical-align:top;min-width:160px;}
.sf-subject{width:100%;padding:.35rem .4rem;border:1px solid var(--border-color);border-radius:.25rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.75rem;margin-bottom:.3rem;box-sizing:border-box;}
.sf-subject:focus{outline:none;border-color:var(--green-primary);}
.sf-teacher{width:100%;padding:.3rem .35rem;border:1px solid var(--border-color);border-radius:.25rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.72rem;box-sizing:border-box;}
.sf-teacher:focus{outline:none;border-color:var(--green-primary);}
.sf-submit-btn{padding:.75rem 2rem;background:linear-gradient(135deg,var(--green-primary),#0d3d20);color:#fff;border:none;border-radius:.5rem;cursor:pointer;font-weight:600;font-size:.9rem;transition:all .2s;}
.sf-submit-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(45,122,80,.3);}
.sf-submit-btn:disabled{opacity:.55;cursor:not-allowed;transform:none;}
.sf-cancel-link{padding:.75rem 1.5rem;background:var(--bg-secondary);color:var(--text-primary);border:1px solid var(--border-color);border-radius:.5rem;font-weight:600;font-size:.875rem;text-decoration:none;display:inline-block;transition:background .2s,border-color .2s;}
.sf-cancel-link:hover{background:var(--bg-tertiary);border-color:var(--green-primary);}
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
    <div class="header-right"></div>
</div>

@if($errors->any())
<div style="background:rgba(239,68,68,.1);border:1px solid #ef4444;color:#b91c1c;padding:1rem 1.25rem;border-radius:.5rem;margin-bottom:1.5rem;font-size:.875rem;">
    <strong>Please fix the following:</strong>
    <ul style="margin:.5rem 0 0 1rem;padding:0;">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
    </ul>
</div>
@endif

<form action="{{ route('grade-school-admin.schedule.store') }}" method="POST" id="scheduleGridForm">
    @csrf

    <!-- Controls -->
    <div class="sf-card">
        <div class="sf-controls">
            <div class="sf-control-group">
                <label for="grade_level">Grade Level</label>
                <select name="grade_level" id="grade_level" class="sf-select" required>
                    <option value="">Select Grade</option>
                    @foreach(['Kinder 2','Kinder 1','Nursery','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g)
                        <option value="{{ $g }}" {{ old('grade_level') === $g ? 'selected' : '' }}>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sf-control-group">
                <label for="day_of_week">Day of Week</label>
                <select name="day_of_week" id="day_of_week" class="sf-select" required>
                    <option value="">Select Day</option>
                    @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday'] as $d)
                        <option value="{{ $d }}" {{ old('day_of_week') === $d ? 'selected' : '' }}>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sf-control-group">
                <label for="schedule_date">Schedule Date</label>
                <input type="date" name="schedule_date" id="schedule_date" class="sf-select" value="{{ old('schedule_date') }}">
            </div>
        </div>
        <p style="font-size:.8rem;color:var(--text-secondary);margin:0;">Select a grade level and day, then fill in subjects and assign teachers per section/time slot. Sections update automatically per grade.</p>
        <div id="sfSlotAssistantStatus" style="margin-top:.75rem;"></div>
    </div>

    <!-- Kinder: MonFri activity only -->
    <div class="sf-card" id="gsKinderPanel" style="display:none;">
        <h3 style="margin:0 0 1rem;font-size:1rem;font-weight:800;color:var(--green-primary);">Kinder Weekly Schedule</h3>
        <p style="font-size:.8rem;color:var(--text-secondary);margin:0 0 1rem;">Choose grade, room/section, and teacher. Assign one activity per weekday from the Subjects list (no duplicate in the same week).</p>
        <div class="sf-controls" style="margin-bottom:1rem;">
            <div class="sf-control-group">
                <label for="kinder_section_name">Room / Section</label>
                <select name="section_name" id="kinder_section_name" class="sf-select" data-kinder-required="1">
                    <option value="">Select section</option>
                </select>
            </div>
            <div class="sf-control-group">
                <label for="kinder_faculty_id">Teacher</label>
                <select name="faculty_id" id="kinder_faculty_id" class="sf-select" data-kinder-required="1">
                    <option value="">Select teacher</option>
                </select>
            </div>
        </div>
        @include('partials.kinder-weekly-activity-table', [
            'gradeTitle' => null,
            'weeklyActivity' => old('activity', \App\Support\KinderScheduleSupport::WEEKLY_ACTIVITY_BY_DAY),
        ])
        <div style="display:flex;gap:1rem;padding-top:1.25rem;border-top:1px solid var(--border-color);margin-top:1rem;">
            <button type="submit" class="sf-submit-btn" id="sfKinderSubmitBtn">Save Kinder Schedule</button>
            <a href="{{ route('grade-school-admin.class-schedule') }}" class="sf-cancel-link">Cancel</a>
        </div>
    </div>

    <!-- Grid -->
    <div class="sf-card" style="overflow-x:auto;position:relative;" id="gsGridCard">
        <div id="gsGridOverlay" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.04);display:flex;align-items:center;justify-content:center;z-index:10;border-radius:.75rem;backdrop-filter:blur(3px);">
            <div style="background:var(--bg-secondary);border:2px dashed var(--border-color);border-radius:.75rem;padding:2rem 3rem;text-align:center;box-shadow:var(--shadow-sm);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--green-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin:0 auto 1rem;display:block;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <p style="font-size:1rem;font-weight:700;color:var(--text-primary);margin:0 0 .4rem;">Select a Grade Level First</p>
                <p style="font-size:.82rem;color:var(--text-secondary);margin:0;">Choose a grade level above to unlock the schedule grid.</p>
            </div>
        </div>
        <table class="sf-table">
            <thead>
                <tr>
                    <th style="width:80px;">Time</th>
                    <th id="gs-sec-th-0">STEPHEN</th>
                    <th id="gs-sec-th-1">PETER</th>
                    <th id="gs-sec-th-2">ST. PAUL</th>
                </tr>
            </thead>
            {{-- Hidden inputs carry the actual section names to the server --}}
            <input type="hidden" name="section_names[0]" id="gs-sec-name-0" value="STEPHEN">
            <input type="hidden" name="section_names[1]" id="gs-sec-name-1" value="PETER">
            <input type="hidden" name="section_names[2]" id="gs-sec-name-2" value="ST. PAUL">
            <tbody>
                @php
                    $gsSubjectOptions = ['SCIENCE','COMPUTER','READING AND LITERACY','LANGUAGE','CLVE','MAKABANSA','MATHEMATICS','ENGLISH','FILIPINO','HELE','AP','MAPEH'];
                @endphp
                @include('partials.schedule-form-grid-rows', [
                    'scheduleFormRows' => $scheduleFormRows,
                    'allTeachersForDropdown' => $allTeachersForDropdown,
                    'subjectOptions' => $gsSubjectOptions,
                    'sectionCount' => 3,
                ])
            </tbody>
        </table>

        <div style="display:flex;gap:1rem;padding:1.25rem;border-top:1px solid var(--border-color);">
            <button type="submit" class="sf-submit-btn" id="sfSubmitBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:.4rem;"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v14a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2"/><polyline points="17 21 17 13 7 13 7 21" stroke="currentColor" stroke-width="2"/><polyline points="7 3 7 8 15 8" stroke="currentColor" stroke-width="2"/></svg>
                Submit Schedule for Approval
            </button>
            <a href="{{ route('grade-school-admin.class-schedule') }}" class="sf-cancel-link">Cancel</a>
        </div>
    </div>

</form>

<script id="sf-gs-teacher-subjects" type="application/json">{!! json_encode($teacherSubjects ?? []) !!}</script>
<script id="sf-gs-teachers-by-grade" type="application/json">{!! json_encode($teachersByGrade ?? []) !!}</script>
<script id="sf-gs-teachers-by-grade-subject" type="application/json">{!! json_encode($teachersByGradeAndSubject ?? []) !!}</script>
<script id="sf-gs-all-teachers" type="application/json">{!! json_encode($allTeachersForDropdown) !!}</script>
<script id="sf-gs-teacher-conflicts" type="application/json">{!! json_encode($teacherConflicts ?? []) !!}</script>
<script id="sf-gs-shared-teachers" type="application/json">{!! json_encode($sharedTeachers ?? []) !!}</script>
<script id="sf-gs-teachers-by-subject" type="application/json">{!! json_encode($teachersBySubject ?? []) !!}</script>
<script id="sf-gs-unavailable-faculty" type="application/json">{!! json_encode($unavailableFaculty ?? []) !!}</script>
<script>
(function () {
    var gradeSelect = document.getElementById('grade_level');
    var daySelect   = document.getElementById('day_of_week');
    var kinderPanel = document.getElementById('gsKinderPanel');
    var gridCard    = document.getElementById('gsGridCard');
    var KINDER_GRADES = ['Kinder 2', 'Kinder 1', 'Nursery'];
    var KINDER_SECTIONS = @json(\App\Support\KinderScheduleSupport::SECTIONS_BY_GRADE);
    var badges      = document.querySelectorAll('table.sf-grid thead th .sf-grade-badge');

    function isKinderGrade(grade) {
        return KINDER_GRADES.indexOf(grade) >= 0;
    }

    function gsUpdateKinderSections(grade) {
        var sel = document.getElementById('kinder_section_name');
        if (!sel) return;
        var list = KINDER_SECTIONS[grade] || [];
        var current = sel.value;
        sel.innerHTML = '<option value="">Select section</option>';
        list.forEach(function (sec) {
            var opt = document.createElement('option');
            opt.value = sec;
            opt.textContent = sec;
            if (sec === current) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    // -- Data maps ------------------------------------------------------------
    var SF_GS_TEACHERS_BY_GRADE         = JSON.parse(document.getElementById('sf-gs-teachers-by-grade')?.textContent || '{}');
    var SF_GS_TEACHERS_BY_GRADE_SUBJECT = JSON.parse(document.getElementById('sf-gs-teachers-by-grade-subject')?.textContent || '{}');
    var SF_GS_ALL_TEACHERS              = JSON.parse(document.getElementById('sf-gs-all-teachers')?.textContent || '[]');
    var SF_GS_TEACHERS_BY_SUBJECT       = JSON.parse(document.getElementById('sf-gs-teachers-by-subject')?.textContent || '{}');
    var SF_GS_UNAVAILABLE_FACULTY       = JSON.parse(document.getElementById('sf-gs-unavailable-faculty')?.textContent || '{}');

    function gsRebuildKinderTeachers(grade) {
        var sel = document.getElementById('kinder_faculty_id');
        if (!sel) return;
        var current = sel.value || '';
        var allowedIds = (SF_GS_TEACHERS_BY_GRADE[grade] || []).map(String);
        sel.innerHTML = '<option value="">Select teacher</option>';
        var added = {};
        SF_GS_ALL_TEACHERS.forEach(function (t) {
            var id = String(t.id);
            if (!grade) return;
            if (allowedIds.length > 0 && !allowedIds.includes(id) && id !== String(current)) return;
            if (SF_GS_UNAVAILABLE_FACULTY[id] && id !== String(current)) return;
            var opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            if (id === String(current)) opt.selected = true;
            sel.appendChild(opt);
            added[id] = true;
        });
        if (current && !added[String(current)]) {
            var kept = SF_GS_ALL_TEACHERS.find(function (t) { return String(t.id) === String(current); });
            if (kept) {
                var opt = document.createElement('option');
                opt.value = kept.id;
                opt.textContent = kept.name;
                opt.selected = true;
                sel.appendChild(opt);
            }
        }
    }

    // Aliases: form display value ? possible keys in SF_GS_TEACHERS_BY_SUBJECT
    var GS_SUBJECT_ALIASES = {
        'CLVE':                 ['CLVE','CHRISTIAN LIVING EDUCATION','CHRISTIAN LIVING'],
        'AP':                   ['AP','ARALING PANLIPUNAN'],
        'COMPUTER':             ['COMPUTER','COMPUTER EDUCATION','ICT'],
        'HELE':                 ['HELE','HOME ECONOMICS AND LIVELIHOOD EDUCATION','HOME ECONOMICS','TECHNOLOGY AND LIVELIHOOD EDUCATION'],
        'READING AND LITERACY': ['READING AND LITERACY','READING'],
        'MAPEH':                ['MAPEH'],
        'MAKABANSA':            ['MAKABANSA'],
        'LANGUAGE':             ['LANGUAGE'],
        'SCIENCE':              ['SCIENCE'],
        'MATHEMATICS':          ['MATHEMATICS'],
        'ENGLISH':              ['ENGLISH'],
        'FILIPINO':             ['FILIPINO'],
    };

    // Resolve teacher IDs for a given subject display value
    function gsGetSubjectIds(subject) {
        var aliases = GS_SUBJECT_ALIASES[subject] || [subject];
        for (var i = 0; i < aliases.length; i++) {
            var ids = SF_GS_TEACHERS_BY_SUBJECT[aliases[i]];
            if (ids && ids.length > 0) return ids;
        }
        // Fuzzy fallback: partial match on subject key
        var matchedKey = Object.keys(SF_GS_TEACHERS_BY_SUBJECT).find(function(k) {
            return k.includes(subject) || subject.includes(k);
        });
        return matchedKey ? SF_GS_TEACHERS_BY_SUBJECT[matchedKey] : null;
    }

    // Collect all teacher IDs that have at least one faculty load assignment
    var _seenLoaded = {};
    var GS_ALL_LOADED_IDS = [];
    [SF_GS_TEACHERS_BY_GRADE, SF_GS_TEACHERS_BY_SUBJECT].forEach(function(map) {
        Object.values(map).forEach(function(ids) {
            ids.forEach(function(id) {
                var s = String(id);
                if (!_seenLoaded[s]) { _seenLoaded[s] = true; GS_ALL_LOADED_IDS.push(s); }
            });
        });
    });

    // -- Rebuild a single teacher <select> based on grade + paired subject input --
    function gsRebuildTeacherSel(teacherSel) {
        var grade = gradeSelect ? gradeSelect.value : '';
        // Support both dynamically-created .sf-subject-row and static .sf-cell wrappers
        var row = teacherSel.closest('.sf-subject-row') || teacherSel.closest('.sf-cell');
        var subjectInp = row ? row.querySelector('.sf-subject') : null;
        var subject = subjectInp ? subjectInp.value.trim().toUpperCase() : '';

        var allowedIds = null;
        var subjectSelected = subject !== '';
        if (subject) {
            // Filter strictly to teachers assigned to this subject
            var subjectIds = gsGetSubjectIds(subject);
            if (subjectIds && subjectIds.length > 0) {
                allowedIds = subjectIds.map(String);
                // Intersect with grade filter when both are selected
                if (grade) {
                    var gradeIds = (SF_GS_TEACHERS_BY_GRADE[grade] || []).map(String);
                    if (gradeIds.length > 0) {
                        var intersection = allowedIds.filter(function(id) { return gradeIds.includes(id); });
                        if (intersection.length > 0) allowedIds = intersection;
                        // Keep subject-only list if intersection is empty
                    }
                }
            } else {
                allowedIds = [];
            }
        } else if (grade) {
            allowedIds = (SF_GS_TEACHERS_BY_GRADE[grade] || []).map(String);
        }

        var currentVal = teacherSel.value || teacherSel.dataset.lastTeacher || '';
        teacherSel.innerHTML = '<option value="">-- Teacher --</option>';
        var added = {};
        SF_GS_ALL_TEACHERS.forEach(function(t) {
            if (!subjectSelected && !grade) return;
            var id = String(t.id);
            var include = false;
            if (allowedIds === null) {
                include = true;
            } else if (allowedIds.length === 0) {
                include = (id === String(currentVal));
            } else {
                include = allowedIds.includes(id);
            }
            if (include) {
                if (SF_GS_UNAVAILABLE_FACULTY[id] && id !== String(currentVal)) {
                    return;
                }
                var opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                if (id === String(currentVal)) opt.selected = true;
                teacherSel.appendChild(opt);
                added[id] = true;
            }
        });
        if (currentVal && !added[String(currentVal)]) {
            var kept = SF_GS_ALL_TEACHERS.find(function(t) { return String(t.id) === String(currentVal); });
            if (kept) {
                var opt = document.createElement('option');
                opt.value = kept.id;
                opt.textContent = kept.name;
                opt.selected = true;
                teacherSel.appendChild(opt);
            }
        }
        if (currentVal) teacherSel.value = String(currentVal);
        if (teacherSel.value) teacherSel.dataset.lastTeacher = teacherSel.value;
    }

    function gsFilterTeachersByGrade(grade) {
        document.querySelectorAll('.sf-teacher').forEach(function(sel) {
            delete sel.dataset.lastTeacher;
            gsRebuildTeacherSel(sel);
        });
    }

    // Keep gsFilterTeacherSel as a wrapper so existing wire-up code still works
    function gsFilterTeacherSel(subjectQuery, sel) {
        gsRebuildTeacherSel(sel);
    }

    function updateBadges() {
        var grade = gradeSelect.value || '';
        var kinder = isKinderGrade(grade);

        if (kinderPanel) kinderPanel.style.display = kinder ? 'block' : 'none';
        if (gridCard) gridCard.style.display = kinder ? 'none' : '';
        if (daySelect) daySelect.required = !kinder;
        document.querySelectorAll('[data-kinder-required]').forEach(function (el) {
            el.required = kinder;
            el.disabled = !kinder;
        });

        badges.forEach(function (b) {
            b.textContent = grade;
            b.style.display = grade && !kinder ? '' : 'none';
        });
        if (grade && !kinder) {
            gsUpdateSections(grade);
            gsFilterTeachersByGrade(grade);
        }
        if (kinder) {
            gsUpdateKinderSections(grade);
            gsRebuildKinderTeachers(grade);
        }

        var overlay = document.getElementById('gsGridOverlay');
        var submitBtn = document.getElementById('sfSubmitBtn');
        if (overlay) overlay.style.display = (grade && !kinder) ? 'none' : (kinder ? 'none' : 'flex');
        if (submitBtn) {
            submitBtn.disabled = !grade || kinder;
            submitBtn.style.opacity = (grade && !kinder) ? '1' : '0.45';
            submitBtn.style.cursor = (grade && !kinder) ? 'pointer' : 'not-allowed';
        }
    }

    gradeSelect.addEventListener('change', updateBadges);
    updateBadges();

    // Teacher?subjects map from faculty loads (kept for backward compat)
    var GS_TEACHER_SUBJECTS = JSON.parse(document.getElementById('sf-gs-teacher-subjects')?.textContent || '{}');

    function gsWireSubjectFilter(inp, sel) {
        if (!inp || !sel) return;
        inp.addEventListener('change', function() {
            delete sel.dataset.lastTeacher;
            gsFilterTeacherSel(inp.value, sel);
        });
    }
    var SF_GS_SUBJECTS = [
        'SCIENCE','COMPUTER','READING AND LITERACY','LANGUAGE',
        'CLVE','MAKABANSA','MATHEMATICS','ENGLISH','FILIPINO','HELE','AP','MAPEH'
    ];

    // -- Grade-specific sections & subjects --------------------------------
    var GS_GRADE_SECTIONS = {
        'Grade 1': ['STEPHEN', 'PETER', 'ST. PAUL'],
        'Grade 2': ['ST. LUKE', 'ST. MARK', 'ST. MATTHEW'],
        'Grade 3': ['ST. JOHN', 'ST. JAMES', 'ST. JOSEPH'],
        'Grade 4': ['ST. FRANCIS', 'ST. AQUINAS', 'ST. LORENZO'],
        'Grade 5': ['ST. MARGARETTE', 'ST. THERESE', 'ST. AGATHA'],
        'Grade 6': ['ST. MA. GORETTI', 'ST. CATHERINE', 'ST. CLAIRE'],
    };
    var GS_GRADE_SUBJECTS = {
        'Grade 1': ['SCIENCE','COMPUTER','READING AND LITERACY','LANGUAGE','CLVE','MAKABANSA','MATHEMATICS'],
        'Grade 2': ['MAKABANSA','ENGLISH','FILIPINO','SCIENCE','MATHEMATICS','CLVE','COMPUTER'],
        'Grade 3': ['FILIPINO','CLVE','MAKABANSA','SCIENCE','MATHEMATICS','READING AND LITERACY','ENGLISH','COMPUTER'],
        'Grade 4': ['MATHEMATICS','HELE','AP','MAPEH','ENGLISH','SCIENCE','CLVE','FILIPINO','COMPUTER'],
        'Grade 5': ['AP','FILIPINO','ENGLISH','SCIENCE','HELE','MATHEMATICS','MAPEH','COMPUTER','CLVE'],
        'Grade 6': ['MATHEMATICS','ENGLISH','SCIENCE','FILIPINO','CLVE','HELE','MAPEH','AP'],
    };

    /** Rebuild section column headers + hidden section_names inputs + subject dropdowns. */
    function gsUpdateSections(grade) {
        var sections = GS_GRADE_SECTIONS[grade] || ['STEPHEN','PETER','ST. PAUL'];
        var subjects = GS_GRADE_SUBJECTS[grade]  || SF_GS_SUBJECTS;

        // 1. Update <th> labels
        [0, 1, 2].forEach(function(i) {
            var th = document.getElementById('gs-sec-th-' + i);
            if (th) th.textContent = sections[i] || ('Section ' + (i + 1));
        });

        // 2. Update hidden section_name inputs
        [0, 1, 2].forEach(function(i) {
            var inp = document.getElementById('gs-sec-name-' + i);
            if (inp) inp.value = sections[i] || ('Section ' + (i + 1));
        });

        // 3. Rebuild all .sf-subject selects in the grid
        document.querySelectorAll('.sf-subject').forEach(function(sel) {
            var row = sel.closest('.sf-subject-row') || sel.closest('.sf-cell');
            var teach = row ? row.querySelector('.sf-teacher') : null;
            if (teach) delete teach.dataset.lastTeacher;
            var current = sel.value;
            sel.innerHTML = '<option value="">\u2014 Subject \u2014</option>';
            subjects.forEach(function(s) {
                var opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s;
                if (s === current) opt.selected = true;
                sel.appendChild(opt);
            });
            // If old value is no longer available, keep blank
            if (sel.value !== current) sel.value = '';
            sel.dispatchEvent(new Event('change'));
        });
    }

    // Helper: create a subject+teacher pair row
    function gsMakeSubjectRow(cell, subjectName, teacherSelectName) {
        var wrap = document.createElement('div');
        wrap.className = 'sf-subject-row';
        wrap.style.cssText = 'margin-bottom:.3rem;position:relative;';

        var inp = document.createElement('select');
        inp.name = subjectName;
        inp.className = 'sf-subject';
        var blankOpt = document.createElement('option');
        blankOpt.value = '';
        blankOpt.textContent = '\u2014 Subject \u2014';
        inp.appendChild(blankOpt);
        // Use grade-specific subjects if a grade is selected, else fall back to all
        var grade = gradeSelect ? gradeSelect.value : '';
        var currentSubjects = (grade && GS_GRADE_SUBJECTS[grade]) ? GS_GRADE_SUBJECTS[grade] : SF_GS_SUBJECTS;
        currentSubjects.forEach(function(s) {
            var o = document.createElement('option');
            o.value = s;
            o.textContent = s;
            inp.appendChild(o);
        });

        var origSel = cell.querySelector('.sf-teacher');
        var sel = origSel ? origSel.cloneNode(true) : document.createElement('select');
        sel.name = teacherSelectName;
        sel.className = 'sf-teacher';
        sel.selectedIndex = 0;

        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = '?';
        removeBtn.style.cssText = 'position:absolute;top:2px;right:2px;background:none;border:none;color:#b91c1c;font-size:.8rem;cursor:pointer;padding:0 2px;line-height:1;';
        removeBtn.title = 'Remove this subject';
        removeBtn.addEventListener('click', function () { wrap.remove(); });

        wrap.appendChild(removeBtn);
        wrap.appendChild(inp);
        wrap.appendChild(sel);
        gsWireSubjectFilter(inp, sel);
        inp.addEventListener('change', function() {
            var c = sel.closest('.sf-cell');
            if (c) gsCheckCellDuplicates(c);
        });
        sel.addEventListener('change', function() { gsCheckConflict(sel); });
        return wrap;
    }

    function gsCheckCellDuplicates(cell) {
        cell.querySelectorAll('.sf-teacher').forEach(function(teach) {
            gsCheckConflict(teach);
        });
    }

    // -- Conflict detection helpers ----------------------------------------
    var GS_TEACHER_CONFLICTS = JSON.parse(document.getElementById('sf-gs-teacher-conflicts')?.textContent || '{}');
    var GS_SHARED_TEACHERS   = JSON.parse(document.getElementById('sf-gs-shared-teachers')?.textContent || '[]');
    var gsSharedIdSet = new Set(GS_SHARED_TEACHERS.map(function(t){ return String(t.faculty_id || ''); }).filter(Boolean));

    function gsSlotKeyToStart(key) {
        var s = (key || '').split('_')[0];
        return s.length === 4 ? s.slice(0,2) + ':' + s.slice(2,4) : '';
    }

    function gsGetCellAssignmentPairs(cell) {
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

    function gsDuplicateSubjectTeacherInCell(cell) {
        var seen = {};
        var pairs = gsGetCellAssignmentPairs(cell);
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

    function gsCheckConflict(teacherSel) {
        var teacherId = teacherSel.value;
        var warnEl = teacherSel.parentNode.querySelector('.sf-conflict-warn');
        if (!teacherId) {
            teacherSel.classList.remove('sf-conflict');
            if (warnEl) { warnEl.textContent = ''; warnEl.style.display = 'none'; }
            return;
        }
        var subjectRow = teacherSel.closest('.sf-subject-row');
        var subInp = subjectRow ? subjectRow.querySelector('.sf-subject') : null;
        var slotKey = subInp ? (subInp.name.match(/slots\[([^\]]+)\]/)?.[1] || '') : '';
        var startTime = gsSlotKeyToStart(slotKey);
        var day = (document.getElementById('day_of_week') || {}).value || '';
        var conflictMsg = null;
        var cell = teacherSel.closest('.sf-cell');

        // ? Same subject + teacher twice in one section/time cell
        if (cell) {
            conflictMsg = gsDuplicateSubjectTeacherInCell(cell);
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
        // ? Existing approved schedule same day+time
        if (!conflictMsg && day && startTime) {
            var existing = GS_TEACHER_CONFLICTS[String(teacherId)] || [];
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

    function markSharedInSel(sel) {
        Array.from(sel.options).forEach(function(opt) {
            if (gsSharedIdSet.has(String(opt.value)) && opt.textContent.indexOf('(Shared)') < 0) {
                opt.textContent += ' (Shared)';
            }
        });
    }

    // Patch gsRebuildTeacherSel to re-mark shared after rebuild
    var _gsOrig = window.gsRebuildTeacherSel;
    if (_gsOrig) {
        window.gsRebuildTeacherSel = function(sel) {
            _gsOrig(sel);
            markSharedInSel(sel);
        };
    }

    // Re-check on day change
    var gsDayEl = document.getElementById('day_of_week');
    if (gsDayEl) gsDayEl.addEventListener('change', function() {
        document.querySelectorAll('.sf-teacher').forEach(function(s){ if (s.value) gsCheckConflict(s); });
    });

    document.querySelectorAll('.sf-cell').forEach(function (cell) {
        var origInp = cell.querySelector('.sf-subject');
        var origSel = cell.querySelector('.sf-teacher');
        if (!origInp) return;

        // Wrap original pair
        var origWrap = document.createElement('div');
        origWrap.className = 'sf-subject-row';
        origWrap.style.cssText = 'margin-bottom:.3rem;';
        gsWireSubjectFilter(origInp, origSel);
        cell.insertBefore(origWrap, origInp);
        origWrap.appendChild(origInp);
        if (origSel) origWrap.appendChild(origSel);

        // Extract slot key and section key from input name
        // e.g. slots[0745_0835][STEPHEN][subject]
        var m = origInp.name.match(/slots\[([^\]]+)\]\[([^\]]+)\]/);
        var slotKey = m?.[1] ?? 'slot';
        var secKey  = m?.[2] ?? 'sec';

        // Conflict warning div
        var warnDiv = document.createElement('div');
        warnDiv.className = 'sf-conflict-warn';
        origWrap.appendChild(warnDiv);

        // Mark shared teachers in the teacher dropdown
        if (origSel) markSharedInSel(origSel);

        // Shared teachers panel
        var GS_SHARED = JSON.parse(document.getElementById('sf-gs-shared-teachers')?.textContent || '[]');
        if (GS_SHARED.length > 0 && !cell._sharedPanelAdded) {
            var panel = document.createElement('div');
            panel.className = 'sf-shared-panel';
            var stitle = document.createElement('div');
            stitle.className = 'sf-shared-panel-title';
            stitle.textContent = 'Shared Teachers:';
            panel.appendChild(stitle);
            GS_SHARED.forEach(function(st) {
                // Parse subjects stored as JSON string or array
                var stSubjects = [];
                if (st.subjects) {
                    try { stSubjects = typeof st.subjects === 'string' ? JSON.parse(st.subjects) : st.subjects; } catch(e) {}
                }
                stSubjects = stSubjects.map(function(s){ return s.trim().toUpperCase(); });

                var chip = document.createElement('span');
                chip.className = 'sf-shared-item';
                chip.textContent = st.teacher_name;
                chip.title = (st.department || 'Shared') + '  click to assign';
                chip.dataset.stSubjects = JSON.stringify(stSubjects);
                chip.style.display = 'none'; // hidden until subject matches
                chip.addEventListener('click', function() {
                    if (!origSel) return;
                    var opts = Array.from(origSel.options);
                    var match = opts.find(function(o){ return String(o.value) === String(st.faculty_id); });
                    if (match) {
                        origSel.value = match.value;
                    } else if (st.faculty_id) {
                        var opt = document.createElement('option');
                        opt.value = st.faculty_id;
                        opt.textContent = st.teacher_name + ' (Shared)';
                        origSel.appendChild(opt);
                        origSel.value = String(st.faculty_id);
                    }
                    origSel.dispatchEvent(new Event('change', {bubbles:true}));
                });
                panel.appendChild(chip);
            });
            cell.appendChild(panel);
            cell._sharedPanelAdded = true;

            // Filter chips by selected subject
            function gsRefreshSharedChips() {
                var selSubject = origInp ? origInp.value.trim().toUpperCase() : '';
                var anyVisible = false;
                panel.querySelectorAll('.sf-shared-item').forEach(function(chip) {
                    var chipSubjects = [];
                    try { chipSubjects = JSON.parse(chip.dataset.stSubjects || '[]'); } catch(e) {}
                    var match = selSubject && chipSubjects.length > 0 && chipSubjects.some(function(s){ return s === selSubject; });
                    chip.style.display = match ? '' : 'none';
                    if (match) anyVisible = true;
                });
                stitle.style.display = anyVisible ? '' : 'none';
            }
            gsRefreshSharedChips();
            if (origInp) origInp.addEventListener('change', gsRefreshSharedChips);
            origInp.addEventListener('change', function() {
                var c = origInp.closest('.sf-cell');
                if (c) gsCheckCellDuplicates(c);
            });
        }

        // Teacher change ? conflict check
        if (origSel) {
            origSel.addEventListener('change', function() {
                gsCheckConflict(origSel);
                var tr = origSel.closest('tr');
                if (tr) tr.querySelectorAll('.sf-teacher').forEach(function(s){ if (s !== origSel) gsCheckConflict(s); });
            });
        }
    });

    function gsToast(msg) {
        if (window.spupToast) {
            window.spupToast.error(msg);
        } else {
            alert(msg);
        }
    }

    window.gsFormClientValidate = function () {
        var grade = document.getElementById('grade_level').value;
        var day   = document.getElementById('day_of_week').value;
        if (!grade || !day) {
            gsToast('Please select both a Grade Level and a Day of Week before saving.');
            return false;
        }
        var missingTeacher = false;
        document.querySelectorAll('.sf-subject-row').forEach(function(row) {
            var subj  = row.querySelector('.sf-subject');
            var teach = row.querySelector('.sf-teacher');
            if (subj && teach && subj.value && !teach.value) {
                missingTeacher = true;
            }
        });
        if (missingTeacher) {
            gsToast('Cannot save: every subject must have a teacher assigned. Please select a teacher or clear the subject field.');
            return false;
        }
        var hasCellDup = false;
        document.querySelectorAll('.sf-cell').forEach(function(cell) {
            if (gsDuplicateSubjectTeacherInCell(cell)) hasCellDup = true;
        });
        if (hasCellDup) {
            gsToast('Cannot save: the same subject and teacher cannot be assigned twice in one section slot.');
            return false;
        }
        var warnEls = document.querySelectorAll('.sf-conflict-warn');
        for (var i = 0; i < warnEls.length; i++) {
            var w = warnEls[i];
            if (w.style.display !== 'none' && w.textContent.trim()) {
                gsToast('Cannot save: ' + w.textContent.trim());
                return false;
            }
        }
        return true;
    };
})();
</script>
<script>
window.SF_SLOT_ASSISTANT = {
    apiUrl: @json(route('grade-school-admin.dss.assess-slot')),
    schoolLevel: 'grade_school'
};
</script>
<script src="{{ asset('js/schedule-slot-assistant.js') }}"></script>

@endsection

@push('scripts')
<script>
window.SCHEDULE_FORM_GUARD = {
    formId: 'scheduleGridForm',
    checkUrl: @json(route('grade-school-admin.schedule.check-grid')),
    submitSelector: '#sfSubmitBtn',
    clientValidate: function () { return window.gsFormClientValidate ? window.gsFormClientValidate() : true; },
};
</script>
<script src="{{ asset('js/schedule-form-submit-guard.js') }}"></script>
@endpush