@extends($layout)

@section('title', 'Generate Schedule')

@section('content')
<div class="header">
    <div class="header-left">
        <h1 class="page-title">Auto Schedule Generator</h1>
        <p class="page-subtitle">Dry-run proposal — review conflicts before importing</p>
    </div>
</div>

<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-body">
        <p style="font-size:.875rem;color:var(--text-secondary);margin:0 0 1rem;">
            Teachers on file: <strong>{{ $teacherCount }}</strong> &nbsp;|&nbsp;
            Active loads: <strong>{{ $loadCount }}</strong> &nbsp;|&nbsp;
            Available rooms: <strong>{{ $roomCount }}</strong>
        </p>
        <form method="POST" action="{{ $isGs ? route('grade-school-admin.schedule.generate.preview') : route('admin.schedule.generate.preview') }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1rem;">
                <div>
                    <label style="font-size:.8rem;font-weight:600;">School year</label>
                    <input type="text" name="school_year" value="{{ old('school_year', $schoolYear) }}" required
                        style="width:100%;padding:.55rem;border:1px solid var(--border-color);border-radius:.375rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;">Max classes per teacher / day</label>
                    <input type="number" name="max_per_day" value="{{ old('max_per_day', 2) }}" min="1" max="6" required
                        style="width:100%;padding:.55rem;border:1px solid var(--border-color);border-radius:.375rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;">Day start</label>
                    <input type="time" name="start_hour" value="{{ old('start_hour', '07:00') }}" required
                        style="width:100%;padding:.55rem;border:1px solid var(--border-color);border-radius:.375rem;">
                </div>
                <div>
                    <label style="font-size:.8rem;font-weight:600;">Day end</label>
                    <input type="time" name="end_hour" value="{{ old('end_hour', '17:00') }}" required
                        style="width:100%;padding:.55rem;border:1px solid var(--border-color);border-radius:.375rem;">
                </div>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="font-size:.8rem;font-weight:600;display:block;margin-bottom:.5rem;">Days</label>
                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                    <label style="margin-right:1rem;font-size:.85rem;">
                        <input type="checkbox" name="days[]" value="{{ $d }}"
                            {{ in_array($d, old('days', ['Monday','Tuesday','Wednesday','Thursday','Friday'])) ? 'checked' : '' }}>
                        {{ $d }}
                    </label>
                @endforeach
            </div>
            <div style="margin-bottom:1rem;">
                <label style="font-size:.8rem;font-weight:600;">Sections (comma-separated, optional)</label>
                <input type="text" name="sections" value="{{ old('sections') }}" placeholder="e.g. Grade 7-A, Grade 7-B"
                    style="width:100%;padding:.55rem;border:1px solid var(--border-color);border-radius:.375rem;">
                <p style="font-size:.75rem;color:var(--text-secondary);margin:.35rem 0 0;">Examples: {{ implode(', ', array_slice($suggestedSections, 0, 4)) }}…</p>
            </div>
            @if($errors->any())
                <div style="color:#dc2626;font-size:.85rem;margin-bottom:1rem;">{{ $errors->first() }}</div>
            @endif
            <button type="submit" class="btn btn-primary">Preview generated schedule</button>
        </form>
    </div>
</div>
@endsection
