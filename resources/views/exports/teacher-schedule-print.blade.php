<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Schedule — {{ $teacherName }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; color: #1a1a1a; }
        h1 { font-size: 1.35rem; margin: 0 0 0.25rem; }
        p.meta { color: #555; font-size: 0.85rem; margin: 0 0 1.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        th, td { border: 1px solid #ccc; padding: 0.5rem 0.65rem; text-align: left; }
        th { background: #e8f5e9; }
        .empty { padding: 2rem; text-align: center; color: #666; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print" style="margin-bottom:1rem;">
        <button onclick="window.print()" style="padding:0.5rem 1rem;background:#2d7a50;color:#fff;border:none;border-radius:4px;cursor:pointer;">Print</button>
    </p>
    <h1>Class Schedule</h1>
    <p class="meta">{{ $divisionLabel }} · {{ $teacherName }} · {{ now()->format('F j, Y') }}</p>

    @if($schedules->isEmpty())
        <p class="empty">No approved schedules to print.</p>
    @else
        <table>
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
                        <td>{{ \App\Support\TeacherPortalSupport::formatTimeLabel($s->start_time) }} – {{ \App\Support\TeacherPortalSupport::formatTimeLabel($s->end_time) }}</td>
                        <td>{{ \App\Support\TeacherPortalSupport::roomLabel($s) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
