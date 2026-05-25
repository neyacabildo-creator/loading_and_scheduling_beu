@php
    $isAjax = $isAjax ?? false;
    $teacherName = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? '')) ?: ($teacher->name ?? 'Teacher');
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kinder Schedule — {{ $teacherName }}</title>
@include('partials.spup-favicon')
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #e5e7eb; padding: 1.25rem; font-size: 0.8rem; }
    .card-toolbar { max-width: 960px; margin: 0 auto 0.75rem; display: flex; justify-content: space-between; align-items: center; }
    .card-toolbar button { padding: 0.45rem 1rem; border-radius: 0.4rem; font-weight: 600; font-size: 0.78rem; cursor: pointer; border: none; }
    .btn-print { background: #1a4731; color: #fff; }
    .btn-close { background: #fff; border: 1px solid #d1d5db !important; color: #374151; }
    .kinder-paper { max-width: 960px; margin: 0 auto; background: #fff; border: 2px solid #374151; box-shadow: 0 4px 18px rgba(0,0,0,.12); }
    .kinder-title { background: #1a4731; color: #fff; text-align: center; padding: 0.5rem; font-weight: 700; letter-spacing: 1px; }
    .kinder-meta { display: flex; flex-wrap: wrap; border-bottom: 1px solid #9ca3af; }
    .kinder-meta div { flex: 1; min-width: 140px; padding: 0.35rem 0.65rem; border-right: 1px solid #9ca3af; }
    .kinder-meta div:last-child { border-right: none; }
    .kinder-meta label { display: block; font-size: 0.58rem; font-weight: 700; text-transform: uppercase; color: #6b7280; }
    .kinder-meta span { font-weight: 600; color: #111; }
    .kinder-section { padding: 0.65rem 0.75rem; border-bottom: 1px solid #9ca3af; }
    .kinder-section h3 { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #1a4731; margin-bottom: 0.45rem; }
    table.kt { width: 100%; border-collapse: collapse; }
    table.kt th, table.kt td { border: 1px solid #9ca3af; padding: 0.28rem 0.35rem; text-align: center; font-size: 0.72rem; }
    table.kt th { background: #f3f4f6; font-weight: 700; }
    table.kt .time-col { text-align: left; font-weight: 700; background: #f9fafb; white-space: nowrap; width: 118px; }
    table.kt tr.break td { background: #fffbeb; font-weight: 700; color: #92400e; }
    table.kt tr.activity td { background: #ecfdf5; font-weight: 700; color: #065f46; }
    table.kt tr.routine .time-col { color: #374151; }
    @media print {
        body { background: #fff; padding: 0; }
        .card-toolbar { display: none !important; }
        .kinder-paper { box-shadow: none; max-width: 100%; }
    }
</style>
</head>
<body>
@if(!$isAjax)
<div class="card-toolbar">
    <span style="font-weight:600;color:#374151;">Kinder class schedule</span>
    <div style="display:flex;gap:0.5rem;">
        <button type="button" class="btn-print" onclick="window.print()">Print</button>
        <button type="button" class="btn-close" onclick="window.close()">Close</button>
    </div>
</div>
@endif

<div class="kinder-paper">
    <div class="kinder-title">CLASS ROUTINE &amp; SCHEDULE — {{ strtoupper($gradeLevel) }}</div>
    <div class="kinder-meta">
        <div><label>Teacher</label><span>{{ $teacherName }}</span></div>
        <div><label>Grade</label><span>{{ $gradeLevel }}</span></div>
        <div><label>Room / Section</label><span>{{ $sectionName }}</span></div>
        <div><label>School Year</label><span>{{ $schoolYear ?? '2025-2026' }}</span></div>
    </div>

    <div class="kinder-section">
        <h3>Teachers-in-Charge</h3>
        @foreach($teachersInChargeTables ?? \App\Support\KinderScheduleSupport::teachersInChargeTables() as $table)
            <p style="font-size:0.68rem;font-weight:800;text-transform:uppercase;margin:0.65rem 0 0.35rem;color:#374151;">{{ $table['title'] }}</p>
            <table class="kt" style="margin-bottom:0.75rem;">
                <thead>
                    <tr>
                        <th class="time-col" style="width:120px;"></th>
                        @foreach($table['columns'] as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($table['rows'] as $row)
                        <tr>
                            <td class="time-col" style="font-weight:700;">{{ $row['label'] }}</td>
                            @foreach($row['values'] as $val)
                                <td>{{ $val }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    </div>

    <div class="kinder-section">
        <h3>Weekly Activity — {{ strtoupper($gradeLevel) }} ({{ $sectionName }})</h3>
        <table class="kt">
            <thead>
                <tr>
                    <th class="time-col">TIME</th>
                    <th>ACTIVITY</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weekdays as $day)
                    <tr class="activity">
                        <td class="time-col">{{ strtoupper($day) }}</td>
                        <td>{{ $weeklyActivity[$day] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="kinder-section">
        <h3>Class Routine (Monday – Friday)</h3>
        <table class="kt">
            <thead>
                <tr>
                    <th class="time-col">TIME</th>
                    @foreach($weekdays as $day)
                        <th>{{ strtoupper($day) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($routineSlots as $slot)
                    @php
                        $rowClass = match ($slot['type'] ?? 'routine') {
                            'break' => 'break',
                            'activity' => 'activity',
                            default => 'routine',
                        };
                        $timeLabel = substr($slot['start'], 0, 5) . ' – ' . substr($slot['end'], 0, 5);
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="time-col">{{ $timeLabel }}<br><span style="font-weight:500;font-size:0.65rem;">{{ $slot['label'] }}</span></td>
                        @foreach($weekdays as $day)
                            <td>
                                @if(($slot['type'] ?? '') === 'activity')
                                    {{ $weeklyActivity[$day] ?? '—' }}
                                @elseif(($slot['type'] ?? '') === 'break')
                                    {{ $slot['label'] }}
                                @else
                                    {{ $slot['label'] }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
</body>
</html>
