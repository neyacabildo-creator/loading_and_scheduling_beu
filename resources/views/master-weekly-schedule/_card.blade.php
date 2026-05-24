{{--
    resources/views/master-weekly-schedule/_card.blade.php
    -------------------------------------------------------
    Read-only paper-style "Master Loading Schedule" card.
    Mirrors the physical printed form (image 2).

    Can be standalone page OR loaded via AJAX into a modal.

    Variables expected:
        $teacher        – User model
        $schoolYear     – string  e.g. "2025-2026"
        $timeSlots      – array from MasterWeeklyScheduleController::timeSlots()
        $days           – array ["Monday","Tuesday","Wednesday","Thursday","Friday"]
        $existing       – Collection keyed by "slot_order_DayOfWeek"
        $subjectHandled – string
        $semester       – string  (optional, default '1st Semester')
        $isAjax         – bool    (optional; hides wrapper chrome when true)
--}}
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Master Loading Schedule – {{ trim($teacher->first_name . ' ' . $teacher->last_name) }}</title>
@include('partials.spup-favicon')
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #e5e7eb; padding: 1.5rem; }
    /* ---------- Non-print toolbar ---------- */
    .card-toolbar {
        display: flex; align-items: center; justify-content: space-between;
        max-width: 900px; margin: 0 auto 0.75rem;
    }
    .card-toolbar .tbr-title { font-size: 0.9rem; color: #374151; font-weight: 600; }
    .card-toolbar button {
        padding: 0.45rem 1.1rem; border-radius: 0.4rem; border: none;
        font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: background 0.18s;
    }
    .btn-print-card  { background: #1a4731; color: #fff; }
    .btn-print-card:hover { background: #14392a; }
    .btn-close-modal { background: transparent; color: #6b7280; border: 1px solid #d1d5db !important; }
    .btn-close-modal:hover { background: #f3f4f6; }

    /* ---------- Paper ---------- */
    .sched-paper {
        max-width: 900px; margin: 0 auto;
        background: #fff; border: 2px solid #374151;
        box-shadow: 0 4px 20px rgba(0,0,0,.18);
    }

    /* Title bar */
    .sched-title-bar {
        background: #1a4731; color: #fff;
        text-align: center; padding: 0.55rem 1rem;
        font-size: 1.05rem; font-weight: 700; letter-spacing: 1.5px;
    }

    /* Info section */
    .sched-info-row { display: flex; border-bottom: 1px solid #9ca3af; }
    .sched-info-cell {
        flex: 1; padding: 0.35rem 0.75rem;
        border-right: 1px solid #9ca3af; min-width: 0;
    }
    .sched-info-cell:last-child { border-right: none; }
    .sic-label { display: block; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 1px; }
    .sic-value { display: block; font-size: 0.8rem; color: #111; font-weight: 600; border-bottom: 1px solid #d1d5db; min-height: 1.4rem; padding-bottom: 1px; }

    /* Grid */
    .sched-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .sched-table th {
        background: #f3f4f6; text-align: center;
        font-size: 0.7rem; font-weight: 700; color: #1f2937;
        padding: 0.4rem 0.2rem; border: 1px solid #9ca3af;
        letter-spacing: 0.5px;
    }
    .sched-table th.th-time { width: 88px; text-align: left; padding-left: 0.5rem; }
    .sched-table td { border: 1px solid #9ca3af; padding: 0; vertical-align: top; }
    .sched-table td.td-time {
        font-size: 0.68rem; font-weight: 700; text-align: center;
        background: #f9fafb; padding: 0.3rem 0.25rem;
        color: #374151; white-space: nowrap; vertical-align: middle;
        width: 88px;
    }
    /* Fixed rows (LUNCH, HOMEROOM) */
    .sched-table td.td-fixed {
        background: #f0fdf4; text-align: center;
        font-size: 0.75rem; font-weight: 700; color: #14532d;
        padding: 0.55rem 0.5rem; vertical-align: middle;
        letter-spacing: 0.5px;
    }
    /* Regular class cell */
    .sched-cell { padding: 0.3rem 0.4rem; min-height: 64px; }
    .cell-section { font-size: 0.72rem; font-weight: 700; color: #1a1a1a; margin-bottom: 3px; line-height: 1.3; }
    .cell-sub-lbl { font-size: 0.55rem; color: #9ca3af; margin-top: 3px; display: block; }
    .cell-students { font-size: 0.62rem; color: #555; line-height: 1.4; }
    .cell-empty { min-height: 64px; }

    /* Signature strip */
    .sched-sig {
        display: flex; gap: 1rem; padding: 0.75rem 1rem;
        border-top: 1px solid #9ca3af; font-size: 0.72rem; color: #374151;
    }
    .sched-sig-block { flex: 1; text-align: center; }
    .sig-line { border-bottom: 1px solid #555; height: 1.6rem; margin-bottom: 3px; }

    /* ---------- Print overrides ---------- */
    @media print {
        body { background: #fff; padding: 0; }
        .card-toolbar { display: none !important; }
        .sched-paper { box-shadow: none; border: 1.5px solid #000; max-width: 100%; }
        .sched-title-bar { background: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sched-table td.td-fixed { background: #e8f5e9; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { size: A4 landscape; margin: 1cm; }
    }
</style>
</head>
<body>

{{-- ===== PAPER CARD ===== --}}
<div class="sched-paper">

    {{-- Title --}}
    <div class="sched-title-bar">MASTER LOADING SCHEDULE</div>

    {{-- Row 1: Teacher name / School year / Semester --}}
    <div class="sched-info-row">
        <div class="sched-info-cell" style="flex:2.5">
            <span class="sic-label">Name of Faculty</span>
            <span class="sic-value">{{ strtoupper(trim($teacher->first_name . ' ' . $teacher->last_name)) }}</span>
        </div>
        <div class="sched-info-cell">
            <span class="sic-label">School Year</span>
            <span class="sic-value">{{ $schoolYear }}</span>
        </div>
        <div class="sched-info-cell">
            <span class="sic-label">Semester</span>
            <span class="sic-value">{{ $semester ?? '1st Semester' }}</span>
        </div>
    </div>

    {{-- Row 2: Subject / Units --}}
    <div class="sched-info-row">
        <div class="sched-info-cell" style="flex:3">
            <span class="sic-label">Subject / Grade Level Handled</span>
            <span class="sic-value">{{ $subjectHandled ?: '—' }}</span>
        </div>
        <div class="sched-info-cell">
            <span class="sic-label">Total Units / Load</span>
            <span class="sic-value">&nbsp;</span>
        </div>
    </div>

    {{-- Weekly grid --}}
    <table class="sched-table">
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
                @php $isFixed = \App\Support\SchoolScheduleSlots::isMasterGridFixedRow($slot, count($days)); @endphp
                <tr>
                    <td class="td-time" style="white-space:pre-line;">{{ \App\Support\SchoolScheduleSlots::formatTimeCellLabel($slot) }}</td>
                    @if($isFixed)
                        <td class="td-fixed" colspan="{{ count($days) }}">
                            {{ $slot['special'] ?? $slot['name'] ?? strtoupper($slot['type']) }}
                        </td>
                    @else
                        @foreach($days as $day)
                            @if(!\App\Support\SchoolScheduleSlots::slotAppliesToDay($slot, $day))
                                <td class="td-fixed" style="text-align:center;color:var(--text-secondary);">—</td>
                                @continue
                            @endif
                            @if(($slot['type'] ?? '') === 'homeroom')
                                <td class="td-fixed">{{ $slot['special'] ?? $slot['name'] ?? 'HOMEROOM' }}</td>
                                @continue
                            @endif
                            @php
                                $cell = $existing->get($slot['order'] . '_' . $day);
                            @endphp
                            <td>
                                @if($cell && $cell->grade_section)
                                    <div class="sched-cell">
                                        <div class="cell-section">{{ $cell->grade_section }}</div>
                                        @if($cell->substitute_teacher)
                                            <span class="cell-sub-lbl">Substitute Teacher</span>
                                            <div class="cell-students">{{ $cell->substitute_teacher }}</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="cell-empty"></div>
                                @endif
                            </td>
                        @endforeach
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Signature strip --}}
    <div class="sched-sig">
        <div class="sched-sig-block">
            <div class="sig-line"></div>
            Prepared by (Faculty)
        </div>
        <div class="sched-sig-block">
            <div class="sig-line"></div>
            Noted by (Principal / Dept. Head)
        </div>
        <div class="sched-sig-block">
            <div class="sig-line"></div>
            Date
        </div>
    </div>

</div>{{-- /sched-paper --}}

</body>
</html>
