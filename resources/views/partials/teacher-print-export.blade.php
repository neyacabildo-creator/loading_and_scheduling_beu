@php
    use App\Support\TeacherPortalSupport;
    $user = Auth::user();
    $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher');
    $exportCsvUrl = $exportCsvUrl ?? '#';
    $exportPrintUrl = $exportPrintUrl ?? '#';
    $divisionLabel = $divisionLabel ?? 'Teacher Portal';
    $photoSlug = \Illuminate\Support\Str::slug($displayName) ?: 'teacher';
@endphp

<style>
    .pe-card{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:.75rem;padding:1.5rem;margin-bottom:1.5rem}
    .pe-actions{display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1.25rem}
    .pe-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.1rem;border-radius:.5rem;font-weight:600;font-size:.875rem;text-decoration:none;border:none;cursor:pointer}
    .pe-btn-print{background:var(--green-primary,#2d7a50);color:#fff}
    .pe-btn-download{background:#0d9488;color:#fff}
    .pe-btn-photo{background:#2563eb;color:#fff}
    .pe-btn-photo:disabled{opacity:.55;cursor:not-allowed}
    .pe-btn:hover{filter:brightness(1.05)}
    #schedulePhotoCapture{background:#fff;padding:1.25rem 1.5rem;border-radius:.5rem;border:1px solid var(--border-color)}
    .schedule-photo-head{text-align:center;padding-bottom:.85rem;margin-bottom:1rem;border-bottom:3px double #1a5336}
    .schedule-photo-head .photo-school{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#1a5336;margin:0 0 .2rem}
    .schedule-photo-head .photo-title{font-size:1.05rem;font-weight:700;color:#1a5336;margin:0 0 .25rem}
    .schedule-photo-head .photo-meta{font-size:.78rem;color:#555;margin:0}
    .schedule-photo-foot{font-size:.75rem;color:#666;margin:1rem 0 0;text-align:center}
    .pe-table{width:100%;border-collapse:collapse;font-size:.85rem}
    .pe-table th,.pe-table td{padding:.6rem .75rem;border:1px solid var(--border-color);text-align:left}
    .pe-table th{background:var(--bg-tertiary);font-weight:600}
    .pe-empty{text-align:center;padding:2rem;color:var(--text-secondary)}
    @media print {
        .no-print { display: none !important; }
        .pe-card { border: none; box-shadow: none; }
    }
</style>

@include('partials.teacher-page-banner', [
    'eyebrow' => $divisionLabel,
    'pageTitle' => 'Print & Export Schedule',
    'pageSubtitle' => 'Download or print your approved class schedule',
    'bannerClass' => 'no-print',
    'notificationsApi' => str_contains($exportCsvUrl ?? '', 'grade-school-teacher')
        ? '/api/grade-school-teacher/notifications'
        : '/api/teacher/notifications',
])

<div class="pe-card">
    <h2 style="margin:0 0 1rem;font-size:1.1rem;color:var(--text-primary);">My Class Schedule — {{ $displayName }}</h2>

    <div class="pe-actions no-print">
        <a href="{{ $exportPrintUrl }}" target="_blank" class="pe-btn pe-btn-print">Open Print View</a>
        <a href="{{ $exportCsvUrl }}" class="pe-btn pe-btn-download">Download Schedule</a>
        <button type="button" class="pe-btn pe-btn-photo" id="downloadSchedulePhotoBtn" @if($schedules->isEmpty()) disabled @endif>
            Download Photo
        </button>
    </div>

    @if($schedules->isEmpty())
        <p class="pe-empty">No approved schedules found for your account.</p>
    @else
        <div id="schedulePhotoCapture">
            <div class="schedule-photo-head">
                <p class="photo-school">Saint Paul University Philippines · {{ $divisionLabel }}</p>
                <p class="photo-title">Class Schedule — {{ $displayName }}</p>
                <p class="photo-meta">Generated {{ now()->format('F d, Y g:i A') }}</p>
            </div>
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
            <p class="schedule-photo-foot">{{ $schedules->count() }} class(es) · Official schedule summary</p>
        </div>
        <p class="no-print" style="font-size:.78rem;color:var(--text-secondary);margin:1rem 0 0;">{{ $schedules->count() }} class(es) · Generated {{ now()->format('M d, Y g:i A') }}</p>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" crossorigin="anonymous"></script>
<script>
(function () {
    const btn = document.getElementById('downloadSchedulePhotoBtn');
    const target = document.getElementById('schedulePhotoCapture');
    if (!btn || !target) return;

    const safeName = @json($photoSlug);

    btn.addEventListener('click', async function () {
        if (typeof html2canvas !== 'function') {
            alert('Image export is not available. Please check your internet connection and try again.');
            return;
        }
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Preparing…';
        try {
            const canvas = await html2canvas(target, {
                scale: 2,
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false,
            });
            const link = document.createElement('a');
            const datePart = new Date().toISOString().slice(0, 10);
            link.download = 'class-schedule-' + safeName + '-' + datePart + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (err) {
            console.error(err);
            alert('Could not create schedule image. Please try again or use Print View.');
        } finally {
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });
})();
</script>
@endpush
