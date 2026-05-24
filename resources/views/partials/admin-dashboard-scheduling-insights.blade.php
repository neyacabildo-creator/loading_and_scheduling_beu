{{-- DSS insight strip — summary counts only; links to existing admin routes (no new nav). --}}
@php
    $summary = $schedulingInsights['summary'] ?? [];
    $totalIssues = (int) ($summary['total_issues'] ?? 0);
    $routes = $insightRoutes ?? [];
    $sharedIssues = (int) ($summary['shared_overload'] ?? 0) + (int) ($summary['shared_cross_conflicts'] ?? 0);
    $missingDate = (int) ($summary['missing_schedule_date'] ?? 0);
    $missingRoom = (int) ($summary['missing_room'] ?? 0);
    $facultyConflicts = (int) ($summary['faculty_conflicts'] ?? 0);
    $roomConflicts = (int) ($summary['room_conflicts'] ?? 0);
@endphp

@once
<style>
.dss-insights { margin-bottom: 1.75rem; }
.dss-insights > summary {
    list-style: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: .75rem;
    font-weight: 600;
    font-size: .95rem;
    color: var(--text-primary);
}
.dss-insights > summary::-webkit-details-marker { display: none; }
.dss-insights > summary::after {
    content: '▾';
    color: var(--text-secondary);
    font-size: .85rem;
    transition: transform .2s;
}
.dss-insights[open] > summary::after { transform: rotate(180deg); }
.dss-insights[open] > summary {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-color: transparent;
}
.dss-insights-body {
    padding: 1rem 1.25rem 1.25rem;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-top: none;
    border-radius: 0 0 .75rem .75rem;
}
.dss-insights-hint { font-size: .78rem; color: var(--text-secondary); font-weight: 400; margin-top: .2rem; }
.dss-insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: .75rem;
}
.dss-insight-card {
    display: block;
    text-decoration: none;
    padding: .85rem 1rem;
    border-radius: .6rem;
    border: 1px solid var(--border-color);
    background: var(--bg-tertiary);
    transition: border-color .15s, box-shadow .15s;
}
.dss-insight-card:hover {
    border-color: var(--green-primary);
    box-shadow: var(--shadow-sm);
}
.dss-insight-val { font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--text-primary); }
.dss-insight-lbl { font-size: .72rem; color: var(--text-secondary); margin-top: .3rem; font-weight: 500; }
.dss-insight-card.is-alert .dss-insight-val { color: #dc2626; }
.dss-insight-card.is-warn .dss-insight-val { color: #d97706; }
.dss-insight-card.is-ok .dss-insight-val { color: #16a34a; }
.dss-insights-badge {
    font-size: .68rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    background: #ef4444;
    color: #fff;
}
.dss-insights-badge.ok { background: rgba(22,163,74,.15); color: #16a34a; }
</style>
@endonce

<details class="dss-insights" {{ $totalIssues > 0 ? 'open' : '' }}>
    <summary>
        <span>
            Scheduling insights
            <div class="dss-insights-hint">Decision support from live schedule and load data</div>
        </span>
        @if($totalIssues > 0)
            <span class="dss-insights-badge">{{ $totalIssues }} issue{{ $totalIssues === 1 ? '' : 's' }}</span>
        @else
            <span class="dss-insights-badge ok">All clear</span>
        @endif
    </summary>
    <div class="dss-insights-body">
        <div class="dss-insights-grid">
            <a href="{{ $routes['class_schedule'] ?? '#' }}" class="dss-insight-card {{ $facultyConflicts > 0 ? 'is-alert' : 'is-ok' }}">
                <div class="dss-insight-val">{{ $facultyConflicts }}</div>
                <div class="dss-insight-lbl">Faculty conflicts</div>
            </a>
            <a href="{{ $routes['class_schedule'] ?? '#' }}" class="dss-insight-card {{ $roomConflicts > 0 ? 'is-alert' : 'is-ok' }}">
                <div class="dss-insight-val">{{ $roomConflicts }}</div>
                <div class="dss-insight-lbl">Room conflicts</div>
            </a>
            <a href="{{ $routes['faculty_loading'] ?? '#' }}" class="dss-insight-card {{ $sharedIssues > 0 ? 'is-warn' : 'is-ok' }}">
                <div class="dss-insight-val">{{ $sharedIssues }}</div>
                <div class="dss-insight-lbl">Shared-teacher issues</div>
            </a>
            <a href="{{ $routes['create_schedule'] ?? ($routes['class_schedule'] ?? '#') }}" class="dss-insight-card {{ $missingDate > 0 ? 'is-warn' : 'is-ok' }}">
                <div class="dss-insight-val">{{ $missingDate }}</div>
                <div class="dss-insight-lbl">Missing schedule date</div>
            </a>
            <a href="{{ $routes['class_schedule'] ?? '#' }}" class="dss-insight-card {{ $missingRoom > 0 ? 'is-warn' : 'is-ok' }}">
                <div class="dss-insight-val">{{ $missingRoom }}</div>
                <div class="dss-insight-lbl">Missing room</div>
            </a>
            <a href="{{ $routes['class_schedule'] ?? '#' }}" class="dss-insight-card {{ $totalIssues > 0 ? 'is-alert' : 'is-ok' }}" title="Review in class schedule">
                <div class="dss-insight-val">{{ $totalIssues }}</div>
                <div class="dss-insight-lbl">Total issues</div>
            </a>
        </div>
        @if(($stReqPending ?? 0) > 0 && !empty($routes['requests']))
            <p style="margin-top:.85rem;font-size:.78rem;color:var(--text-secondary);">
                <a href="{{ $routes['requests'] }}" style="color:var(--green-primary);font-weight:600;text-decoration:none;">
                    {{ $stReqPending }} pending teacher request{{ $stReqPending === 1 ? '' : 's' }}
                </a>
                — review on All Requests
            </p>
        @endif
    </div>
</details>
