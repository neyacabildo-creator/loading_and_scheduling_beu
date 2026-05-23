@php
    $bannerMode = $bannerMode ?? false;
    $btnId = $bannerMode ? 'bannerThemeToggle' : 'themeToggle';
    $iconId = $bannerMode ? 'bannerThemeIcon' : 'toolbarThemeIcon';
@endphp
<button type="button"
    class="{{ $bannerMode ? 'teacher-banner-btn teacher-theme-toggle-btn' : 'theme-toggle-btn' }}"
    id="{{ $btnId }}"
    data-theme-toggle
    onclick="typeof toggleTheme === 'function' && toggleTheme()"
    title="Dark mode"
    aria-label="Toggle dark or light mode">
    <span id="{{ $iconId }}" class="teacher-theme-icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
    </span>
</button>
