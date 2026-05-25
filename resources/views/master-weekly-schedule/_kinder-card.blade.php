@php
    $isAjax = $isAjax ?? false;
    $teacherName = trim(($teacher->first_name ?? '') . ' ' . ($teacher->last_name ?? '')) ?: ($teacher->name ?? 'Teacher');
    $downloadUrl = $downloadUrl ?? null;
@endphp
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Teachers-in-Charge — {{ $teacherName }}</title>
@include('partials.spup-favicon')
<style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: #e5e7eb; padding: 1.5rem; }
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
    .btn-download-card { background: #fff; color: #0369a1; border: 1px solid #0369a1 !important; }
    .btn-download-card:hover { background: #0369a1; color: #fff; }
    .btn-close-modal { background: transparent; color: #6b7280; border: 1px solid #d1d5db !important; }
    .btn-close-modal:hover { background: #f3f4f6; }

    .sched-paper {
        max-width: 900px; margin: 0 auto;
        background: #fff; border: 2px solid #374151;
        box-shadow: 0 4px 20px rgba(0,0,0,.18);
    }
    .sched-title-bar {
        background: #1a4731; color: #fff;
        text-align: center; padding: 0.55rem 1rem;
        font-size: 1.05rem; font-weight: 700; letter-spacing: 1.5px;
    }
    .sched-subtitle-bar {
        background: #f3f4f6; color: #1f2937;
        text-align: center; padding: 0.4rem 1rem;
        font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em;
        border-bottom: 1px solid #9ca3af;
    }
    .sched-info-row { display: flex; border-bottom: 1px solid #9ca3af; }
    .sched-info-cell {
        flex: 1; padding: 0.35rem 0.75rem;
        border-right: 1px solid #9ca3af; min-width: 0;
    }
    .sched-info-cell:last-child { border-right: none; }
    .sic-label { display: block; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; color: #6b7280; margin-bottom: 1px; }
    .sic-value { display: block; font-size: 0.8rem; color: #111; font-weight: 600; border-bottom: 1px solid #d1d5db; min-height: 1.4rem; padding-bottom: 1px; }

    .sched-section { padding: 0.65rem 0.75rem 0.85rem; border-bottom: 1px solid #9ca3af; }
    .sched-section:last-child { border-bottom: none; }

    .sched-table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 0.5rem; }
    .sched-table th {
        background: #f3f4f6; text-align: center;
        font-size: 0.7rem; font-weight: 700; color: #1f2937;
        padding: 0.4rem 0.25rem; border: 1px solid #9ca3af;
        letter-spacing: 0.4px;
    }
    .sched-table th.th-role { width: 110px; text-align: left; padding-left: 0.5rem; }
    .sched-table td {
        border: 1px solid #9ca3af; padding: 0.35rem 0.3rem;
        text-align: center; font-size: 0.72rem; font-weight: 600; color: #111;
        vertical-align: middle;
    }
    .sched-table td.td-role {
        text-align: left; padding-left: 0.5rem;
        background: #f9fafb; font-weight: 700;
    }
    .sched-table tr.activity-row td { background: #ecfdf5; }

    .sched-sig {
        display: flex; gap: 1rem; padding: 0.75rem 1rem;
        border-top: 1px solid #9ca3af; font-size: 0.72rem; color: #374151;
    }
    .sched-sig-block { flex: 1; text-align: center; }
    .sig-line { border-bottom: 1px solid #555; height: 1.6rem; margin-bottom: 3px; }

    @media print {
        body { background: #fff; padding: 0; }
        .card-toolbar { display: none !important; }
        .sched-paper { box-shadow: none; border: 1.5px solid #000; max-width: 100%; }
        .sched-title-bar { background: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { size: A4 portrait; margin: 1cm; }
    }
</style>
</head>
<body>

<div class="sched-paper">
    <div class="sched-title-bar">KINDER WEEKLY SCHEDULE</div>

    <div class="sched-info-row">
        <div class="sched-info-cell" style="flex:2">
            <span class="sic-label">Faculty (this card)</span>
            <span class="sic-value">{{ strtoupper($teacherName) }}</span>
        </div>
        <div class="sched-info-cell">
            <span class="sic-label">Grade Level</span>
            <span class="sic-value">{{ $gradeLevel }}</span>
        </div>
        <div class="sched-info-cell">
            <span class="sic-label">Room / Section</span>
            <span class="sic-value">{{ $sectionName }}</span>
        </div>
        <div class="sched-info-cell">
            <span class="sic-label">School Year</span>
            <span class="sic-value">{{ $schoolYear ?? '2025-2026' }}</span>
        </div>
    </div>

    <div class="sched-subtitle-bar">WEEKLY ACTIVITY SCHEDULE — {{ strtoupper($gradeLevel) }} ({{ $sectionName }})</div>
    <div class="sched-section">
        <table class="sched-table">
            <thead>
                <tr>
                    <th class="th-role">TIME</th>
                    <th>Subjects</th>
                </tr>
            </thead>
            <tbody>
                @foreach($weekdays as $day)
                    <tr class="activity-row">
                        <td class="td-role">{{ strtoupper($day) }}</td>
                        <td>{{ ($weeklyActivity[$day] ?? '') !== '' ? $weeklyActivity[$day] : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

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
</div>

</body>
</html>
