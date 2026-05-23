@php
    $teacherLeaveRequests = $teacherLeaveRequests ?? collect();
    $approveRouteName = $approveRouteName ?? 'admin.teacher-leave-requests.approve';
    $rejectRouteName = $rejectRouteName ?? 'admin.teacher-leave-requests.reject';
@endphp

@if($teacherLeaveRequests->isEmpty())
    <div class="str-empty-state">
        <p>No absence or leave requests yet.</p>
    </div>
@else
    <div class="str-table-wrap">
        <table class="str-data-table str-teacher-requests-table">
            <thead>
                <tr>
                    <th class="str-col-teacher">Teacher</th>
                    <th class="str-col-subject">Leave Type</th>
                    <th class="str-col-time">Date Range</th>
                    <th class="str-col-notes">Reason / Notes</th>
                    <th class="str-col-date">Submitted</th>
                    <th class="str-col-status">Status</th>
                    <th class="str-col-actions">Action / Review</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teacherLeaveRequests as $lr)
                <tr data-status="{{ $lr->status }}">
                    <td class="str-col-teacher">
                        @if($lr->user ?? null)
                            @php
                                $leaveTeacher = trim(($lr->user->first_name ?? '') . ' ' . ($lr->user->last_name ?? '')) ?: ($lr->user->name ?? '—');
                                $leaveSchool = ($lr->user->school_level ?? null)
                                    ? ucfirst(str_replace('_', ' ', $lr->user->school_level))
                                    : '';
                            @endphp
                            <div class="str-teacher-cell">
                                <div class="str-teacher-cell-top">
                                    <span class="str-teacher-name">{{ $leaveTeacher }}</span>
                                    @if($leaveSchool !== '')
                                        <span class="str-school-level-badge">{{ $leaveSchool }}</span>
                                    @endif
                                </div>
                                @if(!empty($lr->presence))
                                    <span class="str-presence-badge str-presence-{{ $lr->presence['status'] ?? 'on_leave' }}">{{ $lr->presence['label'] ?? 'On Leave' }}</span>
                                @endif
                            </div>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td class="str-col-subject">
                        <div class="str-cell-subject">{{ $lr->request_type_label ?? 'Leave' }}</div>
                        @if(!empty($lr->total_days))
                            <div class="str-request-type-meta">{{ $lr->total_days }} day(s)</div>
                        @endif
                    </td>
                    <td class="str-col-time str-cell-daytime">
                        <div class="str-day-line">{{ $lr->leave_dates ?? '' }}</div>
                    </td>
                    <td class="str-col-notes str-cell-notes">
                        @if(!empty($lr->reason))
                            <div class="str-notes-reason" title="{{ $lr->reason }}">{{ $lr->reason }}</div>
                        @endif
                    </td>
                    <td class="str-col-date str-date-cell">{{ \Carbon\Carbon::parse($lr->created_at)->format('M d, Y') }}</td>
                    <td class="str-col-status"><span class="status-{{ $lr->status }}">{{ ucfirst($lr->status) }}</span></td>
                    <td class="str-col-actions">
                        @if($lr->status === 'pending')
                            @include('partials.admin-request-actions-pending', [
                                'approveRoute' => route($approveRouteName, $lr->id),
                                'rejectRoute'  => route($rejectRouteName, $lr->id),
                            ])
                        @else
                            <div class="str-reviewed-box">
                                @if(!empty($lr->admin_notes))
                                    <div class="str-reviewed-quote">"{{ $lr->admin_notes }}"</div>
                                @endif
                                @if($lr->reviewer ?? null)
                                    <div>By: <strong>{{ trim(($lr->reviewer->first_name ?? '') . ' ' . ($lr->reviewer->last_name ?? '')) }}</strong></div>
                                @endif
                                @if($lr->reviewed_at ?? null)
                                    <div style="font-size:.73rem;margin-top:.15rem;">{{ \Carbon\Carbon::parse($lr->reviewed_at)->format('M d, Y') }}</div>
                                @endif
                                @if(empty($lr->admin_notes) && empty($lr->reviewer) && empty($lr->reviewed_at))
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
