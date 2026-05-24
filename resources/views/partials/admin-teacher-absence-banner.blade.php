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
                    </span>
                @endforeach
            </div>
        @endif
    </div>
@endif
