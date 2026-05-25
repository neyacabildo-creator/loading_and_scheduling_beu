@php
    $jh = $stWeeklyTimetable['jh'] ?? [];
    $gs = $stWeeklyTimetable['gs'] ?? [];
    $schoolYear = $stWeeklyTimetable['schoolYear'] ?? '2025-2026';
    $teacherName = $stWeeklyTimetable['teacherName'] ?? 'Shared Teacher';
    $jhHas = $jh['has_rows'] ?? false;
    $gsHas = $gs['has_rows'] ?? false;
    $scheduleView = $scheduleView ?? 'current';
    $selectedDate = $selectedDate ?? null;
    $buckets = $stScheduleDateBuckets ?? ['saved' => [], 'future' => [], 'past' => []];
    $viewLabels = [
        'current' => 'Current schedules',
        'saved' => 'Saved schedules',
        'future' => 'Upcoming schedules',
        'past' => 'Past schedules',
        'last' => 'Last schedules',
    ];
@endphp

@include('partials.admin-print-export-styles')

<style>
    .st-pe-panel { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.75rem; margin-bottom: 1.75rem; overflow: hidden; }
    .st-pe-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); }
    .st-pe-toolbar h2 { margin: 0; font-size: 1.05rem; font-weight: 800; color: var(--text-primary); }
    .st-pe-tabs { display: flex; gap: 0.35rem; flex-wrap: wrap; }
    .st-pe-tab { padding: 0.4rem 0.9rem; border-radius: 9999px; border: 1px solid var(--border-color); background: var(--bg-primary); font-size: 0.78rem; font-weight: 600; cursor: pointer; color: var(--text-secondary); }
    .st-pe-tab.active { background: var(--green-primary); border-color: var(--green-primary); color: #fff; }
    .st-pe-tab.jh.active { background: #1a5336; border-color: #1a5336; }
    .st-pe-tab.gs.active { background: #4338ca; border-color: #4338ca; }
    .st-pe-body { padding: 1.25rem; }
    .st-pe-body.hidden { display: none; }
    .st-pe-screen-head { text-align: center; padding-bottom: 1rem; margin-bottom: 1rem; border-bottom: 3px double #1a5336; }
    .st-pe-screen-head .photo-school { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #1a5336; margin: 0 0 0.2rem; }
    .st-pe-screen-head .photo-title { font-size: 1.05rem; font-weight: 700; color: #1a5336; margin: 0 0 0.25rem; }
    .st-pe-screen-head .photo-meta { font-size: 0.78rem; color: #555; margin: 0; }
    .st-pe-subject-badge { display: inline-block; background: #d1fae5; color: #065f46; border-radius: 9999px; padding: 0.15rem 0.65rem; font-size: 0.75rem; font-weight: 600; margin-top: 0.35rem; }
    .st-pe-empty { text-align: center; padding: 2.5rem 1rem; color: var(--text-secondary); font-size: 0.875rem; }
    .st-pe-print-btn { padding: 0.45rem 1rem; background: transparent; border: 1px solid var(--green-primary); color: var(--green-primary); border-radius: 0.4rem; font-size: 0.8rem; font-weight: 600; cursor: pointer; }
    .st-pe-date-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; border-bottom: 1px solid var(--border-color); background: var(--bg-primary); }
    .st-pe-date-bar label { font-size: 0.72rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em; }
    .st-pe-date-bar select { padding: 0.4rem 0.65rem; border: 1px solid var(--border-color); border-radius: 0.375rem; font-size: 0.8rem; background: var(--bg-secondary); color: var(--text-primary); }
    @media print {
        .sidebar, .st-pe-toolbar, .st-pe-tabs, .st-pe-print-btn, .st-pe-date-bar, .no-print { display: none !important; }
        .st-pe-panel { border: none; }
        .pe-print-header { display: block !important; }
        #stWeeklyPrintArea, #stWeeklyPrintArea * { visibility: visible !important; }
        #stWeeklyPrintArea .st-pe-body.hidden { display: none !important; visibility: hidden !important; }
    }
</style>

<div class="st-pe-panel" id="stWeeklyPrintArea">
    <form method="get" action="{{ route('shared-teacher.dashboard') }}" class="st-pe-date-bar no-print">
        <label for="st-schedule-view">View</label>
        <select name="view" id="st-schedule-view" onchange="this.form.submit()">
            @foreach($viewLabels as $key => $label)
                <option value="{{ $key }}" @selected($scheduleView === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="st-schedule-date">Date</label>
        <select name="date" id="st-schedule-date" onchange="this.form.submit()">
            <option value="">— All in this view —</option>
            @foreach(array_merge($buckets['saved'] ?? [], $buckets['future'] ?? [], $buckets['past'] ?? []) as $d)
                <option value="{{ $d }}" @selected($selectedDate === $d)>{{ \Carbon\Carbon::parse($d)->format('M d, Y') }}</option>
            @endforeach
        </select>
        @if($selectedDate || $scheduleView !== 'current')
            <a href="{{ route('shared-teacher.dashboard') }}" style="font-size:0.78rem;font-weight:600;color:var(--green-primary);">Reset</a>
        @endif
        <span style="font-size:0.75rem;color:var(--text-secondary);margin-left:auto;">
            {{ $viewLabels[$scheduleView] ?? 'Schedules' }}@if($selectedDate) · {{ \Carbon\Carbon::parse($selectedDate)->format('F d, Y') }}@endif
        </span>
    </form>
    <div class="st-pe-toolbar no-print">
        <h2>Weekly Timetable</h2>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <div class="st-pe-tabs">
                <button type="button" class="st-pe-tab jh active" data-target="st-pe-jh">Junior High</button>
                <button type="button" class="st-pe-tab gs" data-target="st-pe-gs">Grade School</button>
            </div>
            <button type="button" class="st-pe-print-btn" id="stPrintScheduleBtn">Print Schedule</button>
        </div>
    </div>

    @foreach(['jh' => $jh, 'gs' => $gs] as $key => $presentation)
        @php
            $isJh = $key === 'jh';
            $division = $presentation['division_label'] ?? ($isJh ? 'Junior High School' : 'Grade School');
            $timeSlots = $presentation['time_slots'] ?? [];
            $days = $presentation['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $cellMap = $presentation['cell_map'] ?? collect();
            $hasContent = $isJh ? $jhHas : $gsHas;
        @endphp
        <div class="st-pe-body {{ $loop->first ? '' : 'hidden' }}" id="st-pe-{{ $key }}" data-school="{{ $key }}">
            @if($hasContent)
                <div class="st-pe-screen-head">
                    <p class="photo-school">Saint Paul University Philippines · {{ $division }}</p>
                    <p class="photo-title">Class Schedule — {{ $teacherName }}</p>
                    <p class="photo-meta">School Year {{ $schoolYear }} · Generated {{ now()->format('F d, Y') }}</p>
                    @if(!empty($presentation['subject_label']))
                        <span class="st-pe-subject-badge">{{ $presentation['subject_label'] }}</span>
                    @endif
                </div>

                @include('partials.admin-print-document-header', [
                    'schoolLabel' => $division,
                    'gradeLevel' => $isJh ? 'Junior High' : 'Grade School',
                    'dayOfWeek' => null,
                    'scheduleDate' => null,
                ])

                <div style="overflow-x:auto;">
                    <table class="pe-sched-table">
                        <thead>
                            <tr>
                                <th style="min-width:72px;">TIME</th>
                                @foreach($days as $day)
                                    <th>{{ strtoupper($day) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($timeSlots as $slot)
                                @php $isFixed = \App\Support\SchoolScheduleSlots::isMasterGridFixedRow($slot, count($days)); @endphp
                                <tr class="{{ ($slot['type'] ?? '') === 'break' || $isFixed ? 'break-row' : '' }}">
                                    <td class="time-cell" style="white-space:pre-line;">{{ \App\Support\SchoolScheduleSlots::formatTimeCellLabel($slot) }}</td>
                                    @foreach($days as $day)
                                        @if($isFixed)
                                            @if($loop->first)
                                                <td colspan="{{ count($days) }}" style="text-align:center;letter-spacing:.1em;">
                                                    &#10022; {{ $slot['special'] ?? $slot['name'] ?? strtoupper($slot['type'] ?? '') }} &#10022;
                                                </td>
                                            @endif
                                            @continue
                                        @endif
                                        @if(!\App\Support\SchoolScheduleSlots::slotAppliesToDay($slot, $day))
                                            <td><span class="pe-empty-cell">-</span></td>
                                            @continue
                                        @endif
                                        @if(($slot['type'] ?? '') === 'homeroom')
                                            <td style="font-weight:700;color:#92400e;">{{ $slot['special'] ?? $slot['name'] ?? 'HOMEROOM' }}</td>
                                            @continue
                                        @endif
                                        @php
                                            $cell = $cellMap->get(($slot['order'] ?? 0) . '_' . $day);
                                        @endphp
                                        <td>
                                            @if($cell && ($cell->grade_section || $cell->subject))
                                                <div class="pe-cell-entry">
                                                    @if($cell->subject)
                                                        <div class="pe-cell-subject">{{ $cell->subject }}</div>
                                                    @endif
                                                    @if($cell->grade_section)
                                                        <div class="pe-cell-teacher">({{ $cell->grade_section }})</div>
                                                    @endif
                                                    @if(!empty($cell->detail))
                                                        <div class="pe-cell-teacher">{{ $cell->detail }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="pe-empty-cell">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p style="font-size:.75rem;color:var(--text-secondary);margin:1rem 0 0;text-align:center;">
                    Official weekly schedule summary · {{ $division }}
                </p>
            @else
                <div class="st-pe-empty">
                    <p>No approved {{ $isJh ? 'Junior High' : 'Grade School' }} schedules to display yet.</p>
                </div>
            @endif
        </div>
    @endforeach
</div>

<script>
(function () {
    document.querySelectorAll('.st-pe-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.st-pe-tab').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            document.querySelectorAll('.st-pe-body').forEach(function (panel) {
                panel.classList.add('hidden');
            });
            var target = document.getElementById(tab.dataset.target);
            if (target) target.classList.remove('hidden');
        });
    });

    var printBtn = document.getElementById('stPrintScheduleBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            var area = document.getElementById('stWeeklyPrintArea');
            if (!area) {
                window.print();
                return;
            }
            area.setAttribute('id', 'pe-printable');
            window.print();
            area.setAttribute('id', 'stWeeklyPrintArea');
        });
    }
})();
</script>
