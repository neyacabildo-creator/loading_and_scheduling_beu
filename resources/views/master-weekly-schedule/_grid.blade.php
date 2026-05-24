{{-- resources/views/master-weekly-schedule/_grid.blade.php --}}
{{--
    Paper-style form for the Master Loading Schedule management page.
    Layout mirrors the physical printed schedule sheet (see image 2).

    Variables expected (passed from JH/GS manage wrappers):
        $teacher        - User model
        $schoolYear     - string e.g. "2025-2026"
        $timeSlots      - array from MasterWeeklyScheduleController::timeSlots()
        $days           - array ["Monday","Tuesday","Wednesday","Thursday","Friday"]
        $existing       - Collection keyed by "slot_order_DayOfWeek"
        $subjectHandled - string (current subject_handled value)
        $saveRoute      - named route for form action
        $clearRoute     - named route for clear action
        $backRoute      - named route for back button
--}}

<style>
    /* ======================================================
       Toolbar (screen only)
    ====================================================== */
    .mws-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 0.75rem;
        max-width: 960px; margin: 0 auto 1rem;
    }
    .mws-toolbar-left { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }
    .mws-toolbar-right { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .mws-toolbar a, .mws-toolbar button {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.5rem 1rem; border-radius: 0.45rem;
        font-size: 0.8rem; font-weight: 600; cursor: pointer;
        border: 1px solid var(--border-color, #d1d5db); text-decoration: none;
        background: var(--bg-secondary, #fff); color: var(--text-primary, #111);
        transition: all 0.18s;
    }
    .mws-toolbar .t-btn-save   { background: #1a4731; color: #fff; border-color: #1a4731; }
    .mws-toolbar .t-btn-save:hover { background: #14392a; }
    .mws-toolbar .t-btn-print  { color: #1a4731; border-color: #1a4731; }
    .mws-toolbar .t-btn-print:hover { background: #1a4731; color: #fff; }
    .mws-toolbar .t-btn-download { color: #0369a1; border-color: #0369a1; }
    .mws-toolbar .t-btn-download:hover { background: #0369a1; color: #fff; }
    .mws-toolbar .t-btn-clear  { color: #dc2626; border-color: #dc2626; }
    .mws-toolbar .t-btn-clear:hover { background: #dc2626; color: #fff; }
    .mws-toolbar .t-btn-back   { color: var(--text-secondary, #6b7280); }
    .mws-toolbar .t-btn-back:hover { background: var(--bg-tertiary, #f3f4f6); }

    /* ======================================================
       Paper card
    ====================================================== */
    .mws-paper {
        max-width: 960px; margin: 0 auto;
        background: #fff; color: #111;
        border: 2px solid #374151;
        box-shadow: 0 4px 24px rgba(0,0,0,.14);
        font-family: Arial, Helvetica, sans-serif;
    }

    /* Title bar */
    .mws-title-bar {
        background: #1a4731; color: #fff;
        text-align: center; padding: 0.55rem 1rem;
        font-size: 1.05rem; font-weight: 700; letter-spacing: 1.5px;
    }

    /* Header info section */
    .mws-info-row { display: flex; border-bottom: 1px solid #9ca3af; }
    .mws-ic {
        flex: 1; padding: 0.4rem 0.75rem;
        border-right: 1px solid #9ca3af; min-width: 0;
    }
    .mws-ic:last-child { border-right: none; }
    .mws-ic-label {
        display: block; font-size: 0.6rem; font-weight: 700;
        text-transform: uppercase; color: #6b7280; margin-bottom: 2px;
    }
    .mws-ic input, .mws-ic select {
        display: block; width: 100%;
        border: none; border-bottom: 1px solid #9ca3af;
        outline: none; font-size: 0.82rem; font-weight: 600;
        color: #111; background: transparent; padding: 1px 0 2px;
        font-family: inherit;
    }
    .mws-ic input:focus, .mws-ic select:focus {
        border-bottom-color: #1a4731;
        box-shadow: none;
    }

    /* ======================================================
       Weekly grid
    ====================================================== */
    .mws-table-wrap { overflow-x: auto; }
    .mws-table { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 680px; }
    .mws-table th {
        background: #f3f4f6; text-align: center;
        font-size: 0.7rem; font-weight: 700; color: #1f2937;
        padding: 0.4rem 0.25rem; border: 1px solid #9ca3af;
        letter-spacing: 0.5px;
    }
    .mws-table th.th-time { width: 90px; text-align: left; padding-left: 0.5rem; }
    .mws-table td { border: 1px solid #9ca3af; padding: 0; vertical-align: top; }
    .mws-table td.td-time {
        font-size: 0.68rem; font-weight: 700; text-align: center;
        background: #f9fafb; padding: 0.3rem 0.2rem;
        color: #374151; white-space: nowrap; vertical-align: middle;
        width: 90px;
    }
    /* Fixed rows (LUNCH / HOMEROOM) */
    .mws-table td.td-fixed {
        background: #f0fdf4; text-align: center;
        font-size: 0.75rem; font-weight: 700; color: #14532d;
        padding: 0.55rem 0.5rem; vertical-align: middle;
        letter-spacing: 0.5px;
    }
    /* Editable cell container */
    .mws-cell-wrap { padding: 0.3rem 0.35rem; }
    .mws-lbl { display: block; font-size: 0.6rem; color: #9ca3af; margin-bottom: 1px; }
    .mws-cell-grade {
        width: 100%; border: none; border-bottom: 1px dashed #d1d5db;
        outline: none; font-size: 0.78rem; font-weight: 600;
        color: #111; background: transparent; padding: 1px 0 2px;
        font-family: inherit;
    }
    .mws-cell-grade:focus { border-bottom-color: #1a4731; }
    .mws-cell-grade::placeholder { color: #c4cdd6; font-weight: 400; }
    .mws-cell-students {
        width: 100%; border: none; outline: none;
        font-size: 0.68rem; color: #555; line-height: 1.4;
        background: transparent; resize: none; padding: 2px 0 0;
        font-family: inherit; min-height: 48px;
    }
    .mws-cell-students:focus { outline: none; }
    .mws-cell-students::placeholder { color: #cbd5e1; }

    /* Year select in toolbar */
    .mws-year-select {
        padding: 0.4rem 0.6rem; border-radius: 0.4rem;
        border: 1px solid var(--border-color, #d1d5db);
        background: var(--bg-secondary, #fff); color: var(--text-primary, #111);
        font-size: 0.8rem; font-weight: 600;
    }

    /* Signature strip */
    .mws-sig {
        display: flex; gap: 1rem; padding: 0.75rem 1rem;
        border-top: 1px solid #9ca3af; font-size: 0.72rem; color: #374151;
    }
    .mws-sig-block { flex: 1; text-align: center; }
    .sig-line { border-bottom: 1px solid #555; height: 1.6rem; margin-bottom: 3px; }

    /* Alert */
    .mws-alert {
        background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46;
        padding: 0.65rem 1rem; border-radius: 0.5rem;
        margin: 0 auto 1rem; max-width: 960px; font-size: 0.875rem;
    }

    /* ======================================================
       Print overrides
    ====================================================== */
    @media print {
        .mws-toolbar, nav, header, aside { display: none !important; }
        body { background: #fff; }
        .mws-paper {
            box-shadow: none; border: 1.5px solid #000; max-width: 100%; margin: 0;
        }
        .mws-title-bar { background: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .mws-table td.td-fixed { background: #e8f5e9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .mws-cell-grade, .mws-cell-students {
            border: none !important; pointer-events: none;
        }
        .mws-lbl { display: none; }
        @page { size: A4 landscape; margin: 1cm; }
    }
</style>

{{-- ===== TOOLBAR (screen only) ===== --}}
<div class="mws-toolbar">
    <div class="mws-toolbar-left">
        <a href="{{ route($backRoute) }}" class="t-btn-back">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back
        </a>
        <span style="font-size:0.8rem;color:var(--text-secondary,#6b7280)">School Year:</span>
        <select class="mws-year-select" onchange="switchSchoolYear(this.value)">
            @foreach(['2024-2025','2025-2026','2026-2027'] as $sy)
                <option value="{{ $sy }}" {{ $schoolYear === $sy ? 'selected' : '' }}>{{ $sy }}</option>
            @endforeach
        </select>
    </div>
    <div class="mws-toolbar-right">
        <button type="button" class="t-btn-print" onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
        @if(!empty($downloadRoute))
        <button type="button" class="t-btn-download" onclick="mwsDownloadSchedule()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download
        </button>
        @endif
        <button type="button" class="t-btn-clear"
            onclick="if(confirm('Clear ALL cells for this teacher and school year?')) document.getElementById('mwsClearForm').submit()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Clear
        </button>
        <button type="submit" form="mwsForm" class="t-btn-save">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Save Schedule
        </button>
    </div>
</div>

{{-- ===== PAPER FORM ===== --}}
<form id="mwsForm" method="POST" action="{{ route($saveRoute, ['teacherId' => $teacher->id]) }}">
@csrf
<input type="hidden" name="school_year" value="{{ $schoolYear }}">

<div class="mws-paper">

    {{-- Title bar --}}
    <div class="mws-title-bar">MASTER LOADING SCHEDULE</div>

    {{-- Info row 1: Teacher name / School year / Semester --}}
    <div class="mws-info-row">
        <div class="mws-ic" style="flex:2.5">
            <span class="mws-ic-label">Name of Faculty</span>
            <input type="text" value="{{ strtoupper(trim($teacher->first_name . ' ' . $teacher->last_name)) }}" readonly>
        </div>
        <div class="mws-ic">
            <span class="mws-ic-label">School Year</span>
            <input type="text" value="{{ $schoolYear }}" readonly>
        </div>
        <div class="mws-ic">
            <span class="mws-ic-label">Semester</span>
            <select name="semester">
                <option value="1st Semester">1st Semester</option>
                <option value="2nd Semester">2nd Semester</option>
                <option value="Summer">Summer</option>
            </select>
        </div>
    </div>

    {{-- Info row 2: Subject handled / Units --}}
    <div class="mws-info-row">
        <div class="mws-ic" style="flex:3">
            <span class="mws-ic-label">Subject / Grade Level Handled</span>
            @php $savedSubject = old('subject_handled', $subjectHandled); @endphp
            <select name="subject_handled">
                <option value="">— Select —</option>
                @foreach($subjectOptions ?? [] as $opt)
                    <option value="{{ $opt }}" {{ $savedSubject === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                @endforeach
                {{-- Keep saved value selectable even if it's no longer in the current schedule --}}
                @if($savedSubject && !($subjectOptions ?? collect())->contains($savedSubject))
                    <option value="{{ $savedSubject }}" selected>{{ $savedSubject }}</option>
                @endif
            </select>
        </div>
        <div class="mws-ic">
            <span class="mws-ic-label">Total Units / Load</span>
            <input type="text" name="total_units" id="mwsTotalUnits" placeholder="Auto-calculated" readonly
                   style="cursor:default;background:transparent;">
        </div>
    </div>

    {{-- Weekly grid --}}
    <div class="mws-table-wrap">
        <table class="mws-table">
            <thead>
                <tr>
                    <th class="th-time">TIME</th>
                    @foreach($days as $day)
                        <th>{{ strtoupper($day) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($timeSlots as $slot)
                    @php
                        $isFixed = \App\Support\SchoolScheduleSlots::isMasterGridFixedRow($slot, count($days));
                    @endphp
                    <tr>
                        <td class="td-time">{{ $slot['label'] }}</td>
                        @if($isFixed)
                            {{-- Fixed rows: preserve via hidden inputs, show label --}}
                            <td class="td-fixed" colspan="{{ count($days) }}">
                                @foreach($days as $day)
                                    <input type="hidden" name="cells[{{ $slot['order'] }}][{{ $day }}][entry_type]" value="{{ $slot['type'] }}">
                                @endforeach
                                {{ $slot['special'] ?? $slot['name'] ?? strtoupper($slot['type']) }}
                            </td>
                        @else
                            @foreach($days as $day)
                                @if(!\App\Support\SchoolScheduleSlots::slotAppliesToDay($slot, $day))
                                    <td class="td-fixed" style="background:var(--bg-tertiary);color:var(--text-secondary);text-align:center;font-size:.72rem;">—</td>
                                    @continue
                                @endif
                                @if(($slot['type'] ?? '') === 'homeroom')
                                    <td class="td-fixed">
                                        <input type="hidden" name="cells[{{ $slot['order'] }}][{{ $day }}][entry_type]" value="homeroom">
                                        {{ $slot['special'] ?? $slot['name'] ?? 'HOMEROOM' }}
                                    </td>
                                    @continue
                                @endif
                                @php
                                    $key  = $slot['order'] . '_' . $day;
                                    $cell = $existing->get($key);
                                @endphp
                                <td>
                                    <input type="hidden" name="cells[{{ $slot['order'] }}][{{ $day }}][entry_type]" value="class">
                                    <div class="mws-cell-wrap">
                                        <span class="mws-lbl">Class / Section</span>
                                        <input type="text"
                                               class="mws-cell-grade"
                                               name="cells[{{ $slot['order'] }}][{{ $day }}][grade_section]"
                                               value="{{ old('cells.' . $slot['order'] . '.' . $day . '.grade_section', $cell?->grade_section ?? '') }}"
                                               placeholder="Fil 8 – St. Joseph">
                                        <span class="mws-lbl" style="margin-top:3px;">Substitute Teacher</span>
                                        <textarea
                                            class="mws-cell-students"
                                            name="cells[{{ $slot['order'] }}][{{ $day }}][substitute_teacher]"
                                            placeholder="Substitute teacher name…"
                                            rows="3">{{ old('cells.' . $slot['order'] . '.' . $day . '.substitute_teacher', $cell?->substitute_teacher ?? '') }}</textarea>
                                    </div>
                                </td>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Signature strip --}}
    <div class="mws-sig">
        <div class="mws-sig-block">
            <div class="sig-line"></div>
            Prepared by (Faculty)
        </div>
        <div class="mws-sig-block">
            <div class="sig-line"></div>
            Noted by (Principal / Dept. Head)
        </div>
        <div class="mws-sig-block">
            <div class="sig-line"></div>
            Date
        </div>
    </div>

</div>{{-- /mws-paper --}}
</form>

{{-- Clear form (DELETE) --}}
<form id="mwsClearForm" method="POST"
      action="{{ route($clearRoute, ['teacherId' => $teacher->id]) }}"
      style="display:none">
    @csrf
    @method('DELETE')
    <input type="hidden" name="school_year" value="{{ $schoolYear }}">
</form>

{{-- JSON data for client-side subject filter --}}
<script id="mws-teacher-schedules" type="application/json">{!! json_encode($teacherSchedules ?? []) !!}</script>
<script id="mws-slot-by-start" type="application/json">{!! json_encode(collect($timeSlots)->mapWithKeys(fn($s) => [$s['start'] => $s['order']])->toArray()) !!}</script>

<script>
function switchSchoolYear(year) {
    const url = new URL(window.location.href);
    url.searchParams.set('school_year', year);
    window.location.href = url.toString();
}

// ── Subject filter & auto total-units ─────────────────────────────────────
(function () {
    var MWS_SCHEDULES = JSON.parse(document.getElementById('mws-teacher-schedules')?.textContent || '[]');
    var SLOT_BY_START = JSON.parse(document.getElementById('mws-slot-by-start')?.textContent || '{}');

    var subjectSel  = document.querySelector('[name="subject_handled"]');
    var totalInp    = document.getElementById('mwsTotalUnits');

    function parseSubjectGrade(val) {
        if (!val) return { subject: '', grade: null };
        var m = val.trim().match(/^(.+?)\s+(\d+)$/);
        return m ? { subject: m[1].toUpperCase(), grade: m[2] }
                 : { subject: val.trim().toUpperCase(), grade: null };
    }

    function filterGrid(selected) {
        // 1. Clear every editable cell
        document.querySelectorAll('.mws-cell-grade').forEach(function(inp) { inp.value = ''; });

        if (!selected) return;

        var sg = parseSubjectGrade(selected);

        // 2. Re-fill cells that match the selected subject+grade
        MWS_SCHEDULES.forEach(function(cs) {
            var csSubj = (cs.subject || '').trim().toUpperCase();
            var csGradeNum = (cs.grade_level || '').replace(/[^0-9]/g, '');
            var startH = (cs.start_time || '').substring(0, 5);
            var order  = SLOT_BY_START[startH];
            if (!order) return;
            if (csSubj !== sg.subject) return;
            if (sg.grade && csGradeNum !== sg.grade) return;
            if (!cs.day_of_week) return;

            // Build label: "ENGLISH 7 – CHERUBIM"
            var label = csSubj + (csGradeNum ? ' ' + csGradeNum : '');
            if (cs.section_name) label += ' \u2013 ' + cs.section_name;

            var inp = document.querySelector(
                'input[name="cells[' + order + '][' + cs.day_of_week + '][grade_section]"]'
            );
            if (inp) inp.value = label;
        });
    }

    function calcTotalUnits(selected) {
        if (!totalInp) return;
        if (!selected) { totalInp.value = ''; return; }
        var sg = parseSubjectGrade(selected);
        var count = MWS_SCHEDULES.filter(function(cs) {
            var csSubj = (cs.subject || '').trim().toUpperCase();
            var csGradeNum = (cs.grade_level || '').replace(/[^0-9]/g, '');
            return csSubj === sg.subject && (!sg.grade || csGradeNum === sg.grade);
        }).length;
        totalInp.value = count;
    }

    if (subjectSel) {
        subjectSel.addEventListener('change', function () {
            filterGrid(this.value);
            calcTotalUnits(this.value);
        });
        // Run on initial load if a subject is already selected
        if (subjectSel.value) {
            calcTotalUnits(subjectSel.value);
        }
    }
    window.mwsDownloadSchedule = function () {
        @if(!empty($downloadRoute))
        var url = @json(route($downloadRoute, ['teacherId' => $teacher->id]));
        var params = new URLSearchParams({ school_year: @json($schoolYear) });
        var sem = document.querySelector('[name="semester"]');
        if (sem && sem.value) params.set('semester', sem.value);
        window.location.href = url + '?' + params.toString();
        @endif
    };
}());
</script>