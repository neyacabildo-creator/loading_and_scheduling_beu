{{-- resources/views/principal/dashboard.blade.php --}}
@extends('layouts.principal')

@section('title', 'Principal Dashboard')

@section('content')
<div class="header">
    <div class="header-left">
        <div>
            <h1 class="page-title">Principal Dashboard</h1>
            <p class="page-subtitle">Full system overview — both school levels</p>
        </div>
    </div>
    <div class="header-right">
        @if($pendingRequests > 0)
            <a href="{{ route('principal.permission-requests') }}" class="btn btn-primary" style="background:#ef4444;border-color:#ef4444;">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                {{ $pendingRequests }} Pending Request{{ $pendingRequests != 1 ? 's' : '' }}
            </a>
        @endif
        <span style="font-size:0.8rem;color:var(--text-secondary);">{{ now()->format('l, F j, Y') }}</span>
    </div>
</div>

{{-- Permission matrix notice --}}
<div style="background:rgba(26,58,92,0.06);border:1px solid rgba(26,58,92,0.2);border-radius:0.75rem;padding:1rem 1.25rem;margin-bottom:1.75rem;font-size:0.875rem;color:#1a3a5c;">
    <strong>Principal Privileges:</strong> You have full read/write access across both Grade School and Junior High. Regular admins are limited to their own school level and must request your approval for sensitive operations.
</div>

{{-- Stats --}}
<div class="stats-grid">
    <div class="stat-card accent">
        <div class="stat-value">{{ $totalFaculty }}</div>
        <div class="stat-label">Active Faculty</div>
        <div class="stat-sub">JH: {{ $jhFaculty }} &nbsp;|&nbsp; GS: {{ $gsFaculty }}</div>
    </div>
    <div class="stat-card info">
        <div class="stat-value">{{ $totalAdmins }}</div>
        <div class="stat-label">Active Admins</div>
        <div class="stat-sub">JH: {{ $jhAdmins }} &nbsp;|&nbsp; GS: {{ $gsAdmins }}</div>
    </div>
    <div class="stat-card {{ $pendingRequests > 0 ? 'warning' : '' }}">
        <div class="stat-value">{{ $pendingRequests }}</div>
        <div class="stat-label">Pending Admin Requests</div>
        <div class="stat-sub">Awaiting your review</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value">{{ $gsSchedules }}</div>
        <div class="stat-label">GS Schedules</div>
        <div class="stat-sub">Grade School total</div>
    </div>
    <div class="stat-card success">
        <div class="stat-value">{{ $jhSchedules }}</div>
        <div class="stat-label">JH Schedules</div>
        <div class="stat-sub">Junior High total</div>
    </div>
    <div class="stat-card {{ $pendingSchedules > 0 ? 'warning' : '' }}" style="cursor:pointer;" onclick="window.location='{{ route('principal.schedule-approvals') }}'" title="Review schedules awaiting principal approval">
        <div class="stat-value">{{ $pendingSchedules }}</div>
        <div class="stat-label">Awaiting Your Approval</div>
        <div class="stat-sub">Admin-approved, needs principal review
            @if(($principalScheduleFlags['with_policy_flags'] ?? 0) > 0)
                <span style="display:inline-block;margin-left:.35rem;background:#fef3c7;color:#92400e;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:9999px;">
                    {{ $principalScheduleFlags['with_policy_flags'] }} with policy flags
                </span>
            @endif
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $totalUsers }}</div>
        <div class="stat-label">Total Accounts</div>
        <div class="stat-sub">{{ $inactiveUsers }} inactive</div>
    </div>
    <div class="stat-card">
        <div class="stat-value">{{ $totalRooms }}</div>
        <div class="stat-label">Rooms</div>
        <div class="stat-sub">JH: {{ $jhRooms }} &nbsp;|&nbsp; GS: {{ $gsRooms }}</div>
    </div>
</div>

