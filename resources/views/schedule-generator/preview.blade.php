@extends($layout)

@section('title', 'Schedule Generator Preview')

@section('content')
@php
    use App\Support\ScheduleDssSupport;
    $stats = $stats ?? [];
    $proposedCount = $stats['total_proposed'] ?? count($proposed ?? []);
    $conflictCount = $stats['total_conflicts'] ?? count($conflicts ?? []);
    $unscheduledCount = $stats['total_unscheduled'] ?? count($unscheduled ?? []);
    $confirmRoute = $isGs ? route('grade-school-admin.schedule.generate.confirm') : route('admin.schedule.generate.confirm');
    $backRoute = $isGs ? route('grade-school-admin.schedule.generate') : route('admin.schedule.generate');
@endphp

<div class="header">
    <div class="header-left">
        <h1 class="page-title">Generator Preview</h1>
        <p class="page-subtitle">School year {{ $school_year }} — review before import</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="stat-card" style="padding:1rem;">
        <div class="stat-value" style="color:#2563eb;">{{ $proposedCount }}</div>
        <div class="stat-label">Proposed</div>
    </div>
    <div class="stat-card" style="padding:1rem;">
        <div class="stat-value" style="color:{{ $conflictCount > 0 ? '#d97706' : '#16a34a' }};">{{ $conflictCount }}</div>
        <div class="stat-label">Conflicts</div>
    </div>
    <div class="stat-card" style="padding:1rem;">
        <div class="stat-value" style="color:{{ $unscheduledCount > 0 ? '#dc2626' : '#16a34a' }};">{{ $unscheduledCount }}</div>
        <div class="stat-label">Unscheduled</div>
    </div>
</div>

@if(!empty($conflicts))
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><span class="card-title">Conflicts &amp; suggested actions</span></div>
    <div class="card-body" style="padding:0;">
        <table style="width:100%;font-size:.85rem;">
            <thead>
                <tr style="background:var(--bg-tertiary);">
                    <th style="padding:.6rem;text-align:left;">Issue</th>
                    <th style="padding:.6rem;text-align:left;">Suggested action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($conflicts as $c)
                <tr>
                    <td style="padding:.6rem;border-top:1px solid var(--border-color);">{{ $c['message'] ?? ($c['type'] ?? 'Conflict') }}</td>
                    <td style="padding:.6rem;border-top:1px solid var(--border-color);color:#1a3a5c;font-weight:600;">
                        {{ ScheduleDssSupport::conflictSuggestedAction($c) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(!empty($unscheduled))
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><span class="card-title">Could not schedule</span></div>
    <div class="card-body">
        <ul style="margin:0;padding-left:1.2rem;font-size:.85rem;">
            @foreach($unscheduled as $u)
                <li>{{ $u['teacher'] ?? '' }} — {{ $u['subject'] ?? '' }}: {{ $u['reason'] ?? 'No slot' }}</li>
            @endforeach
        </ul>
    </div>
</div>
@endif

@if(!empty($proposed))
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-header"><span class="card-title">Proposed rows ({{ count($proposed) }})</span></div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table style="width:100%;font-size:.8rem;">
            <thead>
                <tr style="background:var(--bg-tertiary);">
                    <th style="padding:.5rem;">Teacher</th>
                    <th style="padding:.5rem;">Subject</th>
                    <th style="padding:.5rem;">Section</th>
                    <th style="padding:.5rem;">Day</th>
                    <th style="padding:.5rem;">Time</th>
                    <th style="padding:.5rem;">Room</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($proposed, 0, 50) as $p)
                <tr>
                    <td style="padding:.45rem;border-top:1px solid var(--border-color);">{{ $p['teacher_name'] ?? '' }}</td>
                    <td style="padding:.45rem;border-top:1px solid var(--border-color);">{{ $p['subject'] ?? '' }}</td>
                    <td style="padding:.45rem;border-top:1px solid var(--border-color);">{{ $p['grade_level'] ?? '' }} {{ $p['section_name'] ?? '' }}</td>
                    <td style="padding:.45rem;border-top:1px solid var(--border-color);">{{ $p['day_of_week'] ?? '' }}</td>
                    <td style="padding:.45rem;border-top:1px solid var(--border-color);">{{ $p['start_time'] ?? '' }}–{{ $p['end_time'] ?? '' }}</td>
                    <td style="padding:.45rem;border-top:1px solid var(--border-color);">{{ $p['room_number'] ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if(count($proposed) > 50)
            <p style="padding:.75rem;font-size:.8rem;color:var(--text-secondary);">Showing first 50 of {{ count($proposed) }} proposals.</p>
        @endif
    </div>
</div>
@endif

<form method="POST" action="{{ $confirmRoute }}" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;">
    @csrf
    <label style="font-size:.85rem;display:flex;align-items:center;gap:.4rem;">
        <input type="checkbox" name="import_only_clean" value="1">
        Import only rows without conflicts
    </label>
    <button type="submit" class="btn btn-primary" onclick="return confirm('Import selected schedules as pending for admin review?');">
        Confirm import
    </button>
    <a href="{{ $backRoute }}" class="btn btn-outline">Back</a>
</form>
@endsection
