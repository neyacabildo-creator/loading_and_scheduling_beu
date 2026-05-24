{{-- Printable document header (visible only when printing) --}}
@php
    $schoolLabel = $schoolLabel ?? 'Junior High School';
@endphp
<div class="pe-print-header">
    <div class="pe-print-brand">
        <img src="{{ asset('images/spup-seal.png') }}" alt="SPUP" class="pe-print-logo">
        <div class="pe-print-brand-text">
            <p class="pe-print-school">Saint Paul University Philippines</p>
            <p class="pe-print-subtitle">Basic Education Unit &mdash; {{ $schoolLabel }}</p>
        </div>
    </div>
    <h2 class="pe-print-title">Class Schedule &mdash; {{ strtoupper($gradeLevel) }}</h2>
    <p class="pe-print-meta">
        @if($dayOfWeek ?? null){{ strtoupper($dayOfWeek) }} &nbsp;&bull;&nbsp; @endif
        @if($scheduleDate ?? null)
            Date: {{ \Carbon\Carbon::parse($scheduleDate)->format('F d, Y') }}
        @else
            School Year {{ now()->year }}&ndash;{{ now()->year + 1 }}
        @endif
    </p>
    <p class="pe-print-footer-note">Official class schedule &mdash; for internal use</p>
</div>
