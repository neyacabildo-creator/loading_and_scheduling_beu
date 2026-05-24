@php
    use App\Support\ScheduleDssSupport;
    $impact = ScheduleDssSupport::assessRequestImpact(
        $adminConnection ?? 'mysql_jh',
        $schoolLevel ?? 'junior_high',
        $tsr
    );
    $summary = $impact['summary'] ?? [];
    $warnings = $impact['warnings'] ?? [];
    $substitutes = $impact['substitutes'] ?? [];
@endphp
<div class="admin-request-impact" style="margin-bottom:.65rem;padding:.65rem .75rem;background:rgba(26,58,92,.04);border:1px solid rgba(26,58,92,.15);border-radius:.5rem;font-size:.78rem;">
    <div style="font-weight:700;color:var(--text-primary);margin-bottom:.35rem;">Impact</div>
    @if(!empty($summary['preferred_date']))
        <div><strong>Date:</strong> {{ $summary['preferred_date'] }}</div>
    @endif
    @if(!empty($summary['day']))
        <div><strong>Day:</strong> {{ $summary['day'] }}</div>
    @endif
    @if(!empty($summary['time']))
        <div><strong>Time:</strong> {{ $summary['time'] }}</div>
    @endif
    @if(!empty($summary['subject']) || !empty($summary['grade_section']))
        <div><strong>Class:</strong> {{ trim(($summary['subject'] ?? '') . ' ' . ($summary['grade_section'] ?? '')) }}</div>
    @endif
    @foreach($warnings as $w)
        <div style="color:#d97706;margin-top:.25rem;">⚠ {{ $w }}</div>
    @endforeach
    @if(count($substitutes) > 0)
        <div style="margin-top:.4rem;font-weight:600;color:var(--text-secondary);">Suggested substitutes</div>
        <ul style="margin:.25rem 0 0;padding-left:1.1rem;">
            @foreach($substitutes as $sub)
                <li>{{ $sub['name'] }}@if(($sub['kind'] ?? '') === 'shared_teacher') <span style="opacity:.7;">(Shared)</span>@endif</li>
            @endforeach
        </ul>
    @endif
</div>
