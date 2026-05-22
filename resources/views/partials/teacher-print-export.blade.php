@php
    use App\Support\TeacherPortalSupport;
    $user = Auth::user();
    $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher');
    $exportCsvUrl = $exportCsvUrl ?? '#';
    $exportExcelUrl = $exportExcelUrl ?? ($exportCsvUrl !== '#' ? preg_replace('/format=[^&]+/', 'format=excel', $exportCsvUrl) : '#');
    $exportPrintUrl = $exportPrintUrl ?? '#';
    $divisionLabel = $divisionLabel ?? 'Teacher Portal';
@endphp

<style>
    .pe-card{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:.75rem;padding:1.5rem;margin-bottom:1.5rem}
    .pe-actions{display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem}
    .pe-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.1rem;border-radius:.5rem;font-weight:600;font-size:.875rem;text-decoration:none;border:none;cursor:pointer}
    .pe-btn-print{background:var(--green-primary,#2d7a50);color:#fff}
    .pe-btn-csv{background:#0d9488;color:#fff}
    .pe-btn-excel{background:#10b981;color:#fff}
    .pe-table{width:100%;border-collapse:collapse;font-size:.85rem}
    .pe-table th,.pe-table td{padding:.6rem .75rem;border:1px solid var(--border-color);text-align:left}
    .pe-table th{background:var(--bg-tertiary);font-weight:600}
    .pe-empty{text-align:center;padding:2rem;color:var(--text-secondary)}
    @media print {
        .no-print { display: none !important; }
        .pe-card { border: none; box-shadow: none; }
    }
</style>

<div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;" class="no-print">
    <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin:0 0 .3rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">{{ $divisionLabel }}</p>
    <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">Print &amp; Export Schedule</h1>
    <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Download or print your approved class schedule</p>
</div>

<div class="pe-card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem;color:var(--text-primary);">My Class Schedule — {{ $displayName }}</h2>

    <div class="pe-actions no-print">
        <a href="{{ $exportPrintUrl }}" target="_blank" class="pe-btn pe-btn-print">Open Print View</a>
        <a href="{{ $exportCsvUrl }}" class="pe-btn pe-btn-csv">Download CSV</a>
        <a href="{{ $exportExcelUrl }}" class="pe-btn pe-btn-excel">Download Excel</a>
        <button type="button" class="pe-btn pe-btn-print" onclick="window.print()">Print This Page</button>
    </div>

    @if($schedules->isEmpty())
        <p class="pe-empty">No approved schedules found for your account.</p>
    @else
        <div style="overflow-x:auto;">
            <table class="pe-table" id="scheduleExportTable">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Grade / Section</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $s)
                        <tr>
                            <td>{{ $s->subject ?? '—' }}</td>
                            <td>{{ trim(($s->grade_level ?? '') . ($s->section_name ? ' – ' . $s->section_name : '')) ?: '—' }}</td>
                            <td>{{ $s->day_of_week ?? '—' }}</td>
                            <td>{{ TeacherPortalSupport::formatTimeLabel($s->start_time) }} – {{ TeacherPortalSupport::formatTimeLabel($s->end_time) }}</td>
                            <td>{{ TeacherPortalSupport::roomLabel($s) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p style="font-size:.78rem;color:var(--text-secondary);margin:1rem 0 0;">{{ $schedules->count() }} class(es) · Generated {{ now()->format('M d, Y g:i A') }}</p>
    @endif
</div>
