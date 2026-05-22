{{-- resources/views/principal/system-logs.blade.php --}}
@extends('layouts.principal')

@section('title', 'System Logs')

@section('content')
<div class="header">
    <div class="header-left">
        <div>
            <h1 class="page-title">System Logs</h1>
            <p class="page-subtitle">Cross-level audit trail — all schedule and user changes from Junior High &amp; Grade School</p>
        </div>
    </div>
    <div class="header-right" style="gap:0.5rem;">
        <a href="{{ route('principal.teacher-logs.jh') }}" class="btn btn-outline btn-sm">Teacher Logs (JH)</a>
        <a href="{{ route('principal.teacher-logs.gs') }}" class="btn btn-outline btn-sm">Teacher Logs (GS)</a>
    </div>
</div>

{{-- Filter bar --}}
<div class="card" style="margin-bottom:1.25rem;">
    <div class="card-body" style="padding:.75rem 1.25rem;display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
        <input type="text" id="log-search" placeholder="Search by user, table, action, details…"
               class="form-control" style="max-width:320px;padding:.4rem .75rem;font-size:.875rem;">
        <select id="school-filter" class="form-control" style="max-width:160px;padding:.4rem .75rem;font-size:.875rem;">
            <option value="">All Schools</option>
            <option value="Junior High">Junior High</option>
            <option value="Grade School">Grade School</option>
        </select>
        <select id="action-filter" class="form-control" style="max-width:140px;padding:.4rem .75rem;font-size:.875rem;">
            <option value="">All Actions</option>
            <option value="INSERT">INSERT</option>
            <option value="UPDATE">UPDATE</option>
            <option value="DELETE">DELETE</option>
        </select>
        <span id="log-count" style="font-size:.8rem;color:var(--text-secondary);margin-left:auto;">
            Showing <strong id="visible-count">{{ count($logs) }}</strong> of {{ count($logs) }} entries
        </span>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;overflow-x:auto;">
        @if($logs->isEmpty())
            <div style="padding:3rem;text-align:center;color:var(--text-secondary);">
                No audit log entries found. Admin actions on schedules, users, and rooms will appear here.
            </div>
        @else
        <table id="logs-table" style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr style="background:rgba(26,58,92,.06);">
                    <th style="padding:.6rem 1rem;text-align:left;font-weight:600;white-space:nowrap;">Timestamp</th>
                    <th style="padding:.6rem .75rem;text-align:left;font-weight:600;">School</th>
                    <th style="padding:.6rem .75rem;text-align:left;font-weight:600;">User / Admin</th>
                    <th style="padding:.6rem .75rem;text-align:left;font-weight:600;">Table</th>
                    <th style="padding:.6rem .75rem;text-align:center;font-weight:600;">Action</th>
                    <th style="padding:.6rem .75rem;text-align:center;font-weight:600;">Record</th>
                    <th style="padding:.6rem 1rem;text-align:left;font-weight:600;">Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr class="log-row" data-school="{{ $log['school'] }}" data-action="{{ $log['action'] }}"
                    style="border-bottom:1px solid rgba(0,0,0,.06);">
                    <td style="padding:.55rem 1rem;white-space:nowrap;color:var(--text-secondary);">
                        {{ \Carbon\Carbon::parse($log['timestamp'])->format('M d, Y H:i:s') }}
                    </td>
                    <td style="padding:.55rem .75rem;">
                        <span style="display:inline-block;padding:.15rem .5rem;border-radius:.3rem;font-size:.75rem;font-weight:600;
                            background:{{ $log['school'] === 'Junior High' ? 'rgba(26,58,92,.1)' : 'rgba(16,122,60,.1)' }};
                            color:{{ $log['school'] === 'Junior High' ? '#1a3a5c' : '#107a3c' }};">
                            {{ $log['school'] === 'Junior High' ? 'JH' : 'GS' }}
                        </span>
                    </td>
                    <td style="padding:.55rem .75rem;font-weight:500;">{{ $log['user'] }}</td>
                    <td style="padding:.55rem .75rem;color:var(--text-secondary);">{{ $log['table'] }}</td>
                    <td style="padding:.55rem .75rem;text-align:center;">
                        <span class="badge {{ $log['level_class'] }}"
                              style="font-size:.7rem;padding:.2rem .5rem;border-radius:.25rem;
                                background:{{ $log['action']==='DELETE' ? '#fef2f2' : ($log['action']==='UPDATE' ? '#fffbeb' : '#f0fdf4') }};
                                color:{{ $log['action']==='DELETE' ? '#b91c1c' : ($log['action']==='UPDATE' ? '#92400e' : '#166534') }};
                                border:1px solid {{ $log['action']==='DELETE' ? '#fca5a5' : ($log['action']==='UPDATE' ? '#fde68a' : '#86efac') }};">
                            {{ $log['action'] }}
                        </span>
                    </td>
                    <td style="padding:.55rem .75rem;text-align:center;color:var(--text-secondary);">{{ $log['record'] }}</td>
                    <td style="padding:.55rem 1rem;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                        title="{{ $log['details'] }}">
                        {{ $log['details'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<script>
(function () {
    var searchEl  = document.getElementById('log-search');
    var schoolEl  = document.getElementById('school-filter');
    var actionEl  = document.getElementById('action-filter');
    var countEl   = document.getElementById('visible-count');
    var rows      = Array.from(document.querySelectorAll('.log-row'));
    var total     = rows.length;

    function applyFilters() {
        var q      = (searchEl.value || '').toLowerCase();
        var school = schoolEl.value;
        var action = actionEl.value;
        var visible = 0;

        rows.forEach(function (row) {
            var matchSchool = !school || row.dataset.school === school;
            var matchAction = !action || row.dataset.action === action;
            var matchSearch = !q || row.textContent.toLowerCase().includes(q);
            var show = matchSchool && matchAction && matchSearch;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (countEl) countEl.textContent = visible;
    }

    if (searchEl) searchEl.addEventListener('input', applyFilters);
    if (schoolEl) schoolEl.addEventListener('change', applyFilters);
    if (actionEl) actionEl.addEventListener('change', applyFilters);
})();
</script>
@endsection
