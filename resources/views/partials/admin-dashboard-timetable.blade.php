@php
    $prefix = $prefix ?? 'jh';
    $apiUrl = $apiUrl ?? '';
    $initial = $initial ?? [];
    $dayClass = $prefix . 'd-day-btn';
    $gradeClass = $prefix . 'd-grade-btn';
@endphp
<script>
    window.__DASH_TIMETABLE_CONFIG__ = {
        prefix: @json($prefix),
        apiUrl: @json($apiUrl),
        initial: @json($initial, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE),
        slots: @json($slots ?? []),
        sections: @json($sections ?? []),
        grades: @json($grades ?? []),
    };
</script>
<script src="{{ asset('js/admin-dashboard-timetable.js') }}" defer></script>
