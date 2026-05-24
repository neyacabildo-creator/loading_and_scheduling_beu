@extends('layouts.admin')

@section('title', $title ?? 'Monitoring & Alerts')

@section('content')
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">{{ $title ?? 'Monitoring & Alerts' }}</h1>
        </div>
        <div class="header-right"></div>
    </div>

    <p style="font-size:.88rem;color:var(--text-secondary);margin:-1rem 0 1.25rem;line-height:1.5;max-width:52rem;">
        Descriptive decision support: live conflict detection, workload balance, utilization patterns, and leave impact — aligned with dashboard stats, class schedule checks, and compliance reports.
    </p>

    @include('partials.admin-teacher-absence-banner', ['leaveBanner' => $leaveBanner ?? null])

    @include('partials.admin-monitoring-alerts', [
        'monitoring' => $monitoring,
        'scheduleRoute' => $scheduleRoute ?? 'admin.class-schedule',
    ])
@endsection
