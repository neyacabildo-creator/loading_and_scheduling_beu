{{-- resources/views/junior-high-admin/print-export.blade.php --}}
@extends('layouts.admin')

@section('title', 'Print / Export Schedules')

@section('content')
@include('partials.admin-print-export-styles')

<div class="header">
    <div class="header-left">
        <svg width="22" height="22" fill="none" stroke="var(--green-primary)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <h1 class="page-title">Print / Export Schedules</h1>
    </div>
    <div class="header-right"></div>
</div>

{{-- Filter Form --}}
<div class="pe-filter-card">
    <form method="GET" action="{{ route('admin.print-export') }}" id="peFilterForm">
        <div class="pe-filter-row">
            <div class="pe-fgroup">
                <label>Grade Level <span style="color:#ef4444;">*</span></label>
                <select name="grade_level" class="pe-select" id="peGrade" onchange="this.form.submit()">
                    <option value="">-- Select Grade --</option>
                    @foreach($gradeLevels as $gl)
                        <option value="{{ $gl }}" @selected($gradeLevel === $gl)>{{ $gl }}</option>
                    @endforeach
                </select>
            </div>
            @if($gradeLevel)
            <div class="pe-fgroup">
                <label>Day of Week</label>
                <select name="day_of_week" class="pe-select" id="peDay">
                    <option value="">-- All Days --</option>
                    @foreach($days as $d)
                        <option value="{{ $d }}" @selected($dayOfWeek === $d)>{{ $d }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pe-fgroup">
                <label>Schedule Date</label>
                <input type="date" name="schedule_date" class="pe-input" value="{{ $scheduleDate ?? '' }}">
            </div>
            <div class="pe-fgroup" style="justify-content:flex-end;">
                <button type="submit" class="pe-btn pe-btn-primary">Apply Filter</button>
            </div>
            @endif
        </div>
        @if(!$gradeLevel)
        <p style="font-size:.82rem;color:var(--text-secondary);margin:.75rem 0 0 0;">Select a grade level to view and print/export the class schedule.</p>
        @endif
    </form>
</div>

@if($gradeLevel)
{{-- Action Bar --}}
<div class="pe-action-bar">
    <button class="pe-btn pe-btn-primary" onclick="window.print()">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print Schedule
    </button>
    <a class="pe-btn pe-btn-secondary"
       href="{{ route('admin.export.excel', array_filter(['grade_level' => $gradeLevel, 'day_of_week' => $dayOfWeek])) }}">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Export Excel
    </a>
    <a class="pe-btn pe-btn-secondary"
       href="{{ route('admin.export.csv', array_filter(['grade_level' => $gradeLevel, 'day_of_week' => $dayOfWeek])) }}">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export CSV
    </a>
    <span style="font-size:.8rem;color:var(--text-secondary);margin-left:.25rem;">
        Showing: <strong>{{ $gradeLevel }}</strong>
        @if($dayOfWeek) &nbsp;|&nbsp; <strong>{{ $dayOfWeek }}</strong>@endif
        @if($scheduleDate) &nbsp;|&nbsp; <strong>{{ \Carbon\Carbon::parse($scheduleDate)->format('M d, Y') }}</strong>@endif
    </span>
</div>

{{-- Printable Section --}}
<div id="pe-printable">
    @include('partials.admin-print-document-header', [
        'schoolLabel' => 'Junior High School',
        'gradeLevel' => $gradeLevel,
        'dayOfWeek' => $dayOfWeek,
        'scheduleDate' => $scheduleDate,
    ])

    @php
        $displayDays = $dayOfWeek ? [$dayOfWeek] : ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    @endphp

    @foreach($displayDays as $day)
        @php $daySched = $scheduleGrid[$day] ?? []; @endphp
        <div class="pe-day-block">
            <div class="pe-day-title">{{ strtoupper($day) }}</div>
            <div style="overflow-x:auto;">
                <table class="pe-sched-table">
                    <thead>
                        <tr>
                            <th style="min-width:72px;">{{ strtoupper($day) }} / TIME</th>
                            @foreach($sections as $sec)
                                <th>{{ $sec }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($timeSlots as $slot)
                            @if(!empty($slot['days']) && !in_array($day, $slot['days'], true))
                                @continue
                            @endif
                            @if(isset($slot['type']) && $slot['type'] === 'break')
                                <tr class="break-row">
                                    <td class="time-cell">{{ $slot['label'] }}</td>
                                    @foreach($sections as $sec)
                                        <td>{{ $slot['name'] }}</td>
                                    @endforeach
                                </tr>
                            @else
                                <tr>
                                    <td class="time-cell">{{ $slot['label'] }}</td>
                                    @foreach($sections as $sec)
                                        <td>
                                            @php $entries = $daySched[$sec][$slot['start']] ?? []; @endphp
                                            @if(!empty($entries))
                                                @foreach($entries as $entry)
                                                    <div class="pe-cell-entry">
                                                        <div class="pe-cell-subject">{{ $entry['subject'] }}</div>
                                                        @if($entry['teacher'])
                                                            <div class="pe-cell-teacher">({{ $entry['teacher'] }})</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <span class="pe-empty-cell">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

@else
<div class="pe-empty">
    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto .75rem;display:block;color:var(--border-color);"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <p style="font-weight:600;margin:0 0 .35rem 0;color:var(--text-primary);">Select a Grade Level</p>
    <p style="font-size:.85rem;margin:0;">Choose a grade level from the filter above to view and export the class schedule.</p>
</div>
@endif

@endsection
