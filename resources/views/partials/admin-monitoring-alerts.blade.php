{{-- Descriptive DSS: conflicts, workload fairness, utilization heatmaps. --}}
@php
    $m = $monitoring ?? [];
    $summary = $m['summary'] ?? [];
    $facultyConflicts = $m['faculty_conflicts'] ?? [];
    $roomConflicts = $m['room_conflicts'] ?? [];
    $missing = $m['missing_data'] ?? [];
    $sharedOverload = $m['shared_overload'] ?? [];
    $workload = $m['workload'] ?? [];
    $teachersWl = $workload['teachers'] ?? [];
    $deptAvg = (float) ($workload['dept_average'] ?? 0);
    $maxWlHours = max(1, ...array_map(fn ($t) => (float) ($t['load_hours'] ?? 0), $teachersWl ?: [['load_hours' => 1]]));
    $teacherHeat = $m['teacher_heatmap'] ?? ['columns' => [], 'rows' => []];
    $roomHeat = $m['room_heatmap'] ?? ['columns' => [], 'rows' => []];
    $scheduleUrl = route($scheduleRoute ?? 'admin.class-schedule');
@endphp

@once
<style>
.mon-page { --mon-danger: #dc2626; --mon-warn: #d97706; --mon-ok: #16a34a; --mon-muted: #94a3b8; }
.mon-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 1rem; margin-bottom: 1.75rem; }
.mon-card { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: .75rem; padding: 1rem 1.1rem; }
.mon-card.danger { border-color: rgba(220,38,38,.35); background: linear-gradient(135deg, rgba(220,38,38,.08), transparent); }
.mon-card.warn { border-color: rgba(217,119,6,.35); }
.mon-card.ok { border-color: rgba(22,163,74,.25); }
.mon-card-val { font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
.mon-card-lbl { font-size: .78rem; color: var(--text-secondary); margin-top: .35rem; }
.mon-section { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: .75rem; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
.mon-section h2 { font-size: 1.05rem; font-weight: 700; margin: 0 0 .35rem; }
.mon-section .sub { font-size: .82rem; color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.45; }
.mon-tabs { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
.mon-tab { padding: .4rem .85rem; border-radius: 9999px; border: 1px solid var(--border-color); background: var(--bg-tertiary); font-size: .78rem; font-weight: 600; cursor: pointer; color: var(--text-secondary); }
.mon-tab.active { background: var(--green-primary); color: #fff; border-color: var(--green-primary); }
.mon-panel { display: none; }
.mon-panel.active { display: block; }
.mon-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
.mon-table th, .mon-table td { padding: .55rem .65rem; text-align: left; border-bottom: 1px solid var(--border-color); }
.mon-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--text-secondary); background: var(--bg-tertiary); }
.mon-table tr:hover td { background: rgba(45,122,80,.04); }
.mon-badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: .68rem; font-weight: 700; }
.mon-badge.danger { background: rgba(220,38,38,.12); color: var(--mon-danger); }
.mon-badge.warn { background: rgba(217,119,6,.12); color: var(--mon-warn); }
.mon-badge.ok { background: rgba(22,163,74,.12); color: var(--mon-ok); }
.mon-drill { margin-top: .5rem; padding: .65rem .75rem; background: var(--bg-tertiary); border-radius: .5rem; font-size: .76rem; }
.mon-drill summary { cursor: pointer; font-weight: 600; color: var(--green-primary); }
.mon-link { color: var(--green-primary); font-weight: 600; text-decoration: none; font-size: .76rem; }
.mon-link:hover { text-decoration: underline; }
.mon-chart { display: flex; flex-direction: column; gap: .45rem; max-height: 420px; overflow-y: auto; padding-right: .25rem; }
.mon-bar-row { display: grid; grid-template-columns: 120px 1fr 52px; gap: .5rem; align-items: center; font-size: .76rem; }
.mon-bar-track { height: 18px; background: var(--bg-tertiary); border-radius: 4px; position: relative; overflow: hidden; }
.mon-bar-fill { height: 100%; border-radius: 4px; background: var(--green-primary); min-width: 2px; }
.mon-bar-fill.outlier-high { background: #dc2626; }
.mon-bar-fill.outlier-low { background: #2563eb; }
.mon-bar-avg { position: absolute; top: 0; bottom: 0; width: 2px; background: #f0c040; z-index: 2; }
.mon-heat-wrap { overflow-x: auto; margin-top: .75rem; }
.mon-heat { border-collapse: collapse; font-size: .62rem; }
.mon-heat th, .mon-heat td { border: 1px solid var(--border-color); padding: 2px; min-width: 14px; height: 14px; }
.mon-heat th { background: var(--bg-tertiary); font-weight: 600; writing-mode: vertical-rl; text-orientation: mixed; height: 72px; max-width: 28px; font-size: .58rem; color: var(--text-secondary); }
.mon-heat .lbl { writing-mode: horizontal-tb; text-align: left; min-width: 100px; max-width: 120px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: .7rem; padding: 4px 6px; }
.mon-heat .cell-empty { background: #e2e8f0; }
.mon-heat .cell-full { background: #16a34a; }
html[data-theme="dark"] .mon-heat .cell-empty { background: #404040; }
html[data-theme="dark"] .mon-heat .cell-full { background: #4a9d6f; }
.mon-empty { text-align: center; padding: 1.5rem; color: var(--text-secondary); font-size: .85rem; }
.str-leave-sections-hint { color: var(--mon-warn); font-style: normal; }
</style>
@endonce

<div class="mon-page">
    <div class="mon-summary">
        <div class="mon-card {{ ($summary['faculty_conflicts'] ?? 0) > 0 ? 'danger' : 'ok' }}">
            <div class="mon-card-val" style="{{ ($summary['faculty_conflicts'] ?? 0) > 0 ? 'color:var(--mon-danger)' : '' }}">{{ $summary['faculty_conflicts'] ?? 0 }}</div>
            <div class="mon-card-lbl">Faculty time conflicts</div>
        </div>
        <div class="mon-card {{ ($summary['room_conflicts'] ?? 0) > 0 ? 'danger' : 'ok' }}">
            <div class="mon-card-val" style="{{ ($summary['room_conflicts'] ?? 0) > 0 ? 'color:var(--mon-danger)' : '' }}">{{ $summary['room_conflicts'] ?? 0 }}</div>
            <div class="mon-card-lbl">Room conflicts</div>
        </div>
        <div class="mon-card {{ ($summary['shared_overload'] ?? 0) + ($summary['shared_cross_conflicts'] ?? 0) > 0 ? 'warn' : 'ok' }}">
            <div class="mon-card-val">{{ ($summary['shared_overload'] ?? 0) + ($summary['shared_cross_conflicts'] ?? 0) }}</div>
            <div class="mon-card-lbl">Shared-teacher issues</div>
        </div>
        <div class="mon-card {{ ($summary['missing_room'] ?? 0) + ($summary['missing_schedule_date'] ?? 0) > 0 ? 'warn' : 'ok' }}">
            <div class="mon-card-val">{{ ($summary['missing_room'] ?? 0) + ($summary['missing_schedule_date'] ?? 0) }}</div>
            <div class="mon-card-lbl">Missing room / date</div>
        </div>
    </div>

    {{-- Conflict command center --}}
    <section class="mon-section" id="mon-conflicts">
        <h2>Conflict command center</h2>
        <p class="sub">Faculty double-booking, room overlaps, shared-teacher cross-school clashes, and schedules missing room or date. Expand a row to see affected schedule entries.</p>

        <div class="mon-tabs" role="tablist">
            <button type="button" class="mon-tab active" data-mon-tab="faculty">Faculty ({{ count($facultyConflicts) }})</button>
            <button type="button" class="mon-tab" data-mon-tab="room">Rooms ({{ count($roomConflicts) }})</button>
            <button type="button" class="mon-tab" data-mon-tab="shared">Shared teachers ({{ count($sharedOverload) }})</button>
            <button type="button" class="mon-tab" data-mon-tab="missing">Data quality ({{ count($missing['missing_room'] ?? []) + count($missing['missing_schedule_date'] ?? []) }})</button>
        </div>

        <div class="mon-panel active" data-mon-panel="faculty">
            @if(count($facultyConflicts) === 0)
                <div class="mon-empty">No faculty time conflicts among approved active schedules.</div>
            @else
                @foreach($facultyConflicts as $group)
                    <details class="mon-drill">
                        <summary>{{ $group['faculty_name'] }} — {{ $group['day'] }} · {{ $group['time'] }}</summary>
                        <table class="mon-table" style="margin-top:.5rem;">
                            <thead><tr><th>ID</th><th>Subject</th><th>Grade</th><th>Section</th><th>Room</th><th>Time</th><th></th></tr></thead>
                            <tbody>
                                @foreach($group['rows'] ?? [] as $row)
                                    <tr>
                                        <td>#{{ $row['id'] }}</td>
                                        <td>{{ $row['subject'] }}</td>
                                        <td>{{ $row['grade_level'] }}</td>
                                        <td>{{ $row['section_name'] }}</td>
                                        <td>{{ $row['room'] }}</td>
                                        <td>{{ $row['time'] }}</td>
                                        <td><a class="mon-link" href="{{ $scheduleUrl }}?highlight={{ $row['id'] }}">Open schedule</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                @endforeach
            @endif
        </div>

        <div class="mon-panel" data-mon-panel="room">
            @if(count($roomConflicts) === 0)
                <div class="mon-empty">No room double-bookings detected.</div>
            @else
                @foreach($roomConflicts as $group)
                    <details class="mon-drill">
                        <summary>{{ $group['room_name'] }} — {{ $group['day'] }} · {{ $group['time'] }}</summary>
                        <table class="mon-table" style="margin-top:.5rem;">
                            <thead><tr><th>ID</th><th>Subject</th><th>Teacher</th><th>Section</th><th>Time</th><th></th></tr></thead>
                            <tbody>
                                @foreach($group['rows'] ?? [] as $row)
                                    <tr>
                                        <td>#{{ $row['id'] }}</td>
                                        <td>{{ $row['subject'] }}</td>
                                        <td>{{ $row['faculty_name'] }}</td>
                                        <td>{{ $row['grade_level'] }} {{ $row['section_name'] }}</td>
                                        <td>{{ $row['time'] }}</td>
                                        <td><a class="mon-link" href="{{ $scheduleUrl }}?highlight={{ $row['id'] }}">Open schedule</a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                @endforeach
            @endif
        </div>

        <div class="mon-panel" data-mon-panel="shared">
            @if(count($sharedOverload) === 0)
                <div class="mon-empty">No shared teachers configured.</div>
            @else
                <table class="mon-table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>JH hrs</th>
                            <th>GS hrs</th>
                            <th>Total</th>
                            <th>Load status</th>
                            <th>Cross-school clashes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sharedOverload as $st)
                            <tr>
                                <td><strong>{{ $st['name'] }}</strong></td>
                                <td>{{ $st['jh_hours'] }}</td>
                                <td>{{ $st['gs_hours'] }}</td>
                                <td>{{ $st['total_hours'] }} / {{ $st['max_hours'] }}</td>
                                <td>
                                    @if(($st['status'] ?? '') === 'overloaded')
                                        <span class="mon-badge danger">Overloaded</span>
                                    @elseif(($st['status'] ?? '') === 'near_limit')
                                        <span class="mon-badge warn">Near limit</span>
                                    @else
                                        <span class="mon-badge ok">OK</span>
                                    @endif
                                </td>
                                <td>
                                    @if(count($st['conflicts'] ?? []) > 0)
                                        <details>
                                            <summary class="mon-link" style="cursor:pointer;">{{ count($st['conflicts']) }} clash(es)</summary>
                                            <ul style="margin:.35rem 0 0 1rem;font-size:.74rem;">
                                                @foreach($st['conflicts'] as $c)
                                                    <li>{{ $c['day'] }}: JH {{ $c['jh'] }} ↔ GS {{ $c['gs'] }}</li>
                                                @endforeach
                                            </ul>
                                        </details>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="mon-panel" data-mon-panel="missing">
            @php
                $missingDate = $missing['missing_schedule_date'] ?? [];
                $missingRoom = $missing['missing_room'] ?? [];
            @endphp
            @if(count($missingDate) === 0 && count($missingRoom) === 0)
                <div class="mon-empty">All active schedules have room and date assigned.</div>
            @else
                @if(count($missingDate) > 0)
                    <h3 style="font-size:.88rem;margin:.5rem 0;">Missing schedule date ({{ count($missingDate) }})</h3>
                    @include('partials.admin-monitoring-missing-table', ['rows' => $missingDate, 'scheduleUrl' => $scheduleUrl])
                @endif
                @if(count($missingRoom) > 0)
                    <h3 style="font-size:.88rem;margin:1rem 0 .5rem;">Missing room ({{ count($missingRoom) }})</h3>
                    @include('partials.admin-monitoring-missing-table', ['rows' => $missingRoom, 'scheduleUrl' => $scheduleUrl])
                @endif
            @endif
        </div>
    </section>

    {{-- Workload fairness --}}
    <section class="mon-section">
        <h2>Workload fairness</h2>
        <p class="sub">Load hours per teacher from faculty loads vs. department average ({{ number_format($deptAvg, 1) }} hrs). Outliers exceed ±{{ number_format($workload['outlier_threshold'] ?? 0, 1) }} hrs from average.</p>
        @if(count($teachersWl) === 0)
            <div class="mon-empty">No faculty load records yet.</div>
        @else
            <div class="mon-chart">
                @foreach($teachersWl as $t)
                    @php
                        $hrs = (float) ($t['load_hours'] ?? 0);
                        $pct = min(100, ($hrs / $maxWlHours) * 100);
                        $avgPct = min(100, ($deptAvg / $maxWlHours) * 100);
                        $barClass = !empty($t['is_outlier']) ? ('outlier-' . ($t['outlier_type'] ?? 'high')) : '';
                    @endphp
                    <div class="mon-bar-row">
                        <span title="{{ $t['name'] }}">{{ Str::limit($t['name'], 16) }}{{ !empty($t['is_outlier']) ? ' ⚠' : '' }}</span>
                        <div class="mon-bar-track">
                            <div class="mon-bar-avg" style="left: {{ $avgPct }}%;"></div>
                            <div class="mon-bar-fill {{ $barClass }}" style="width: {{ $pct }}%;"></div>
                        </div>
                        <span style="font-weight:600;text-align:right;">{{ number_format($hrs, 1) }}</span>
                    </div>
                @endforeach
            </div>
            <p style="font-size:.72rem;color:var(--text-secondary);margin-top:.75rem;">
                <span style="display:inline-block;width:12px;height:12px;background:#f0c040;vertical-align:middle;border-radius:2px;"></span> Dept. average
                · <span style="display:inline-block;width:12px;height:12px;background:#dc2626;vertical-align:middle;border-radius:2px;"></span> High outlier
                · <span style="display:inline-block;width:12px;height:12px;background:#2563eb;vertical-align:middle;border-radius:2px;"></span> Low outlier
            </p>
        @endif
    </section>

    {{-- Teacher utilization heatmap --}}
    <section class="mon-section">
        <h2>Teacher utilization</h2>
        <p class="sub">Teacher × day × period grid (green = class scheduled, gray = empty). Showing up to 20 teachers with assignments.</p>
        @if(count($teacherHeat['rows'] ?? []) === 0)
            <div class="mon-empty">No teacher schedules to display.</div>
        @else
            <div class="mon-heat-wrap">
                <table class="mon-heat">
                    <thead>
                        <tr>
                            <th class="lbl">Teacher</th>
                            @foreach($teacherHeat['columns'] ?? [] as $col)
                                <th title="{{ $col['label'] }}">{{ Str::limit($col['label'], 8, '') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teacherHeat['rows'] as $row)
                            <tr>
                                <td class="lbl" title="{{ $row['name'] }}">{{ Str::limit($row['name'], 14) }}</td>
                                @foreach($teacherHeat['columns'] as $col)
                                    @php $state = $row['cells'][$col['key']] ?? 'empty'; @endphp
                                    <td class="cell-{{ $state }}" title="{{ $row['name'] }} · {{ $col['label'] }}"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Room utilization heatmap --}}
    <section class="mon-section">
        <h2>Room utilization</h2>
        <p class="sub">Room × day × period — occupancy across the official class periods (up to 15 rooms).</p>
        @if(count($roomHeat['rows'] ?? []) === 0)
            <div class="mon-empty">No room assignments to display.</div>
        @else
            <div class="mon-heat-wrap">
                <table class="mon-heat">
                    <thead>
                        <tr>
                            <th class="lbl">Room</th>
                            @foreach($roomHeat['columns'] ?? [] as $col)
                                <th title="{{ $col['label'] }}">{{ Str::limit($col['label'], 8, '') }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roomHeat['rows'] as $row)
                            <tr>
                                <td class="lbl">{{ $row['name'] }}</td>
                                @foreach($roomHeat['columns'] as $col)
                                    @php $state = $row['cells'][$col['key']] ?? 'empty'; @endphp
                                    <td class="cell-{{ $state }}" title="{{ $row['name'] }} · {{ $col['label'] }}"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

<script>
document.querySelectorAll('[data-mon-tab]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = btn.getAttribute('data-mon-tab');
        document.querySelectorAll('[data-mon-tab]').forEach(function(b) { b.classList.toggle('active', b === btn); });
        document.querySelectorAll('[data-mon-panel]').forEach(function(p) {
            p.classList.toggle('active', p.getAttribute('data-mon-panel') === id);
        });
    });
});
</script>
