@extends('layouts.admin')

@section('title', 'System Logs')

@section('content')
    <style>
        .table-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .table-header { padding: 1.5rem; border-bottom: 1px solid #e8dcc8; }
        .table-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; }
        .helper-text { font-size: 0.875rem; color: #7a7a6e; }
        .error-banner { margin-bottom: 1rem; padding: 0.875rem 1rem; background: #fee2e2; border: 1px solid #fecaca; border-radius: 0.5rem; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 1.5rem; background: #f5f3ed; text-align: left; font-weight: 600; color: #2d3436; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; vertical-align: top; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; vertical-align: top; }
        tr:hover { background: #fafaf8; }
        .details-cell { white-space: normal; line-height: 1.45; max-width: 34rem; }
        .log-level { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .level-info { background: #dbeafe; color: #0c4a6e; }
        .level-warning { background: #fef3c7; color: #92400e; }
        .level-error { background: #fee2e2; color: #b91c1c; }

        html[data-theme="dark"] .table-card {
            background: var(--bg-secondary) !important;
            border-color: var(--border-color) !important;
        }
        html[data-theme="dark"] .table-header { border-color: var(--border-color) !important; }
        html[data-theme="dark"] .table-title { color: var(--text-primary) !important; }
        html[data-theme="dark"] .helper-text { color: var(--text-secondary) !important; }
        html[data-theme="dark"] .page-title { color: var(--text-primary) !important; }
        html[data-theme="dark"] th {
            background: var(--bg-primary) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }
        html[data-theme="dark"] td {
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }
        html[data-theme="dark"] tr:hover { background: var(--bg-tertiary) !important; }
        html[data-theme="dark"] .level-info { background: rgba(96,165,250,0.15); color: #93c5fd; }
        html[data-theme="dark"] .level-warning { background: rgba(251,191,36,0.15); color: #fbbf24; }
        html[data-theme="dark"] .level-error { background: rgba(239,68,68,0.15); color: #f87171; }
    </style>

    <div class="header">
        <div class="header-left">
            <h1 class="page-title">System Logs</h1>
        </div>
        <div class="header-right">
            <span class="helper-text">Showing latest {{ $logs->count() }} entries</span>
        </div>
    </div>

    @if (!empty($error))
        <div class="error-banner">{{ $error }}</div>
    @endif

    <div class="table-card">
        <div class="table-header">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;">
                <div class="table-title">Audit Log Entries</div>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Record</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($log['timestamp'])->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log['user'] }}</td>
                            <td>{{ $log['table'] }}</td>
                            <td><span class="log-level {{ $log['level_class'] }}">{{ $log['action'] }}</span></td>
                            <td>{{ $log['record'] }}</td>
                            <td class="details-cell">{!! nl2br(e($log['details'])) !!}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:2rem;color:#7a7a6e;">No audit log entries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
