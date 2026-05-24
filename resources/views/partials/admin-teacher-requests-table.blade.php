@php
    use App\Support\AdminRequestDisplay;
    $approveRouteName = $approveRouteName ?? 'admin.teacher-schedule-requests.approve';
    $rejectRouteName = $rejectRouteName ?? 'admin.teacher-schedule-requests.reject';
@endphp

@if($teacherScheduleRequests->isEmpty())
    <div class="str-empty-state">
        <p>No teacher requests yet.</p>
    </div>
@else
    <div class="str-table-wrap">
        <table id="tsrTable" class="str-data-table str-teacher-requests-table">
            <thead>
                <tr>
                    <th class="str-col-teacher">Teacher</th>
                    <th class="str-col-subject">Subject / Type</th>
                    <th class="str-col-grade">Grade / Section</th>
                    <th class="str-col-time">Preferred Date, Day &amp; Time</th>
                    <th class="str-col-notes">Notes</th>
                    <th class="str-col-date">Submitted</th>
                    <th class="str-col-status">Status</th>
                    <th class="str-col-actions">Action / Review</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teacherScheduleRequests as $tsr)
                @php $view = AdminRequestDisplay::teacherRequestView($tsr); @endphp
                <tr data-status="{{ $tsr->status }}">
                    <td class="str-col-teacher">
                        @php
                            $teacherDisplay = ($tsr->user ?? null)
                                ? (trim(($tsr->user->first_name ?? '') . ' ' . ($tsr->user->last_name ?? '')) ?: ($tsr->user->name ?? ''))
                                : trim((string) ($tsr->teacher_name ?? ''));
                            $schoolLevelLabel = ($tsr->user && !empty($tsr->user->school_level))
                                ? ucfirst(str_replace('_', ' ', $tsr->user->school_level))
                                : '';
                        @endphp
                        @if($teacherDisplay !== '')
                            <div class="str-teacher-cell">
                                <div class="str-teacher-cell-top">
                                    <span class="str-teacher-name">{{ $teacherDisplay }}</span>
                                    @if($schoolLevelLabel !== '')
                                        <span class="str-school-level-badge">{{ $schoolLevelLabel }}</span>
                                    @endif
                                </div>
                                @if(!empty($tsr->presence))
                                    <span class="str-presence-badge str-presence-{{ $tsr->presence['status'] ?? 'on_leave' }}" title="Active {{ $tsr->presence['date_from'] ?? '' }} – {{ $tsr->presence['date_to'] ?? '' }}">{{ $tsr->presence['label'] ?? 'On Leave' }}</span>
                                @endif
                            </div>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td class="str-col-subject">
                        <div class="str-cell-subject">{{ $view['subject'] }}</div>
                        <div class="str-request-type-meta">{{ $view['request_type_label'] }}</div>
                    </td>
                    <td class="str-col-grade str-cell-grade">@if($view['grade_section'] !== ''){{ $view['grade_section'] }}@endif</td>
                    <td class="str-col-time str-cell-daytime">
                        @if(!empty($view['has_adjustment_date']))
                            <div class="str-day-line"><strong>Date:</strong> {{ $view['adjustment_date'] }}</div>
                        @endif
                        @if(!empty($view['has_leave_dates']))
                            <div class="str-day-line">{{ $view['leave_dates'] }}</div>
                        @endif
                        @if(($view['day'] ?? '') !== '')
                            <div class="str-day-line">{{ $view['day'] }}</div>
                        @endif
                        @if($view['has_time'])
                            <div class="str-time-line">{{ $view['time_range'] }}</div>
                        @endif
                    </td>
                    <td class="str-col-notes str-cell-notes">
                        @if($view['reason'] !== '')
                            <div class="str-notes-reason" title="{{ $view['reason'] }}">{{ $view['reason'] }}</div>
                        @endif
                        @if(!empty($view['detail']))
                            <div class="str-notes-detail" title="{{ $view['detail'] }}">{{ $view['detail'] }}</div>
                        @endif
                    </td>
                    <td class="str-col-date str-date-cell">{{ \Carbon\Carbon::parse($tsr->created_at)->format('M d, Y') }}</td>
                    <td class="str-col-status"><span class="status-{{ $tsr->status }}">{{ ucfirst($tsr->status) }}</span></td>
                    <td class="str-col-actions">
                        @if($tsr->status === 'pending')
                            @include('partials.admin-request-impact', [
                                'tsr' => $tsr,
                                'adminConnection' => $adminConnection ?? 'mysql_jh',
                                'schoolLevel' => $schoolLevel ?? 'junior_high',
                            ])
                            @include('partials.admin-request-actions-pending', [
                                'approveRoute' => route($approveRouteName, $tsr->id),
                                'rejectRoute'  => route($rejectRouteName, $tsr->id),
                            ])
                        @else
                            <div class="str-reviewed-box">
                                @if(!empty($tsr->admin_notes))
                                    <div class="str-reviewed-quote">"{{ $tsr->admin_notes }}"</div>
                                @endif
                                @if($tsr->reviewer ?? null)
                                    <div>By: <strong>{{ trim(($tsr->reviewer->first_name ?? '') . ' ' . ($tsr->reviewer->last_name ?? '')) }}</strong></div>
                                @endif
                                @if($tsr->reviewed_at ?? null)
                                    <div style="font-size:.73rem;margin-top:.15rem;">{{ \Carbon\Carbon::parse($tsr->reviewed_at)->format('M d, Y') }}</div>
                                @endif
                                @if(empty($tsr->admin_notes) && empty($tsr->reviewer) && empty($tsr->reviewed_at))
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
