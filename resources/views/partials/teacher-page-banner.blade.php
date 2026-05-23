@php
    $pageTitle = $pageTitle ?? 'Page';
    $pageSubtitle = $pageSubtitle ?? null;
    $eyebrow = $eyebrow ?? null;
    $showPrint = $showPrint ?? false;
    $printLabel = $printLabel ?? 'Print';
    $bannerClass = $bannerClass ?? '';
    $notificationsApi = $notificationsApi ?? (
        request()->routeIs('grade-school-teacher.*')
            ? '/api/grade-school-teacher/notifications'
            : '/api/teacher/notifications'
    );
    $markReadApi = $markReadApi ?? ($notificationsApi . '/read');
@endphp
@include('partials.teacher-banner-styles')
<div class="teacher-dash-banner {{ $bannerClass }}">
    <div class="teacher-dash-banner-text">
        @if($eyebrow)
            <p class="teacher-banner-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="teacher-banner-title">{{ $pageTitle }}</h1>
        @if($pageSubtitle)
            <p class="teacher-banner-subtitle">{{ $pageSubtitle }}</p>
        @endif
    </div>
    <div class="teacher-dash-banner-actions">
        @if($showPrint)
            <button type="button" class="teacher-banner-btn teacher-banner-print-btn" onclick="window.print()" title="Print this page">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                {{ $printLabel }}
            </button>
        @endif
        @isset($beforeActions){!! $beforeActions !!}@endisset
        @include('partials.teacher-theme-toggle', ['bannerMode' => true])
        @include('partials.teacher-portal-notifications', [
            'notificationsApi' => $notificationsApi,
            'markReadApi' => $markReadApi,
            'bannerMode' => true,
        ])
    </div>
</div>
