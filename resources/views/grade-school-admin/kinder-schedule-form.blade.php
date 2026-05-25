@extends('layouts.grade-school-admin')

@section('title', 'Kinder Class Schedule')

@section('content')
<style>
    .ks-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .ks-panel { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1.25rem; }
    .ks-panel h3 { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--green-primary); margin: 0 0 0.75rem; }
    .ks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    .ks-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .ks-table th, .ks-table td { border: 1px solid var(--border-color); padding: 0.45rem; text-align: center; }
    .ks-table th { background: var(--bg-tertiary); font-weight: 700; font-size: 0.72rem; text-transform: uppercase; }
    .ks-table .time-col { text-align: left; font-weight: 700; min-width: 130px; }
    .ks-table tr.activity-row td { background: rgba(45, 122, 80, 0.08); }
    .ks-table select { width: 100%; padding: 0.4rem; border: 1px solid var(--border-color); border-radius: 0.35rem; background: var(--bg-secondary); color: var(--text-primary); font-size: 0.78rem; }
</style>

<div class="ks-header">
    <div>
        <h1 style="margin:0 0 0.35rem;font-size:1.5rem;font-weight:800;">Kinder Class Schedule</h1>
        <p style="margin:0;color:var(--text-secondary);font-size:0.875rem;">Kinder 2 (morning), Kinder 1 &amp; Nursery (afternoon). Activity row uses Reading, Language, Filipino, Mathematics, CLVE/PE/Arts.</p>
    </div>
    <a href="{{ route('grade-school-admin.faculty-loading') }}" class="action-btn" style="text-decoration:none;">← Faculty Loads</a>
</div>

@if(session('success'))
    <div style="padding:0.85rem 1rem;background:rgba(45,122,80,0.1);border:1px solid rgba(45,122,80,0.3);border-radius:0.5rem;margin-bottom:1rem;color:#166534;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div style="padding:0.85rem 1rem;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.3);border-radius:0.5rem;margin-bottom:1rem;color:#b91c1c;">{{ session('error') }}</div>
@endif

<form method="POST" action="{{ route('grade-school-admin.kinder-schedule.store') }}" id="kinderScheduleForm">
    @csrf
    <div class="ks-panel">
        <h3>Section &amp; Teacher</h3>
        <div class="ks-grid">
            <div>
                <label style="font-size:0.75rem;font-weight:700;display:block;margin-bottom:0.35rem;">Grade Level *</label>
                <select name="grade_level" id="ksGrade" required style="width:100%;padding:0.55rem;border:1px solid var(--border-color);border-radius:0.375rem;">
                    @foreach(\App\Support\KinderScheduleSupport::GRADES as $g)
                        <option value="{{ $g }}" @selected(old('grade_level', $gradeLevel) === $g)>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:0.75rem;font-weight:700;display:block;margin-bottom:0.35rem;">Room / Section *</label>
                <select name="section_name" id="ksSection" required style="width:100%;padding:0.55rem;border:1px solid var(--border-color);border-radius:0.375rem;">
                    @foreach($sections as $sec)
                        <option value="{{ $sec }}" @selected(old('section_name', $sectionName) === $sec)>{{ $sec }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:0.75rem;font-weight:700;display:block;margin-bottom:0.35rem;">Teacher *</label>
                <select name="faculty_id" required style="width:100%;padding:0.55rem;border:1px solid var(--border-color);border-radius:0.375rem;">
                    <option value="">— Select —</option>
                    @foreach($teachers as $t)
                        <option value="{{ $t->id }}" @selected((int) old('faculty_id', $facultyId) === (int) $t->id)>
                            {{ trim($t->first_name . ' ' . $t->last_name) ?: $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="ks-panel">
        <h3>Class Routine (fixed times — Monday to Friday)</h3>
        <div style="overflow-x:auto;">
            <table class="ks-table">
                <thead>
                    <tr>
                        <th class="time-col">TIME</th>
                        @foreach($weekdays as $day)
                            <th>{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($routineSlots as $slot)
                        <tr class="{{ ($slot['type'] ?? '') === 'activity' ? 'activity-row' : '' }}">
                            <td class="time-col">{{ substr($slot['start'], 0, 5) }} – {{ substr($slot['end'], 0, 5) }}<br><small>{{ $slot['label'] }}</small></td>
                            @foreach($weekdays as $day)
                                <td>
                                    @if(($slot['type'] ?? '') === 'activity')
                                        <select name="activity[{{ $day }}]" required>
                                            @foreach($activitySubjects as $subj)
                                                <option value="{{ $subj }}" @selected(($weeklyActivity[$day] ?? '') === $subj)>{{ $subj }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        {{ $slot['label'] }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <button type="submit" class="action-btn action-btn-primary" style="background:var(--green-primary);color:#fff;border:none;padding:0.75rem 1.5rem;border-radius:0.4rem;font-weight:700;cursor:pointer;">Save Kinder Schedule</button>
</form>

<script>
(function () {
    var sectionsByGrade = @json(\App\Support\KinderScheduleSupport::SECTIONS_BY_GRADE);
    var gradeEl = document.getElementById('ksGrade');
    var sectionEl = document.getElementById('ksSection');
    if (!gradeEl || !sectionEl) return;

    function refillSections() {
        var grade = gradeEl.value;
        var list = sectionsByGrade[grade] || [];
        var current = sectionEl.value;
        sectionEl.innerHTML = '';
        list.forEach(function (s) {
            var o = document.createElement('option');
            o.value = s;
            o.textContent = s;
            if (s === current) o.selected = true;
            sectionEl.appendChild(o);
        });
    }

    gradeEl.addEventListener('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('grade_level', gradeEl.value);
        if (sectionEl.options.length) {
            url.searchParams.set('section_name', sectionEl.value);
        }
        window.location.href = url.toString();
    });
})();
</script>
@endsection
