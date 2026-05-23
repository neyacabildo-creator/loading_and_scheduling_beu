<div class="st-portal-header-actions">
    @include('partials.teacher-theme-toggle', ['bannerMode' => false])
    @include('partials.teacher-portal-notifications', [
        'notificationsApi' => $notificationsApi ?? '/api/shared-teacher/notifications',
        'markReadApi' => $markReadApi ?? '/api/shared-teacher/notifications/read',
    ])
</div>
