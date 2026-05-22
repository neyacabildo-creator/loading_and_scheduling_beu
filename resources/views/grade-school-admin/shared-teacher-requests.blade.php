{{-- resources/views/grade-school-admin/shared-teacher-requests.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'All Requests – GS')

@section('content')
    @include('partials.admin-all-requests-page', [
        'requests' => $requests,
        'reviewers' => $reviewers,
        'teacherScheduleRequests' => $teacherScheduleRequests,
        'teacherLeaveRequests' => $teacherLeaveRequests ?? collect(),
        'sharedApproveRoute' => 'grade-school-admin.shared-teacher-requests.approve',
        'sharedRejectRoute' => 'grade-school-admin.shared-teacher-requests.reject',
        'teacherApproveRoute' => 'grade-school-admin.teacher-schedule-requests.approve',
        'teacherRejectRoute' => 'grade-school-admin.teacher-schedule-requests.reject',
        'leaveApproveRoute' => 'grade-school-admin.teacher-leave-requests.approve',
        'leaveRejectRoute' => 'grade-school-admin.teacher-leave-requests.reject',
    ])
@endsection
