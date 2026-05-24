@php
    $pfx = $prefix ?? 'jh';
    $dayClass = $pfx . 'd-day-btn';
    $gradeClass = $pfx . 'd-grade-btn';
    $grades = $grades ?? ($pfx === 'gs' ? ['1','2','3','4','5','6'] : ['7','8','9','10']);
@endphp
<style>
.{{ $dayClass }}{padding:.35rem .7rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-secondary);cursor:pointer;font-size:.78rem;font-weight:600;transition:all .2s;}
.{{ $dayClass }}:hover{border-color:var(--green-primary);color:var(--green-primary);}
.{{ $dayClass }}.active{background:var(--green-primary);border-color:var(--green-primary);color:#fff;}
.{{ $dayClass }}.today-marker{box-shadow:0 0 0 2px rgba(45,122,80,.35);}
.{{ $gradeClass }}{padding:.3rem .9rem;border:2px solid var(--border-color);border-radius:9999px;background:var(--bg-secondary);color:var(--text-secondary);cursor:pointer;font-size:.78rem;font-weight:600;transition:all .2s;}
.{{ $gradeClass }}:hover{border-color:var(--green-primary);color:var(--green-primary);}
.{{ $gradeClass }}.active{background:var(--green-primary);border-color:var(--green-primary);color:#fff;}
</style>
<div class="calendar-card">
    <div class="calendar-header">
        <h2 class="calendar-title">Weekly Timetable</h2>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <label style="display:flex;align-items:center;gap:0.4rem;font-size:.78rem;font-weight:600;color:var(--text-secondary);">
                Date:
                <input type="date" id="{{ $pfx }}DashTTDate"
                    style="padding:0.4rem 0.55rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.78rem;font-weight:500;cursor:pointer;">
            </label>
            <div style="display:flex;align-items:center;gap:0.25rem;">
                <button type="button" onclick="{{ $pfx }}DashTTPrevDay()" style="padding:0.35rem 0.6rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-primary);cursor:pointer;font-size:.85rem;">&#8249;</button>
                <button type="button" class="{{ $dayClass }} active" data-day="Monday" onclick="{{ $pfx }}DashTTSetDay(this,'Monday')">Mon</button>
                <button type="button" class="{{ $dayClass }}" data-day="Tuesday" onclick="{{ $pfx }}DashTTSetDay(this,'Tuesday')">Tue</button>
                <button type="button" class="{{ $dayClass }}" data-day="Wednesday" onclick="{{ $pfx }}DashTTSetDay(this,'Wednesday')">Wed</button>
                <button type="button" class="{{ $dayClass }}" data-day="Thursday" onclick="{{ $pfx }}DashTTSetDay(this,'Thursday')">Thu</button>
                <button type="button" class="{{ $dayClass }}" data-day="Friday" onclick="{{ $pfx }}DashTTSetDay(this,'Friday')">Fri</button>
                <button type="button" onclick="{{ $pfx }}DashTTNextDay()" style="padding:0.35rem 0.6rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-primary);cursor:pointer;font-size:.85rem;">&#8250;</button>
            </div>
            <input type="text" id="{{ $pfx }}DashTTFilter" placeholder="Filter by teacher…" oninput="{{ $pfx === 'jh' ? 'jhDashRenderTimetable' : 'gsRenderTimetable' }}()"
                style="padding:0.5rem 0.75rem;border:1px solid var(--border-color);border-radius:0.375rem;background:var(--bg-secondary);color:var(--text-primary);font-size:0.875rem;min-width:160px;">
        </div>
    </div>
    <div id="{{ $pfx }}DashTTDateLabel" style="font-size:.75rem;color:var(--text-secondary);padding:.25rem 1.5rem .35rem;font-style:italic;"></div>
    <div style="padding:.6rem 1.5rem;border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
        <span style="font-size:.75rem;font-weight:600;color:var(--text-secondary);">Grade:</span>
        @foreach($grades as $g)
            <button type="button" class="{{ $gradeClass }}{{ $loop->first ? ' active' : '' }}" data-grade="{{ $g }}" onclick="{{ $pfx }}DashSetGrade(this,'{{ $g }}')">Grade {{ $g }}</button>
        @endforeach
    </div>
    <div id="{{ $pfx }}DashConflictBanner" style="display:none;background:rgba(200,50,50,.1);border:1px solid #c83232;border-radius:.375rem;padding:.6rem 1rem;margin:.75rem 1.5rem;color:#c83232;font-size:.8rem;font-weight:600;">
        &#9888; Schedule conflicts detected. Conflicting entries are highlighted in red.
    </div>
    <div class="spup-table-scroll timetable-wrap" style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;min-width:400px;">
            <thead id="{{ $pfx }}DashTimetableHead"></thead>
            <tbody id="{{ $pfx }}DashTimetableBody">
                <tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-secondary);">Loading timetable…</td></tr>
            </tbody>
        </table>
    </div>
</div>
<script>
window.__DASH_TIMETABLE_CONFIG__ = {
    prefix: @json($pfx),
    school: @json($pfx === 'gs' ? 'GS' : 'JH'),
    apiUrl: @json($apiUrl ?? ''),
    initial: @json($initial ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE),
    slots: @json($slots ?? []),
    slotsByDay: @json($slotsByDay ?? null),
    sections: @json($sections ?? []),
    grades: @json($grades),
};
</script>
<script src="{{ asset('js/admin-dashboard-timetable.js') }}?v=4"></script>
