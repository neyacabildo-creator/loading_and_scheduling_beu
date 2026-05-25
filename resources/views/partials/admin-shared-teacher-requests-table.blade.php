@php
    use App\Support\AdminRequestDisplay;
    use App\Support\UserProfileSupport;
    $approveRouteName = $approveRouteName ?? 'admin.shared-teacher-requests.approve';
    $rejectRouteName = $rejectRouteName ?? 'admin.shared-teacher-requests.reject';
    $teacherUsers = $teacherUsers ?? collect();
@endphp

@if($requests->isEmpty())
    <div class="str-empty-state">
        <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 1rem;display:block;opacity:.35;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <p>No schedule requests received yet.</p>
    </div>
@else
    <div class="str-table-wrap">
        <table id="strTable" class="str-data-table">
            <thead>
                <tr>
                    <th class="str-col-teacher">Teacher</th>
                    <th class="str-col-subject">Subject</th>
                    <th class="str-col-grade">Grade / Section</th>
                    <th class="str-col-time">Preferred Day &amp; Time</th>
                    <th class="str-col-notes">Notes</th>
                    <th class="str-col-date">Submitted</th>
                    <th class="str-col-status">Status</th>
                    <th class="str-col-actions">Action / Review</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                @php
                    $teacherUser = $teacherUsers->get((int) ($req->faculty_id ?? 0));
                    $teacherDisplay = $teacherUser ? UserProfileSupport::displayName($teacherUser) : ($req->teacher_name ?? '—');
                    $gradeLabel = AdminRequestDisplay::gradeSectionLabel(null, $req->grade_level ?? null, $req->section_name ?? null);
                    $dayLabel = trim((string) ($req->day_of_week ?? ''));
                    $timeRange = AdminRequestDisplay::formatTimeRange($req->preferred_start_time ?? null, $req->preferred_end_time ?? null);
                    $searchHaystack = strtolower(implode(' ', array_filter([
                        $teacherDisplay,
                        $req->subject ?? '',
                        $gradeLabel,
                        $req->grade_level ?? '',
                        $req->section_name ?? '',
                        $dayLabel,
                        $timeRange,
                        $req->status ?? '',
                    ])));
                @endphp
                <tr data-status="{{ $req->status }}" data-search="{{ $searchHaystack }}">
                    <td class="str-col-teacher">
                        <div class="str-teacher-cell">
                            <div class="str-teacher-cell-top" style="display:flex;align-items:center;gap:0.5rem;">
                                @include('partials.user-avatar', ['user' => $teacherUser, 'size' => 32])
                                <span class="str-teacher-name">{{ $teacherDisplay }}</span>
                                @if(!empty($req->school_level))
                                    <span class="str-school-level-badge">{{ ucfirst(str_replace('_', ' ', $req->school_level)) }}</span>
                                @endif
                            </div>
                            @if(!empty($req->presence))
                                <span class="str-presence-badge str-presence-{{ $req->presence['status'] ?? 'on_leave' }}">{{ $req->presence['label'] ?? 'On Leave' }}</span>
                            @endif
                        </div>
                    </td>
                    <td class="str-col-subject str-cell-subject" style="font-weight:500;">{{ $req->subject ?? '—' }}</td>
                    <td class="str-col-grade">
                        @if($gradeLabel !== ''){{ $gradeLabel }}@endif
                    </td>
                    <td class="str-col-time">
                        @if($dayLabel !== '')
                            <div class="str-day-line">{{ $dayLabel }}</div>
                        @endif
                        @if($timeRange !== '')
                            <div class="str-time-line">{{ $timeRange }}</div>
                        @endif
                    </td>
                    <td class="str-col-notes">
                        @if(trim((string) ($req->description ?? '')) !== '')
                            <span class="str-notes-text" title="{{ $req->description }}">{{ $req->description }}</span>
                        @endif
                    </td>
                    <td class="str-col-date str-date-cell">{{ \Carbon\Carbon::parse($req->created_at)->format('M d, Y') }}</td>
                    <td class="str-col-status"><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                    <td class="str-col-actions">
                        @if($req->status === 'pending')
                            @include('partials.admin-request-actions-pending', [
                                'approveRoute' => route($approveRouteName, $req->id),
                                'rejectRoute'  => route($rejectRouteName, $req->id),
                            ])
                        @else
                            <div class="str-reviewed-box">
                                @if(!empty($req->admin_notes))
                                    <div class="str-reviewed-quote">"{{ $req->admin_notes }}"</div>
                                @endif
                                @if($req->reviewed_by && isset($reviewers[$req->reviewed_by]))
                                    @php $rv = $reviewers[$req->reviewed_by]; @endphp
                                    <div>By: <strong>{{ trim(($rv->first_name ?? '') . ' ' . ($rv->last_name ?? '')) }}</strong></div>
                                @endif
                                @if($req->reviewed_at ?? null)
                                    <div style="font-size:.73rem;margin-top:.15rem;">{{ \Carbon\Carbon::parse($req->reviewed_at)->format('M d, Y') }}</div>
                                @endif
                                @if(empty($req->admin_notes) && empty($req->reviewed_by))
                                    <span style="opacity:.5;">—</span>
                                @endif
                            </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
