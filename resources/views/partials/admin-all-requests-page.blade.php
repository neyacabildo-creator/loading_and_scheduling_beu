{{--
    Shared All Requests page (JH + GS admin).
    Required: $requests, $reviewers, $teacherScheduleRequests, $teacherLeaveRequests, $absentToday (optional)
    Required routes: $sharedApproveRoute, $sharedRejectRoute, $teacherApproveRoute, $teacherRejectRoute, $leaveApproveRoute, $leaveRejectRoute
--}}
@include('partials.admin-all-requests-styles')

<div class="header">
    <div class="header-left">
        <h1>All Requests</h1>
    </div>
    <div class="header-right">
        @php
            $pendingCount = $requests->where('status', 'pending')->count()
                + $teacherScheduleRequests->where('status', 'pending')->count()
                + ($teacherLeaveRequests ?? collect())->where('status', 'pending')->count();
        @endphp
        @if($pendingCount > 0)
            <span class="str-pending-pill">{{ $pendingCount }} pending</span>
        @endif
    </div>
</div>

@php
    $absentToday = $absentToday ?? ['regular' => [], 'shared' => []];
    $absentRegular = $absentToday['regular'] ?? [];
    $absentShared = $absentToday['shared'] ?? [];
@endphp
@if(count($absentRegular) > 0 || count($absentShared) > 0)
    <div class="str-absent-alert" role="status">
        <strong>Currently absent / on leave today</strong>
        @if(count($absentShared) > 0)
            <div class="str-absent-group">
                <span class="str-absent-group-label">Shared teachers:</span>
                @foreach($absentShared as $person)
                    <span class="str-presence-badge str-presence-{{ ($person['type'] ?? '') === 'absent' ? 'absent' : 'on_leave' }}">{{ $person['name'] }} — {{ $person['label'] }}</span>
                @endforeach
            </div>
        @endif
        @if(count($absentRegular) > 0)
            <div class="str-absent-group">
                <span class="str-absent-group-label">Teachers:</span>
                @foreach($absentRegular as $person)
                    <span class="str-presence-badge str-presence-{{ ($person['type'] ?? '') === 'absent' ? 'absent' : 'on_leave' }}">{{ $person['name'] }} — {{ $person['label'] }}</span>
                @endforeach
            </div>
        @endif
    </div>
@endif

<section class="str-panel" aria-labelledby="str-shared-title">
    <div class="str-panel-header">
        <h2 id="str-shared-title" class="str-section-title">Shared Teacher Requests</h2>
    </div>
    <div class="str-panel-body">
        @include('partials.admin-shared-teacher-requests-table', [
            'requests' => $requests,
            'reviewers' => $reviewers,
            'approveRouteName' => $sharedApproveRoute,
            'rejectRouteName' => $sharedRejectRoute,
        ])
    </div>
</section>

<section class="str-panel str-section-block" aria-labelledby="str-teacher-title">
    <div class="str-panel-header">
        <h2 id="str-teacher-title" class="str-section-title">Teacher Requests</h2>
    </div>
    <div class="str-panel-body">
        @include('partials.admin-teacher-requests-table', [
            'teacherScheduleRequests' => $teacherScheduleRequests,
            'approveRouteName' => $teacherApproveRoute,
            'rejectRouteName' => $teacherRejectRoute,
        ])
    </div>
</section>

<section class="str-panel str-section-block" aria-labelledby="str-leave-title">
    <div class="str-panel-header">
        <h2 id="str-leave-title" class="str-section-title">Absence / Leave Requests</h2>
    </div>
    <div class="str-panel-body">
        @include('partials.admin-teacher-leave-requests-table', [
            'teacherLeaveRequests' => $teacherLeaveRequests ?? collect(),
            'approveRouteName' => $leaveApproveRoute ?? 'admin.teacher-leave-requests.approve',
            'rejectRouteName' => $leaveRejectRoute ?? 'admin.teacher-leave-requests.reject',
        ])
    </div>
</section>
