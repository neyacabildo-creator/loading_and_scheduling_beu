@php
    $weekdays = $weekdays ?? \App\Support\KinderScheduleSupport::WEEKDAYS;
    $weeklyActivity = $weeklyActivity ?? \App\Support\KinderScheduleSupport::WEEKLY_ACTIVITY_BY_DAY;
    $activitySubjects = $activitySubjects ?? \App\Support\KinderScheduleSupport::ACTIVITY_SUBJECTS;
    $gradeTitle = $gradeTitle ?? null;
    $singleDay = $singleDay ?? null;
    $visibleDays = $singleDay
        ? (in_array($singleDay, $weekdays, true) ? [$singleDay] : [])
        : $weekdays;
@endphp
<table class="ks-table" id="gsKinderActivityTable" style="width:100%;border-collapse:collapse;font-size:0.82rem;">
    @if($gradeTitle)
        <thead>
            <tr>
                <th colspan="2" style="background:var(--bg-tertiary);text-align:center;font-weight:800;text-transform:uppercase;letter-spacing:.05em;padding:.55rem;border:1px solid var(--border-color);">
                    {{ $gradeTitle }}
                </th>
            </tr>
        </thead>
    @endif
    <thead>
        <tr>
            <th style="background:var(--bg-tertiary);padding:.5rem;border:1px solid var(--border-color);width:35%;">TIME</th>
            <th style="background:var(--bg-tertiary);padding:.5rem;border:1px solid var(--border-color);">Subjects</th>
        </tr>
    </thead>
    <tbody id="gsKinderScheduleBody">
        @forelse($visibleDays as $day)
            <tr data-kinder-day="{{ $day }}">
                <td style="border:1px solid var(--border-color);padding:.5rem;font-weight:700;text-transform:uppercase;">{{ strtoupper($day) }}</td>
                <td style="border:1px solid var(--border-color);padding:.35rem;">
                    <select name="activity[{{ $day }}]" required title="Subjects" style="width:100%;padding:.45rem;border:1px solid var(--border-color);border-radius:.35rem;background:var(--bg-secondary);">
                        <option value="">Select subject</option>
                        @foreach($activitySubjects as $subj)
                            <option value="{{ $subj }}" @selected(($weeklyActivity[$day] ?? '') === $subj)>{{ $subj }}</option>
                        @endforeach
                    </select>
                </td>
            </tr>
        @empty
            <tr id="gsKinderDayPlaceholder">
                <td colspan="2" style="border:1px solid var(--border-color);padding:1rem;text-align:center;color:var(--text-secondary);font-size:0.85rem;">
                    Select a day of week above to assign a subject.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
