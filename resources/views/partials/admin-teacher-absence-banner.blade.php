{{-- Active approved leave/absence — JH & GS admin. Expects $leaveBanner or $absentToday. --}}
@once
    @include('partials.admin-all-requests-styles')
@endonce
@php
    $leaveBanner = $leaveBanner ?? $absentToday ?? ['regular' => [], 'shared' => []];
    $absentRegular = $leaveBanner['regular'] ?? [];
    $absentShared = $leaveBanner['shared'] ?? [];
    $hasAny = count($absentRegular) > 0 || count($absentShared) > 0;
@endphp
@if($hasAny)
    <div class="str-absent-alert" role="alert">
        <strong>Teachers absent or on leave — transfer schedules</strong>
        <p style="margin:.5rem 0 .75rem;font-size:.82rem;color:var(--text-secondary);line-height:1.45;">
            The following faculty are <strong>not available</strong> for scheduling today. Do not assign new loads or class schedules to them during their leave period.
            Reassign their classes to other available teachers in <strong>Faculty Loading</strong> or <strong>Class Schedule</strong>.
        </p>
        @if(count($absentShared) > 0)
            <div class="str-absent-group">
                <span class="str-absent-group-label">Shared teachers:</span>
                @foreach($absentShared as $person)
                    <span class="str-absent-chip str-presence-{{ ($person['status'] ?? '') === 'absent' ? 'absent' : 'on_leave' }}" title="{{ $person['date_range'] ?? '' }}">
                        {{ $person['name'] }} — {{ $person['label'] }}
                        @if(!empty($person['total_days']))
                            <em>({{ $person['total_days'] }} {{ $person['total_days'] == 1 ? 'day' : 'days' }})</em>
                        @endif
                        @php $sections = $person['sections_affected_this_week'] ?? []; @endphp
                        @if(count($sections) > 0)
                            <em class="str-leave-sections-hint"> · {{ count($sections) }} section{{ count($sections) === 1 ? '' : 's' }} affected this week</em>
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
        @if(count($absentRegular) > 0)
            <div class="str-absent-group">
                <span class="str-absent-group-label">Teachers:</span>
                @foreach($absentRegular as $person)
                    <span class="str-absent-chip str-presence-{{ ($person['status'] ?? '') === 'absent' ? 'absent' : 'on_leave' }}" title="{{ $person['date_range'] ?? '' }}">
                        {{ $person['name'] }} — {{ $person['label'] }}
                        @if(!empty($person['total_days']))
                            <em>({{ $person['total_days'] }} {{ $person['total_days'] == 1 ? 'day' : 'days' }})</em>
                        @endif
                        @php $sections = $person['sections_affected_this_week'] ?? []; @endphp
                        @if(count($sections) > 0)
                            <em class="str-leave-sections-hint"> · {{ count($sections) }} section{{ count($sections) === 1 ? '' : 's' }} affected this week</em>
                        @endif
                    </span>
                @endforeach
            </div>
        @endif
        @php
            $allAbsent = array_merge($absentShared, $absentRegular);
            $withSections = array_filter($allAbsent, fn ($p) => !empty($p['sections_affected_this_week']));
        @endphp
        @if(count($withSections) > 0)
            <div class="str-leave-sections-detail" style="margin-top:.85rem;padding-top:.75rem;border-top:1px dashed var(--border-color);">
                <strong style="font-size:.8rem;display:block;margin-bottom:.5rem;">Sections affected this week</strong>
                @foreach($withSections as $person)
                    @php $sections = $person['sections_affected_this_week'] ?? []; @endphp
                    @if(count($sections) > 0)
                        <div style="margin-bottom:.6rem;">
                            <span style="font-size:.78rem;font-weight:600;color:var(--text-primary);">{{ $person['name'] }}</span>
                            <ul style="margin:.25rem 0 0 1.1rem;font-size:.76rem;color:var(--text-secondary);line-height:1.5;">
                                @foreach(array_slice($sections, 0, 6) as $sec)
                                    <li>{{ $sec['grade_level'] }} {{ $sec['section_name'] }} — {{ $sec['subject'] }} ({{ $sec['day_of_week'] }}, {{ $sec['time'] }})</li>
                                @endforeach
                                @if(count($sections) > 6)
                                    <li><em>+{{ count($sections) - 6 }} more</em></li>
                                @endif
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endif
