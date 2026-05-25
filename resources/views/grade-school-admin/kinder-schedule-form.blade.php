@extends('layouts.grade-school-admin')

@section('title', 'Kinder Class Schedule')

@section('content')
<style>
    .ks-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .ks-panel { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1.25rem; }
    .ks-panel h3 { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: var(--green-primary); margin: 0 0 0.75rem; }
    .ks-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
</style>

<div class="ks-header">
    <div>
        <h1 style="margin:0 0 0.35rem;font-size:1.5rem;font-weight:800;">Kinder Class Schedule</h1>
        <p style="margin:0;color:var(--text-secondary);font-size:0.875rem;">Assign one subject per weekday (Reading, Language, Filipino, Mathematics, CLVE/PE/Arts).</p>
    </div>
    <a href="{{ route('grade-school-admin.faculty-loading') }}" class="action-btn" style="text-decoration:none;">← Faculty Loads</a>
</div>

@if(session('success'))
    <div style="padding:0.85rem 1rem;background:rgba(45,122,80,0.1);border:1px solid rgba(45,122,80,0.3);border-radius:0.5rem;margin-bottom:1rem;color:#166534;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div style="padding:0.85rem 1rem;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.3);border-radius:0.5rem;margin-bottom:1rem;color:#b91c1c;">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div style="padding:0.85rem 1rem;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.3);border-radius:0.5rem;margin-bottom:1rem;color:#b91c1c;">
        <ul style="margin:.35rem 0 0 1rem;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('grade-school-admin.kinder-schedule.store') }}" id="kinderScheduleForm">
    @csrf
    <div class="ks-panel">
        <h3>Section &amp; Teacher</h3>
        <div class="ks-grid">
            <div>
                <label style="font-size:0.75rem;font-weight:700;display:block;margin-bottom:0.35rem;">Grade Level </label>
                <select name="grade_level" id="ksGrade" required style="width:100%;padding:0.55rem;border:1px solid var(--border-color);border-radius:0.375rem;">
                    @foreach(\App\Support\KinderScheduleSupport::GRADES as $g)
                        <option value="{{ $g }}" @selected(old('grade_level', $gradeLevel) === $g)>{{ $g }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:0.75rem;font-weight:700;display:block;margin-bottom:0.35rem;">Room / Section </label>
                <select name="section_name" id="ksSection" required style="width:100%;padding:0.55rem;border:1px solid var(--border-color);border-radius:0.375rem;">
                    @foreach($sections as $sec)
                        <option value="{{ $sec }}" @selected(old('section_name', $sectionName) === $sec)>{{ $sec }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size:0.75rem;font-weight:700;display:block;margin-bottom:0.35rem;">Teacher </label>
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
        <h3>Weekly Subjects</h3>
        @include('partials.kinder-weekly-activity-table', [
            'gradeTitle' => strtoupper($gradeLevel),
            'weeklyActivity' => $weeklyActivity,
            'activitySubjects' => $activitySubjects,
            'weekdays' => $weekdays,
        ])
        <p style="font-size:0.75rem;color:var(--text-secondary);margin-top:0.75rem;">Each subject may only appear once across the week.</p>
    </div>

    <div style="display:flex;gap:1rem;">
        <button type="submit" class="sf-submit-btn" style="padding:0.75rem 2rem;background:linear-gradient(135deg,var(--green-primary),#0d3d20);color:#fff;border:none;border-radius:0.5rem;font-weight:600;cursor:pointer;">
            Save Kinder Schedule
        </button>
        <a href="{{ route('grade-school-admin.faculty-loading') }}" class="sf-cancel-link" style="padding:0.75rem 1.5rem;text-decoration:none;">Cancel</a>
    </div>
</form>

<script>
(function () {
    var sectionsByGrade = @json(\App\Support\KinderScheduleSupport::SECTIONS_BY_GRADE);
    var gradeSel = document.getElementById('ksGrade');
    var sectionSel = document.getElementById('ksSection');
    if (!gradeSel || !sectionSel) return;

    function refreshSections() {
        var grade = gradeSel.value;
        var list = sectionsByGrade[grade] || [];
        var current = sectionSel.value;
        sectionSel.innerHTML = '';
        list.forEach(function (sec) {
            var opt = document.createElement('option');
            opt.value = sec;
            opt.textContent = sec;
            if (sec === current) opt.selected = true;
            sectionSel.appendChild(opt);
        });
    }

    gradeSel.addEventListener('change', refreshSections);
})();
</script>
@endsection
