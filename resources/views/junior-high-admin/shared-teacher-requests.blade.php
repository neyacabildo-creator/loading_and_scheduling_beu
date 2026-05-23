{{-- resources/views/junior-high-admin/shared-teacher-requests.blade.php --}}
@extends('layouts.admin')

@section('title', 'All Requests – JH')

@section('content')
    @include('partials.admin-all-requests-page', [
        'adminPortal' => 'junior_high',
        'requests' => $requests,
        'reviewers' => $reviewers,
        'teacherScheduleRequests' => $teacherScheduleRequests,
        'teacherLeaveRequests' => $teacherLeaveRequests ?? collect(),
        'sharedApproveRoute' => 'admin.shared-teacher-requests.approve',
        'sharedRejectRoute' => 'admin.shared-teacher-requests.reject',
        'teacherApproveRoute' => 'admin.teacher-schedule-requests.approve',
        'teacherRejectRoute' => 'admin.teacher-schedule-requests.reject',
        'leaveApproveRoute' => 'admin.teacher-leave-requests.approve',
        'leaveRejectRoute' => 'admin.teacher-leave-requests.reject',
    ])
@endsection
