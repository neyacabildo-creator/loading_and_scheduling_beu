@php
    $portal = $portal ?? 'junior_high';
    if ($portal === 'grade_school') {
        $notificationsApi = $notificationsApi ?? url('/api/grade-school-admin/notifications');
        $allRequestsUrl = $allRequestsUrl ?? route('grade-school-admin.shared-teacher-requests');
        $permissionRequestsUrl = $permissionRequestsUrl ?? route('grade-school-admin.permission-requests');
    } else {
        $notificationsApi = $notificationsApi ?? url('/api/admin/notifications');
        $allRequestsUrl = $allRequestsUrl ?? route('admin.shared-teacher-requests');
        $permissionRequestsUrl = $permissionRequestsUrl ?? route('admin.permission-requests');
    }
@endphp
<div class="admin-header-actions" style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
    @include('partials.admin-portal-notifications', compact('notificationsApi', 'allRequestsUrl', 'permissionRequestsUrl'))
</div>
