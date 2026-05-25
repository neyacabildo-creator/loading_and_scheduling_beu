{{-- resources/views/shared-teacher/dashboard.blade.php --}}
@extends('layouts.shared-teacher')

@section('title', 'My Dashboard')

@php
    $stGradeSection = function ($s) {
        $label = trim(($s->grade_level ?? '') . ' ' . ($s->section_name ?? ''));
        return $label !== '' ? $label : '—';
    };
    $stRoomLabel = $stGradeSection;
    $stFormatTime = function ($s) {
        try {
            $start = $s->start_time ? \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->format('h:i A') : null;
        } catch (\Exception $e) {
            $start = $s->start_time ? substr((string) $s->start_time, 0, 5) : null;
        }
        try {
            $end = $s->end_time ? \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time)->format('h:i A') : null;
        } catch (\Exception $e) {
            $end = $s->end_time ? substr((string) $s->end_time, 0, 5) : null;
        }
        if ($start && $end) return $start . ' – ' . $end;
        return $start ?? '—';
    };
@endphp

@push('styles')
<style>
    .st-dash-hero {
        background: linear-gradient(135deg, rgba(26, 83, 54, 0.08) 0%, rgba(240, 192, 64, 0.12) 50%, rgba(37, 99, 235, 0.06) 100%);
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        padding: 1.75rem 2rem;
        margin-bottom: 1.75rem;
        box-shadow: var(--shadow-sm);
        position: relative;
        overflow: visible;
    }
    .st-dash-hero::after {
        content: '';
        position: absolute;
        right: -2rem;
        top: -2rem;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(45, 122, 80, 0.15) 0%, transparent 70%);
        pointer-events: none;
    }
    .st-dash-hero h1 {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -0.02em;
        margin: 0 0 0.35rem;
    }
    .st-dash-hero p {
        color: var(--text-secondary);
        font-size: 0.9rem;
        margin: 0;
        max-width: 36rem;
        line-height: 1.5;
    }
    .st-dash-hero .st-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.85rem;
        padding: 0.35rem 0.75rem;
        background: rgba(45, 122, 80, 0.12);
        border: 1px solid rgba(45, 122, 80, 0.25);
        border-radius: 9999px;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--green-primary);
    }
    .stats-row.st-dash-stats {
        gap: 1rem;
        margin-bottom: 1.75rem;
    }
    .st-stat-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 0.875rem;
        padding: 1.15rem 1.25rem;
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        box-shadow: var(--shadow-sm);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .st-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    .st-stat-card.jh { border-left: 4px solid #2563eb; }
    .st-stat-card.gs { border-left: 4px solid #2d7a50; }
    .st-stat-card.total { border-left: 4px solid var(--accent); }
    .st-stat-card.pending { border-left: 4px solid #f59e0b; }
    .st-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .st-stat-card.jh .st-stat-icon { background: rgba(37, 99, 235, 0.12); color: #2563eb; }
    .st-stat-card.gs .st-stat-icon { background: rgba(45, 122, 80, 0.12); color: #2d7a50; }
    .st-stat-card.total .st-stat-icon { background: rgba(240, 192, 64, 0.2); color: #b45309; }
    .st-stat-card.pending .st-stat-icon { background: rgba(245, 158, 11, 0.15); color: #b45309; }
    .st-stat-body { flex: 1; min-width: 0; }
    .st-stat-body .stat-label { margin-bottom: 0.15rem; }
    .st-stat-body .stat-value { font-size: 1.75rem; }
    .st-schedule-panel {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.5rem;
    }
    .st-schedule-panel:last-child { margin-bottom: 0; }
    .st-schedule-panel.jh .st-panel-head {
        background: linear-gradient(90deg, rgba(37, 99, 235, 0.08) 0%, transparent 100%);
        border-bottom: 1px solid rgba(37, 99, 235, 0.15);
    }
    .st-schedule-panel.gs .st-panel-head {
        background: linear-gradient(90deg, rgba(45, 122, 80, 0.1) 0%, transparent 100%);
        border-bottom: 1px solid rgba(45, 122, 80, 0.2);
    }
    .st-panel-head {
        padding: 1.1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .st-panel-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .st-panel-count {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-secondary);
        background: var(--bg-tertiary);
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        border: 1px solid var(--border-color);
    }
    .st-schedule-table-wrap { overflow-x: auto; }
    .st-schedule-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    .st-schedule-table thead th {
        padding: 0.7rem 1.15rem;
        background: var(--bg-tertiary);
        text-align: left;
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-secondary);
        border-bottom: 1px solid var(--border-color);
    }
    .st-schedule-table tbody td {
        padding: 0.95rem 1.15rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    .st-schedule-table tbody tr:last-child td { border-bottom: none; }
    .st-schedule-table tbody tr:hover td { background: rgba(45, 122, 80, 0.04); }
    .st-subject-cell {
        font-weight: 700;
        color: var(--text-primary);
    }
    .st-day-chip {
        display: inline-block;
        padding: 0.2rem 0.55rem;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-primary);
    }
    .st-time-cell {
        font-variant-numeric: tabular-nums;
        font-weight: 500;
        color: var(--text-primary);
        white-space: nowrap;
    }
    .st-room-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        background: linear-gradient(135deg, rgba(45, 122, 80, 0.12) 0%, rgba(26, 83, 54, 0.08) 100%);
        border: 1px solid rgba(45, 122, 80, 0.28);
        border-radius: 0.5rem;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--green-dark);
        max-width: 100%;
    }
    .st-schedule-panel.jh .st-room-badge {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(37, 99, 235, 0.04) 100%);
        border-color: rgba(37, 99, 235, 0.28);
        color: #1d4ed8;
    }
    .st-grade-cell {
        font-weight: 500;
        color: var(--text-secondary);
    }
    .st-empty-state {
        padding: 2.5rem 1.5rem;
        text-align: center;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    .st-empty-state svg {
        width: 48px;
        height: 48px;
        margin: 0 auto 0.75rem;
        opacity: 0.35;
        display: block;
    }
</style>
@endpush

@section('content')
<div class="st-dash-hero">
    <div class="st-dash-hero-inner">
        <div class="st-dash-hero-text">
            <h1>My Schedule Dashboard</h1>
            <p>Welcome back, <strong>{{ Auth::user()->first_name }}</strong>.</p>
            <span class="st-hero-badge">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Cross-division shared teacher
            </span>
        </div>
        @include('partials.shared-teacher-header-actions')
    </div>
</div>

<div class="stats-row st-dash-stats">
    <div class="st-stat-card jh">
        <div class="st-stat-icon">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </div>
        <div class="st-stat-body">
            <div class="stat-label">JH Schedules</div>
            <div class="stat-value" style="color:#2563eb;">{{ count($jhSchedules) }}</div>
            <div class="stat-sub">Junior High classes</div>
        </div>
    </div>
    <div class="st-stat-card gs">
        <div class="st-stat-icon">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div class="st-stat-body">
            <div class="stat-label">GS Schedules</div>
            <div class="stat-value" style="color:#2d7a50;">{{ count($gsSchedules) }}</div>
            <div class="stat-sub">Grade School classes</div>
        </div>
    </div>
    <div class="st-stat-card total">
        <div class="st-stat-icon">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div class="st-stat-body">
            <div class="stat-label">Total Classes</div>
            <div class="stat-value">{{ count($jhSchedules) + count($gsSchedules) }}</div>
            <div class="stat-sub">across both levels</div>
        </div>
    </div>
    <div class="st-stat-card pending">
        <div class="st-stat-icon">
            <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="st-stat-body">
            <div class="stat-label">Pending Requests</div>
            <div class="stat-value" style="color:{{ $pendingRequests > 0 ? '#b45309' : 'var(--text-primary)' }};">{{ $pendingRequests }}</div>
            <div class="stat-sub"><a href="{{ route('shared-teacher.requests') }}" style="color:var(--green-primary);text-decoration:none;font-weight:600;">My requests →</a></div>
        </div>
    </div>
</div>

@include('partials.shared-teacher-weekly-timetable', [
    'stWeeklyTimetable' => $stWeeklyTimetable ?? [],
    'scheduleView' => $scheduleView ?? 'current',
    'selectedDate' => $selectedDate ?? null,
    'stScheduleDateBuckets' => $stScheduleDateBuckets ?? ['saved' => [], 'future' => [], 'past' => []],
])
@endsection
