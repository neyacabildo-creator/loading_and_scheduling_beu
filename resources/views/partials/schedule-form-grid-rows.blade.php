{{-- Expects: $scheduleFormRows, $allTeachersForDropdown, $subjectOptions, $sectionCount (default 5) --}}
@php
    $sectionCount = (int) ($sectionCount ?? 5);
    $subjectOptions = $subjectOptions ?? [];
@endphp
@foreach($scheduleFormRows as $row)
    @if($row['is_break'])
        <tr class="break-row">
            <td class="time-col" style="background:rgba(245,158,11,.07);font-size:.68rem;color:#92400e;">{{ $row['time_top'] }}<br>{{ $row['time_bottom'] }}</td>
            <td colspan="{{ $sectionCount }}">✦ {{ $row['break_title'] }} ✦</td>
        </tr>
    @else
        <tr>
            <td class="time-col">{{ $row['time_top'] }}<br>{{ $row['time_bottom'] }}</td>
            @for($i = 0; $i < $sectionCount; $i++)
                @php
                    $subjectOld = old("slots.{$row['key']}.{$i}.subject", '');
                    $teacherOld = old("slots.{$row['key']}.{$i}.faculty_id", '');
                @endphp
                <td class="sf-cell">
                    <select name="slots[{{ $row['key'] }}][{{ $i }}][subject]" class="sf-subject">
                        <option value="">— Subject —</option>
                        @foreach($subjectOptions as $sub)
                            <option value="{{ $sub }}" @selected($subjectOld === $sub)>{{ $sub }}</option>
                        @endforeach
                    </select>
                    <select name="slots[{{ $row['key'] }}][{{ $i }}][faculty_id]" class="sf-teacher">
                        <option value="">— Teacher —</option>
                        @foreach($allTeachersForDropdown as $t)
                            <option value="{{ $t['id'] }}" @selected((string) $teacherOld === (string) $t['id'])>{{ $t['name'] }}</option>
                        @endforeach
                    </select>
                </td>
            @endfor
        </tr>
    @endif
@endforeach
