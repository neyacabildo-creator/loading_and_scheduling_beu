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
<style>
    .admin-header-actions {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }
    .admin-header-actions .ap-notif-btn.ap-notif-btn {
        width: 40px;
        height: 40px;
        min-width: 40px;
        padding: 0;
        background: var(--bg-tertiary);
        border: 2px solid var(--green-primary);
        border-radius: 0.5rem;
        color: var(--green-primary);
    }
    .admin-header-actions .ap-notif-btn.ap-notif-btn:hover {
        color: white;
        background: var(--green-primary);
    }
    html[data-theme="dark"] .admin-header-actions .ap-notif-btn.ap-notif-btn {
        background: #3a3a3a;
        border-color: #4a9d6f;
        color: #e0e0e0;
    }
    html[data-theme="dark"] .admin-header-actions .ap-notif-btn.ap-notif-btn:hover {
        background: #4a9d6f;
        color: white;
    }
</style>
<div class="admin-header-actions">
    @include('partials.teacher-theme-toggle', ['bannerMode' => false])
    @include('partials.admin-portal-notifications', compact('notificationsApi', 'allRequestsUrl', 'permissionRequestsUrl'))
</div>
