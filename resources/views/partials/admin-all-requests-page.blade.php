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

@include('partials.admin-teacher-absence-banner', ['leaveBanner' => $absentToday ?? $leaveBanner ?? null])

<section class="str-panel" aria-labelledby="str-shared-title">
    <div class="str-panel-header">
        <div class="str-panel-header-row">
            <h2 id="str-shared-title" class="str-section-title">Shared Teacher Requests</h2>
            <input type="search" class="str-section-search" data-str-table="strTable" placeholder="Search name, subject, grade…" aria-label="Search shared teacher requests">
        </div>
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
        <div class="str-panel-header-row">
            <h2 id="str-teacher-title" class="str-section-title">Teacher Requests</h2>
            <input type="search" class="str-section-search" data-str-table="tsrTable" placeholder="Search name, subject, grade…" aria-label="Search teacher requests">
        </div>
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
        <div class="str-panel-header-row">
            <div>
                <h2 id="str-leave-title" class="str-section-title">Absence / Leave Requests</h2>
                <p class="str-section-desc">Regular teachers and shared teachers — each row is labeled by role.</p>
            </div>
            <input type="search" class="str-section-search" data-str-table="tlrTable" placeholder="Search name, leave type…" aria-label="Search absence and leave requests">
        </div>
    </div>
    <div class="str-panel-body">
        @include('partials.admin-teacher-leave-requests-table', [
            'teacherLeaveRequests' => $teacherLeaveRequests ?? collect(),
            'approveRouteName' => $leaveApproveRoute ?? 'admin.teacher-leave-requests.approve',
            'rejectRouteName' => $leaveRejectRoute ?? 'admin.teacher-leave-requests.reject',
        ])
    </div>
</section>

<script src="{{ asset('js/admin-all-requests-search.js') }}" defer></script>
