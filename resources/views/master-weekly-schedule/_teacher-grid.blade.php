{{-- resources/views/master-weekly-schedule/_teacher-grid.blade.php --}}
{{--
    Read-only weekly schedule grid shown to teachers.
    Variables expected:
        $weeklyGrid  - Collection of MasterWeeklySchedule records for this teacher+year
        $timeSlots   - array from MasterWeeklyScheduleController::timeSlots()
        $days        - array of day names
        $weeklySchoolYear - string
--}}

@if($weeklyGrid->isNotEmpty())
<div style="margin-top: 2.5rem;">

    <style>
        .teacher-mws-wrap { overflow-x: auto; border: 1px solid var(--border-color); border-radius: 0.75rem; background: var(--bg-secondary); }
        .teacher-mws-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; }
        .teacher-mws-table { width: 100%; border-collapse: collapse; min-width: 700px; font-size: 0.82rem; }
        .teacher-mws-table th { padding: 0.65rem 0.85rem; background: var(--bg-primary); color: var(--text-primary); font-weight: 600; text-align: center; border: 1px solid var(--border-color); white-space: nowrap; font-size: 0.8rem; }
        .teacher-mws-table td { padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); vertical-align: top; }
        .teacher-mws-table td.time-col { font-size: 0.78rem; font-weight: 600; color: var(--text-primary); background: var(--bg-primary); white-space: nowrap; text-align: center; }
        .teacher-mws-table td.fixed-row { background: var(--bg-tertiary); text-align: center; font-size: 0.78rem; font-weight: 600; color: var(--text-tertiary); }
        .cell-section { font-weight: 600; color: var(--text-primary); margin-bottom: 0.2rem; line-height: 1.3; }
        .cell-students-display { font-size: 0.73rem; color: var(--text-tertiary); margin-top: 0.2rem; line-height: 1.4; white-space: pre-wrap; word-break: break-word; }
        .mws-subject-badge { display: inline-block; background: #d1fae5; color: #065f46; border-radius: 9999px; padding: 0.15rem 0.65rem; font-size: 0.75rem; font-weight: 600; }
        @media print {
            .teacher-mws-wrap { border: none; }
            .teacher-mws-table { font-size: 0.75rem; }
            .teacher-mws-header .btn-print { display: none; }
        }
    </style>

    <div class="teacher-mws-wrap">
        <div class="teacher-mws-header">
            <div>
                <h2 style="font-size:1.05rem; font-weight:700; color:var(--text-primary); margin:0 0 0.2rem;">Weekly Master Schedule</h2>
                @php
                    $subjectHandled = $weeklyGrid->first()?->subject_handled;
                @endphp
                @if($subjectHandled)
                    <span class="mws-subject-badge">{{ $subjectHandled }}</span>
                @endif
                <span style="font-size:0.8rem; color:var(--text-tertiary); margin-left:0.5rem;">S.Y. {{ $weeklySchoolYear }}</span>
            </div>
            <button onclick="window.print()" style="padding:0.45rem 1rem; background:transparent; border:1px solid var(--green-primary); color:var(--green-primary); border-radius:0.4rem; font-size:0.8rem; font-weight:600; cursor:pointer;" class="btn-print">
                Print Schedule
            </button>
        </div>

        @php
            $cellMap = $weeklyGrid->keyBy(fn($r) => $r->slot_order . '_' . $r->day_of_week);
        @endphp

        <table class="teacher-mws-table">
            <thead>
                <tr>
                    <th style="width:100px;">TIME</th>
                    @foreach($days as $day)
                        <th>{{ strtoupper($day) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($timeSlots as $slot)
                    @php $isFixed = in_array($slot['type'], ['lunch', 'homeroom']); @endphp
                    <tr>
                        <td class="time-col">{{ $slot['label'] }}</td>
                        @foreach($days as $day)
                            @php
                                $cell = $cellMap->get($slot['order'] . '_' . $day);
                            @endphp
                            @if($isFixed)
                                <td class="fixed-row">{{ $slot['special'] ?? strtoupper($slot['type']) }}</td>
                            @else
                                <td>
                                    @if($cell && ($cell->grade_section || $cell->substitute_teacher))
                                        @if($cell->grade_section)
                                            <div class="cell-section">{{ $cell->grade_section }}</div>
                                        @endif
                                        @if($cell->substitute_teacher)
                                            <div class="cell-students-display">{{ $cell->substitute_teacher }}</div>
                                        @endif
                                    @else
                                        <span style="color:var(--text-tertiary); font-size:0.75rem;">—</span>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
