@php
    $divisionLabel = $divisionLabel ?? 'Teacher Portal';
    $greetingId = $greetingId ?? 'teacherGreeting';
    $dateId = $dateId ?? 'teacherLiveDate';
    $notificationsApi = $notificationsApi ?? '/api/teacher/notifications';
    $markReadApi = $markReadApi ?? ($notificationsApi . '/read');
    $teacherName = auth()->user()->first_name ?? auth()->user()->name ?? 'Teacher';
@endphp
<div class="teacher-dash-banner" style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div>
        <p style="color:rgba(255,255,255,.8);font-size:.85rem;margin-bottom:.35rem;">Good <span id="{{ $greetingId }}">day</span></p>
        <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .25rem;">{{ $teacherName }}</h1>
        <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">{{ $divisionLabel }} &bull; <span id="{{ $dateId }}"></span></p>
    </div>
    <div class="teacher-dash-banner-actions" style="display:flex;align-items:center;gap:.75rem;flex-shrink:0;">
        <button type="button" class="teacher-banner-btn" title="Language">EN</button>
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
<style>
.teacher-banner-btn {
    padding: 0.45rem 0.75rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(255,255,255,.9);
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.35);
    border-radius: 0.375rem;
    cursor: pointer;
}
.teacher-banner-btn:hover { background: rgba(255,255,255,.25); }
.teacher-dash-banner-actions .tp-notif-btn { color: rgba(255,255,255,.9) !important; }
.teacher-dash-banner-actions .tp-notif-btn:hover { background: rgba(255,255,255,.15) !important; color: #fff !important; }
.teacher-dash-banner-actions .tp-notif-dot { border: 1px solid rgba(255,255,255,.5); }
</style>