{{-- Rooms Overview --}}
<div class="card" style="margin-bottom:1.75rem;">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <span class="card-title">Rooms Overview</span>
        <div style="display:flex;gap:.75rem;align-items:center;">
            <label style="font-size:.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:.4rem;">
                <input type="radio" name="rooms_filter" value="all" checked onchange="filterRooms(this.value)" style="accent-color:#1a3a5c;"> All
            </label>
            <label style="font-size:.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:.4rem;">
                <input type="radio" name="rooms_filter" value="Junior High" onchange="filterRooms(this.value)" style="accent-color:#1a3a5c;"> Junior High
            </label>
            <label style="font-size:.8rem;color:var(--text-secondary);display:flex;align-items:center;gap:.4rem;">
                <input type="radio" name="rooms_filter" value="Grade School" onchange="filterRooms(this.value)" style="accent-color:#1a3a5c;"> Grade School
            </label>
        </div>
    </div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        @if($roomsData->isEmpty())
            <div style="padding:2rem;text-align:center;color:var(--text-secondary);font-size:.875rem;">No rooms found in either school database.</div>
        @else
            <table id="rooms-table">
                <thead>
                    <tr>
                        <th>Room No.</th>
                        <th>Building</th>
                        <th>Capacity</th>
                        <th>Features</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">School</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roomsData as $room)
                    @php
                        $cap = $room['capacity'] ?? 0;
                        $capClass = $cap >= 40 ? 'large' : ($cap >= 30 ? 'medium' : 'small');
                        $capColor = $capClass === 'large' ? '#166534' : ($capClass === 'medium' ? '#1e40af' : '#92400e');
                        $capBg    = $capClass === 'large' ? 'rgba(34,197,94,.12)' : ($capClass === 'medium' ? 'rgba(59,130,246,.12)' : 'rgba(245,158,11,.12)');
                        $status   = strtolower($room['status'] ?? 'available');
                        $statusColor = $status === 'available' ? '#166534' : '#991b1b';
                        $statusBg    = $status === 'available' ? 'rgba(34,197,94,.1)' : 'rgba(239,68,68,.1)';
                        $school   = $room['school'] ?? '';
                        $schoolColor = $school === 'Junior High' ? '#1a3a5c' : '#107a3c';
                        $schoolBg    = $school === 'Junior High' ? 'rgba(26,58,92,.1)' : 'rgba(16,122,60,.1)';
                    @endphp
                    <tr data-school="{{ $room['school'] ?? '' }}">
                        <td style="font-weight:600;">{{ $room['room_number'] ?? '—' }}</td>
                        <td>{{ $room['building'] ?? '—' }}</td>
                        <td>
                            <span style="padding:.2rem .6rem;border-radius:9999px;font-size:.72rem;font-weight:700;background:{{ $capBg }};color:{{ $capColor }};">
                                {{ $cap > 0 ? $cap : '—' }}
                            </span>
                        </td>
                        <td style="font-size:.8rem;color:var(--text-secondary);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $room['features'] ?? '' }}">
                            {{ $room['features'] ? implode(', ', array_map('trim', explode(',', $room['features']))) : '—' }}
                        </td>
                        <td style="text-align:center;">
                            <span style="padding:.2rem .65rem;border-radius:9999px;font-size:.72rem;font-weight:600;background:{{ $statusBg }};color:{{ $statusColor }};">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <span style="padding:.15rem .5rem;border-radius:.25rem;font-size:.72rem;font-weight:700;background:{{ $schoolBg }};color:{{ $schoolColor }};">
                                {{ $school === 'Junior High' ? 'JH' : 'GS' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
<script>
function filterRooms(val) {
    document.querySelectorAll('#rooms-table tbody tr').forEach(function(row) {
        row.style.display = (val === 'all' || row.dataset.school === val) ? '' : 'none';
    });
}
</script>

{{-- Permission Matrix --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.75rem;">

    <div class="card">
        <div class="card-header">
            <span class="card-title">Permission Matrix</span>
        </div>
        <div class="card-body" style="padding:0;">
            <table>
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th style="text-align:center;">Admin</th>
                        <th style="text-align:center;">Principal</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $matrix = [
                        ['Manage own-level schedules',      true,  true],
                        ['Manage ALL school-level schedules',false, true],
                        ['Approve / reject schedules',      true,  true],
                        ['Override any admin decision',     false, true],
                        ['Manage teachers (own level)',     true,  true],
                        ['Manage ALL users (all levels)',   false, true],
                        ['Create / edit admin accounts',    false, true],
                        ['View own-level system logs',      true,  true],
                        ['View ALL system logs',            false, true],
                        ['Generate own-level reports',      true,  true],
                        ['Generate institution-wide reports',false,true],
                        ['Send permission requests',        true,  false],
                        ['Approve / reject admin requests', false, true],
                        ['Manage roles',                    false, true],
                    ];
                    @endphp
                    @foreach($matrix as [$feature, $admin, $principal])
                    <tr>
                        <td>{{ $feature }}</td>
                        <td style="text-align:center;">
                            @if($admin)
                                <span style="color:#22c55e;">&#10003;</span>
                            @else
                                <span style="color:#d1d5db;">&#8212;</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            @if($principal)
                                <span style="color:#22c55e;font-weight:700;">&#10003;</span>
                            @else
                                <span style="color:#d1d5db;">&#8212;</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Recent Admin Requests</span>
            <a href="{{ route('principal.permission-requests') }}" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding:0;">
            @if($recentRequests->isEmpty())
                <div style="padding:2rem;text-align:center;color:var(--text-secondary);font-size:0.875rem;">
                    No requests yet.
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRequests as $req)
                        <tr>
                            <td>{{ $req->requester?->first_name ?? $req->requester?->name }}</td>
                            <td>{{ $req->actionLabel() }}</td>
                            <td>
                                <span class="badge badge-{{ $req->status }}">{{ ucfirst($req->status) }}</span>
                            </td>
                            <td style="color:var(--text-secondary);font-size:0.8rem;">{{ $req->created_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>

{{-- Recent System Activity (combined JH + GS) --}}
@if($recentActivity->isNotEmpty())
<div class="card" style="margin-bottom:1.75rem;">
    <div class="card-header">
        <span class="card-title">Recent System Activity</span>
        <a href="{{ route('principal.system-logs') }}" class="btn btn-outline btn-sm">View All Logs</a>
    </div>
    <div class="card-body" style="padding:0;overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr style="background:rgba(26,58,92,.06);">
                    <th style="padding:.5rem 1rem;text-align:left;font-weight:600;white-space:nowrap;">Time</th>
                    <th style="padding:.5rem .75rem;text-align:left;font-weight:600;">School</th>
                    <th style="padding:.5rem .75rem;text-align:left;font-weight:600;">Table</th>
                    <th style="padding:.5rem .75rem;text-align:center;font-weight:600;">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentActivity as $entry)
                @php
                    $action = strtoupper((string)($entry['action'] ?? ''));
                    $actionColor = $action === 'DELETE' ? '#b91c1c' : ($action === 'UPDATE' ? '#92400e' : '#166534');
                    $actionBg    = $action === 'DELETE' ? '#fef2f2' : ($action === 'UPDATE' ? '#fffbeb' : '#f0fdf4');
                    $actionBorder= $action === 'DELETE' ? '#fca5a5' : ($action === 'UPDATE' ? '#fde68a' : '#86efac');
                    $school      = $entry['_school'] ?? '';
                @endphp
                <tr style="border-bottom:1px solid rgba(0,0,0,.06);">
                    <td style="padding:.45rem 1rem;color:var(--text-secondary);white-space:nowrap;">
                        {{ \Carbon\Carbon::parse($entry['changed_at'] ?? '')->format('M d H:i') }}
                    </td>
                    <td style="padding:.45rem .75rem;">
                        <span style="font-size:.72rem;font-weight:600;padding:.1rem .4rem;border-radius:.25rem;
                            background:{{ $school === 'Junior High' ? 'rgba(26,58,92,.1)' : 'rgba(16,122,60,.1)' }};
                            color:{{ $school === 'Junior High' ? '#1a3a5c' : '#107a3c' }};">
                            {{ $school === 'Junior High' ? 'JH' : 'GS' }}
                        </span>
                    </td>
                    <td style="padding:.45rem .75rem;color:var(--text-secondary);">
                        {{ \Illuminate\Support\Str::headline(str_replace('_', ' ', (string)($entry['table_name'] ?? ''))) }}
                    </td>
                    <td style="padding:.45rem .75rem;text-align:center;">
                        <span style="font-size:.7rem;padding:.15rem .45rem;border-radius:.25rem;
                            background:{{ $actionBg }};color:{{ $actionColor }};border:1px solid {{ $actionBorder }};">
                            {{ $action ?: '—' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
