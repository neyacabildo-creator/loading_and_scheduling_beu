{{-- resources/views/grade-school-teacher/dashboard.blade.php --}}
@extends('layouts.grade-school-teacher')

@section('title', 'Teacher Dashboard - Grade School')

@section('content')
    {{-- ── Welcome Banner ────────────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <p style="color:rgba(255,255,255,.8);font-size:.85rem;margin-bottom:.35rem;">Good <span id="gsGreeting">day</span></p>
            <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .25rem;">{{ auth()->user()->first_name ?? auth()->user()->name ?? 'Teacher' }}</h1>
            <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Grade School Division &bull; <span id="gsLiveDate"></span></p>
        </div>
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            <button onclick="toggleTheme()" id="bannerThemeToggle" title="Toggle dark / light mode"
                style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:.5rem;padding:.75rem 1.25rem;cursor:pointer;color:white;min-width:90px;transition:background .2s;">
                <span id="bannerThemeIcon" style="display:flex;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </span>
                <span id="bannerThemeLabel" style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:rgba(255,255,255,.8);">Dark Mode</span>
            </button>
            <div style="background:rgba(255,255,255,.15);border-radius:.5rem;padding:.75rem 1.25rem;text-align:center;min-width:90px;">
                <p style="color:rgba(255,255,255,.8);font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.25rem;">My Classes</p>
                <p style="color:white;font-size:1.5rem;font-weight:800;margin:0;">{{ $myClasses ?? '—' }}</p>
            </div>
            <div style="background:rgba(255,255,255,.15);border-radius:.5rem;padding:.75rem 1.25rem;text-align:center;min-width:90px;">
                <p style="color:rgba(255,255,255,.8);font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.25rem;">Load Hours</p>
                <p style="color:white;font-size:1.5rem;font-weight:800;margin:0;">{{ $teachingLoad ?? '—' }}</p>
            </div>
        </div>
    </div>
    <script>
    (function(){
        const h = new Date().getHours();
        document.getElementById('gsGreeting').textContent = h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening';
        document.getElementById('gsLiveDate').textContent = new Date().toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
    })();
    </script>

    {{-- ── Quick Actions ────────────────────────────────────────────── --}}
    <div style="background:var(--bg-secondary);border-radius:.75rem;padding:1.25rem 1.5rem;border:1px solid var(--border-color);margin-bottom:1.5rem;">
        <h3 style="font-size:.85rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em;margin:0 0 1rem;">Quick Actions</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.75rem;">
            <a href="{{ route('grade-school-teacher.class-schedule') }}" style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;background:rgba(45,122,80,.07);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;text-decoration:none;color:var(--text-primary);">
                <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span style="font-size:.8rem;font-weight:600;">View Schedule</span>
            </a>
            <a href="{{ route('grade-school-teacher.review-schedule') }}" style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;background:rgba(45,122,80,.07);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;text-decoration:none;color:var(--text-primary);">
                <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span style="font-size:.8rem;font-weight:600;">Review Schedule</span>
            </a>
            <a href="{{ route('grade-school-teacher.feedback') }}" style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;background:rgba(45,122,80,.07);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;text-decoration:none;color:var(--text-primary);">
                <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <span style="font-size:.8rem;font-weight:600;">Feedback</span>
            </a>
            <a href="{{ route('grade-school-teacher.request-adjustments') }}" style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;background:rgba(45,122,80,.07);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;text-decoration:none;color:var(--text-primary);">
                <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span style="font-size:.8rem;font-weight:600;">Request Adjustment</span>
            </a>
            <a href="{{ route('grade-school-teacher.faculty-loading') }}" style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;background:rgba(45,122,80,.07);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;text-decoration:none;color:var(--text-primary);">
                <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                <span style="font-size:.8rem;font-weight:600;">Workload Summary</span>
            </a>
            <a href="{{ route('grade-school-teacher.print-export') }}" style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;background:rgba(45,122,80,.07);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;text-decoration:none;color:var(--text-primary);">
                <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span style="font-size:.8rem;font-weight:600;">Export / Print</span>
            </a>
            <a href="{{ route('grade-school-teacher.loading-schedule') }}" style="display:flex;align-items:center;gap:.6rem;padding:.7rem .9rem;background:rgba(45,122,80,.07);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;text-decoration:none;color:var(--text-primary);">
                <svg width="18" height="18" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span style="font-size:.8rem;font-weight:600;">Loading Schedule</span>
            </a>
        </div>
    </div>

    {{-- ── Today's Schedule + Teaching Load ───────────────────────── --}}
    @php
        $todayName       = now()->format('l');
        $mySchedulesColl = $mySchedules ?? collect();
        $todaySchedules  = $mySchedulesColl->filter(fn($s) => strcasecmp($s->day_of_week ?? '', $todayName) === 0)->sortBy('start_time');
        $maxLoad   = 30;
        $loadPct   = $maxLoad > 0 ? min(100, round((($teachingLoad ?? 0) / $maxLoad) * 100)) : 0;
        $loadColor = $loadPct >= 90 ? '#dc2626' : ($loadPct >= 70 ? '#f59e0b' : '#059669');
        $dayOrder  = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $schedsByDay = $mySchedulesColl->groupBy(fn($s) => ucfirst(strtolower($s->day_of_week ?? '')));
    @endphp

    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;margin-bottom:1.5rem;">
        {{-- Today's Schedule --}}
        <div style="background:var(--bg-secondary);border-radius:.75rem;padding:1.5rem;border:1px solid var(--border-color);">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;">
                <div>
                    <h3 style="font-size:1rem;font-weight:700;color:var(--text-primary);margin:0;">Today's Schedule</h3>
                    <p style="font-size:.75rem;color:var(--text-secondary);margin:.2rem 0 0;">{{ now()->format('l, F j, Y') }}</p>
                </div>

            </div>
            @if($todaySchedules->isEmpty())
                <div style="text-align:center;padding:2.5rem;color:var(--text-secondary);">
                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto .75rem;display:block;opacity:.35"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <p style="font-weight:600;margin:0;">No classes today</p>
                    <p style="font-size:.8rem;margin:.3rem 0 0;">Enjoy your {{ $todayName }}.</p>
                </div>
            @else
                <div style="display:flex;flex-direction:column;gap:.75rem;">
                    @foreach($todaySchedules as $sch)
                    @php
                        $st = $sch->start_time ? \Carbon\Carbon::createFromFormat('H:i:s', $sch->start_time)->format('g:i A') : '—';
                        $et = $sch->end_time   ? \Carbon\Carbon::createFromFormat('H:i:s', $sch->end_time)->format('g:i A')   : '—';
                        $isNow = $sch->start_time && $sch->end_time
                            && now()->format('H:i') >= substr($sch->start_time,0,5)
                            && now()->format('H:i') <  substr($sch->end_time,0,5);
                    @endphp
                    <div style="display:flex;align-items:center;gap:.875rem;padding:.75rem;background:{{ $isNow ? 'rgba(45,122,80,.12)' : 'var(--bg-primary)' }};border-radius:.5rem;border-left:3px solid {{ $isNow ? '#2d7a50' : 'var(--border-color)' }};">
                        <div style="text-align:center;min-width:58px;">
                            <p style="font-size:.68rem;color:var(--text-secondary);margin:0;">{{ $st }}</p>
                            <div style="border-left:2px dashed var(--border-color);height:12px;margin:2px auto;width:0;"></div>
                            <p style="font-size:.68rem;color:var(--text-secondary);margin:0;">{{ $et }}</p>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-weight:700;color:var(--text-primary);margin:0;font-size:.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $sch->subject ?? 'N/A' }}</p>
                            <p style="font-size:.75rem;color:var(--text-secondary);margin:.1rem 0 0;">{{ $sch->grade_level ?? '' }}{{ $sch->section_name ? ' · ' . $sch->section_name : '' }}</p>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            @if($isNow)
                                <span style="background:#2d7a50;color:#fff;font-size:.65rem;font-weight:700;padding:.2rem .55rem;border-radius:2rem;white-space:nowrap;">NOW</span>
                            @else
                                <span style="background:var(--bg-primary);border:1px solid var(--border-color);color:var(--text-secondary);font-size:.65rem;padding:.2rem .55rem;border-radius:2rem;white-space:nowrap;">{{ $sch->room_id ? 'Room #'.$sch->room_id : (trim(($sch->grade_level ?? '') . ($sch->section_name ? ' – '.$sch->section_name : '')) ?: 'TBA') }}</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Teaching Load + Notices --}}
        <div style="display:flex;flex-direction:column;gap:1rem;">
            <div style="background:var(--bg-secondary);border-radius:.75rem;padding:1.25rem;border:1px solid var(--border-color);">
                <h3 style="font-size:.9rem;font-weight:700;color:var(--text-primary);margin:0 0 .875rem;">Teaching Load</h3>
                <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:.5rem;">
                    <span style="font-size:1.9rem;font-weight:800;color:{{ $loadColor }};">{{ $teachingLoad ?? 0 }}</span>
                    <span style="font-size:.78rem;color:var(--text-secondary);">/ {{ $maxLoad }} units</span>
                </div>
                <div style="background:var(--bg-primary);border-radius:999px;height:9px;overflow:hidden;margin-bottom:.5rem;">
                    <div style="width:{{ $loadPct }}%;height:100%;background:{{ $loadColor }};border-radius:999px;transition:width .6s;"></div>
                </div>
                <p style="font-size:.73rem;color:var(--text-secondary);margin:0 0 .875rem;">
                    @if($loadPct >= 90) Near maximum load
                    @elseif($loadPct >= 70) {{ $maxLoad - ($teachingLoad ?? 0) }} units remaining
                    @else {{ $maxLoad - ($teachingLoad ?? 0) }} units available
                    @endif
                </p>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;">
                    <div style="background:var(--bg-primary);border-radius:.375rem;padding:.6rem;text-align:center;">
                        <p style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin:0;">{{ $myClasses ?? '—' }}</p>
                        <p style="font-size:.63rem;color:var(--text-secondary);margin:.1rem 0 0;text-transform:uppercase;">Classes</p>
                    </div>
                    <div style="background:var(--bg-primary);border-radius:.375rem;padding:.6rem;text-align:center;">
                        <p style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin:0;">{{ $totalStudents ?? '—' }}</p>
                        <p style="font-size:.63rem;color:var(--text-secondary);margin:.1rem 0 0;text-transform:uppercase;">Students</p>
                    </div>
                </div>
            </div>

            <div style="background:var(--bg-secondary);border-radius:.75rem;padding:1.25rem;border:1px solid var(--border-color);flex:1;">
                <h3 style="font-size:.9rem;font-weight:700;color:var(--text-primary);margin:0 0 .875rem;">Notices</h3>
                @if(($pendingTasks ?? 0) > 0)
                <div style="display:flex;align-items:flex-start;gap:.55rem;padding:.6rem .7rem;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:.5rem;margin-bottom:.6rem;">
                    <svg width="15" height="15" fill="none" stroke="#b45309" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                    <div>
                        <p style="font-size:.78rem;font-weight:700;color:#92400e;margin:0;">{{ $pendingTasks }} Pending Task{{ $pendingTasks > 1 ? 's' : '' }}</p>
                        <p style="font-size:.7rem;color:#78350f;margin:.1rem 0 0;">Review your pending schedule items.</p>
                    </div>
                </div>
                @endif
                <div style="display:flex;align-items:flex-start;gap:.55rem;padding:.6rem .7rem;background:rgba(45,122,80,.08);border:1px solid rgba(45,122,80,.2);border-radius:.5rem;margin-bottom:.6rem;">
                    <svg width="15" height="15" fill="none" stroke="#2d7a50" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <div>
                        <p style="font-size:.78rem;font-weight:700;color:#1a5336;margin:0;">Schedule Review</p>
                        <p style="font-size:.7rem;color:var(--text-secondary);margin:.1rem 0 0;">Check your assigned schedule for accuracy.</p>
                    </div>
                </div>
                <div style="display:flex;align-items:flex-start;gap:.55rem;padding:.6rem .7rem;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);border-radius:.5rem;">
                    <svg width="15" height="15" fill="none" stroke="#1d4ed8" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <div>
                        <p style="font-size:.78rem;font-weight:700;color:#1e40af;margin:0;">Feedback Open</p>
                        <p style="font-size:.7rem;color:var(--text-secondary);margin:.1rem 0 0;">Submit schedule feedback to your admin.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Week Overview ────────────────────────────────────────────── --}}
    <div style="background:var(--bg-secondary);border-radius:.75rem;padding:1.25rem 1.5rem;border:1px solid var(--border-color);margin-bottom:1.5rem;">
        <h3 style="font-size:.9rem;font-weight:700;color:var(--text-primary);margin:0 0 1rem;">Week Overview</h3>
        <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:.5rem;">
            @foreach($dayOrder as $day)
            @php $cnt = isset($schedsByDay[$day]) ? $schedsByDay[$day]->count() : 0; $isTd = $todayName === $day; @endphp
            <div style="text-align:center;padding:.75rem .5rem;border-radius:.5rem;background:{{ $isTd ? '#1a5336' : ($cnt > 0 ? 'rgba(45,122,80,.1)' : 'var(--bg-primary)') }};border:2px solid {{ $isTd ? '#2d7a50' : 'var(--border-color)' }};">
                <p style="font-size:.63rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:{{ $isTd ? '#fff' : 'var(--text-secondary)' }};margin:0;">{{ substr($day,0,3) }}</p>
                @if($cnt > 0)
                    <p style="font-size:1.2rem;font-weight:800;color:{{ $isTd ? '#fff' : '#2d7a50' }};margin:.2rem 0 0;">{{ $cnt }}</p>
                    <p style="font-size:.58rem;color:{{ $isTd ? 'rgba(255,255,255,.75)' : 'var(--text-secondary)' }};margin:0;">class{{ $cnt > 1 ? 'es' : '' }}</p>
                @else
                    <p style="font-size:.75rem;color:{{ $isTd ? 'rgba(255,255,255,.6)' : 'var(--text-secondary)' }};margin:.2rem 0 0;">—</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── My Assigned Schedules ────────────────────────────────────── --}}
    <div style="background:var(--bg-secondary);border-radius:.75rem;border:1px solid var(--border-color);overflow:hidden;margin-bottom:1.5rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:1.25rem 1.5rem;border-bottom:1px solid var(--border-color);">
            <div>
                <h3 style="font-size:1rem;font-weight:700;color:var(--text-primary);margin:0;">My Assigned Schedules</h3>
                <p style="font-size:.75rem;color:var(--text-secondary);margin:.15rem 0 0;">All active class assignments this semester</p>
            </div>
            <a href="{{ route('grade-school-teacher.review-schedule') }}" style="padding:.45rem .9rem;background:#2d7a50;color:#fff;border-radius:.375rem;font-size:.78rem;font-weight:600;text-decoration:none;">Review</a>
        </div>
        @if($mySchedulesColl->isEmpty())
            <div style="text-align:center;padding:3rem;color:var(--text-secondary);">
                <svg width="44" height="44" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto .75rem;display:block;opacity:.3"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p style="font-weight:600;margin:0;">No schedules assigned yet</p>
                <p style="font-size:.8rem;margin:.3rem 0 0;">Contact your admin if you expect assignments here.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:var(--bg-primary);font-size:.73rem;text-transform:uppercase;letter-spacing:.04em;color:var(--text-secondary);">
                            <th style="padding:.7rem 1rem;text-align:left;font-weight:600;">Subject</th>
                            <th style="padding:.7rem 1rem;text-align:left;font-weight:600;">Grade / Section</th>
                            <th style="padding:.7rem 1rem;text-align:left;font-weight:600;">Day</th>
                            <th style="padding:.7rem 1rem;text-align:left;font-weight:600;">Time</th>
                            <th style="padding:.7rem 1rem;text-align:left;font-weight:600;">Room</th>
                            <th style="padding:.7rem 1rem;text-align:left;font-weight:600;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mySchedulesColl as $sch)
                        @php
                            $st = $sch->start_time ? \Carbon\Carbon::createFromFormat('H:i:s', $sch->start_time)->format('g:i A') : '—';
                            $et = $sch->end_time   ? \Carbon\Carbon::createFromFormat('H:i:s', $sch->end_time)->format('g:i A')   : '—';
                            $isActive = in_array($sch->status ?? '', ['active', 'approved']);
                        @endphp
                        <tr style="border-top:1px solid var(--border-color);font-size:.875rem;">
                            <td style="padding:.75rem 1rem;font-weight:600;color:var(--text-primary);">{{ $sch->subject ?? '—' }}</td>
                            <td style="padding:.75rem 1rem;color:var(--text-secondary);">{{ $sch->grade_level ?? '' }}{{ $sch->section_name ? ' – ' . $sch->section_name : '' }}</td>
                            <td style="padding:.75rem 1rem;color:var(--text-secondary);">{{ $sch->day_of_week ?? '—' }}</td>
                            <td style="padding:.75rem 1rem;color:var(--text-secondary);white-space:nowrap;">{{ $st }} &ndash; {{ $et }}</td>
                            <td style="padding:.75rem 1rem;color:var(--text-secondary);">{{ $sch->room_id ? 'Room #'.$sch->room_id : (trim(($sch->grade_level ?? '') . ($sch->section_name ? ' – '.$sch->section_name : '')) ?: 'TBA') }}</td>
                            <td style="padding:.75rem 1rem;">
                                <span style="padding:.2rem .6rem;border-radius:2rem;font-size:.7rem;font-weight:700;background:{{ $isActive ? '#dcfce7' : '#fef3c7' }};color:{{ $isActive ? '#166534' : '#92400e' }};">{{ ucfirst($sch->status ?? 'pending') }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@endsection