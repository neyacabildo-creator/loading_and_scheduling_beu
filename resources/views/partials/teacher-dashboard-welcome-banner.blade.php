@php
    $divisionLabel = $divisionLabel ?? 'Teacher Portal';
    $greetingId = $greetingId ?? 'teacherGreeting';
    $dateId = $dateId ?? 'teacherLiveDate';
    $notificationsApi = $notificationsApi ?? (
        request()->routeIs('grade-school-teacher.*')
            ? '/api/grade-school-teacher/notifications'
            : '/api/teacher/notifications'
    );
    $markReadApi = $markReadApi ?? ($notificationsApi . '/read');
    $teacherName = auth()->user()->first_name ?? auth()->user()->name ?? 'Teacher';
@endphp
@include('partials.teacher-banner-styles')
<div class="teacher-dash-banner">
    <div class="teacher-dash-banner-text">
        <p class="teacher-banner-eyebrow" style="text-transform:none;letter-spacing:0;font-weight:400;font-size:.85rem;color:rgba(255,255,255,.8);margin-bottom:.35rem;">
            Good <span id="{{ $greetingId }}">day</span>
        </p>
        <h1 class="teacher-banner-title">{{ $teacherName }}</h1>
        <p class="teacher-banner-subtitle">{{ $divisionLabel }} &bull; <span id="{{ $dateId }}"></span></p>
    </div>
    <div class="teacher-dash-banner-actions">
        @include('partials.teacher-theme-toggle', ['bannerMode' => true])
        @include('partials.teacher-portal-notifications', [
            'notificationsApi' => $notificationsApi,
            'markReadApi' => $markReadApi,
            'bannerMode' => true,
        ])
    </div>
</div>
<script>
(function(){
    const h = new Date().getHours();
    const g = document.getElementById(@json($greetingId));
    const d = document.getElementById(@json($dateId));
    if (g) g.textContent = h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening';
    if (d) d.textContent = new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
})();
</script>
