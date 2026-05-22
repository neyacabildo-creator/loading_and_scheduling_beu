{{-- resources/views/junior-high-admin/print-export.blade.php --}}
@extends('layouts.admin')

@section('title', 'Print / Export Schedules')

@section('content')
<style>
/* â”€â”€ Screen styles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.pe-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;}
.pe-filter-card{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:.75rem;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--shadow-sm);}
.pe-filter-row{display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;}
.pe-fgroup{display:flex;flex-direction:column;gap:.35rem;}
.pe-fgroup label{font-size:.78rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;}
.pe-select,.pe-input{padding:.55rem .85rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.875rem;min-width:150px;}
.pe-select:focus,.pe-input:focus{outline:none;border-color:var(--green-primary);}
.pe-btn{padding:.6rem 1.4rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.85rem;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;}
.pe-btn-primary{background:linear-gradient(135deg,var(--green-primary),#0d3d20);color:#fff;}
.pe-btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(45,122,80,.3);}
.pe-btn-secondary{background:var(--bg-secondary);border:1px solid var(--border-color);color:var(--text-primary);}
.pe-btn-secondary:hover{border-color:var(--green-primary);color:var(--green-primary);}
.pe-action-bar{display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem;}
.pe-empty{background:var(--bg-secondary);border:2px dashed var(--border-color);border-radius:.75rem;padding:3rem;text-align:center;color:var(--text-secondary);}
/* â”€â”€ Day block â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.pe-day-block{margin-bottom:2.5rem;}
.pe-day-title{font-size:.9rem;font-weight:700;color:var(--green-primary);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.6rem;padding-bottom:.4rem;border-bottom:2px solid var(--green-primary);}
/* â”€â”€ Schedule table â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.pe-sched-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.pe-sched-table th{padding:.5rem .55rem;background:#1a5336;color:#fff;border:1px solid #0d2e1e;text-align:center;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;}
.pe-sched-table td{padding:.38rem .45rem;border:1px solid var(--border-color);text-align:center;vertical-align:middle;font-size:.76rem;}
.pe-sched-table .time-cell{background:var(--bg-tertiary);font-weight:700;color:var(--text-primary);white-space:nowrap;min-width:70px;font-size:.72rem;}
.pe-sched-table .break-row td{background:rgba(245,158,11,.08);font-weight:700;color:#92400e;font-size:.71rem;letter-spacing:.06em;}
.pe-cell-entry{margin:.1rem 0;}
.pe-cell-subject{font-weight:700;color:var(--text-primary);}
.pe-cell-teacher{font-size:.7rem;color:var(--text-secondary);}
.pe-empty-cell{color:#ccc;font-size:.68rem;}
/* â”€â”€ Print styles â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media print {
    body *{visibility:hidden;}
    #pe-printable,#pe-printable *{visibility:visible;}
    #pe-printable{position:absolute;top:0;left:0;width:100%;}
    .pe-sched-table{font-size:8pt;}
    .pe-sched-table th{background:#1a5336!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .pe-sched-table .break-row td{background:rgba(245,158,11,.08)!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .pe-print-header{display:block!important;}
    .pe-day-title{color:#1a5336!important;border-bottom-color:#1a5336!important;}
    .pe-day-block{page-break-inside:avoid;}
}
.pe-print-header{display:none;text-align:center;margin-bottom:1.2rem;}
.pe-print-header h2{font-size:13pt;font-weight:700;margin:0 0 .25rem 0;text-transform:uppercase;letter-spacing:.05em;}
.pe-print-header p{font-size:9pt;color:#555;margin:0;}
</style>

<div class="pe-header">
    <div class="header-left">
        <svg width="22" height="22" fill="none" stroke="#2d7a50" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        <h1 class="page-title">Print / Export Schedules</h1>
    </div>
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
    {{-- Print Header (only visible when printing) --}}
    <div class="pe-print-header">
        <h2>Class Schedule &mdash; {{ strtoupper($gradeLevel) }} | Junior High School</h2>
        <p>
            @if($dayOfWeek){{ strtoupper($dayOfWeek) }} &nbsp;&bull;&nbsp; @endif
            @if($scheduleDate)
                Date: {{ \Carbon\Carbon::parse($scheduleDate)->format('F d, Y') }}
            @else
                S.Y. {{ now()->year }}&ndash;{{ now()->year + 1 }}
            @endif
        </p>
    </div>

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
