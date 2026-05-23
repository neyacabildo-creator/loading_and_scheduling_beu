{{-- resources/views/principal/permission-requests.blade.php --}}
@extends('layouts.principal')

@section('title', 'Admin Permission Requests')

@section('content')
<div class="header">
    <div class="header-left">
        <div>
            <h1 class="page-title">Admin Permission Requests</h1>
            <p class="page-subtitle">Review, approve, or reject requests from GS / JH admins — leave tips for guidance</p>
        </div>
    </div>
    <div class="header-right">
        <form method="GET" style="display:flex;gap:0.5rem;align-items:center;">
            <select name="status" class="form-control" style="width:auto;padding:0.4rem 0.75rem;font-size:0.8rem;" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['pending','approved','rejected','cancelled'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding:0;">
        @if($requests->isEmpty())
            <div style="padding:3rem;text-align:center;color:var(--text-secondary);">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin:0 auto 1rem;display:block;opacity:0.3;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                No requests found.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Requested By</th>
                        <th>School Level</th>
                        <th>Action Type</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $req)
                    <tr>
                        <td style="color:var(--text-secondary);font-size:0.8rem;">{{ $req->id }}</td>
                        <td>
                            <div style="font-weight:500;">{{ $req->requester?->first_name ?? '' }} {{ $req->requester?->last_name ?? '' }}</div>
                            <div style="font-size:0.75rem;color:var(--text-secondary);">{{ $req->requester?->email }}</div>
                        </td>
                        <td>
                            @if($req->school_level)
                                <span style="font-size:0.8rem;">{{ $req->school_level === 'grade_school' ? 'Grade School' : 'Junior High' }}</span>
                            @else
                                <span style="color:var(--text-secondary);font-size:0.8rem;">—</span>
                            @endif
                        </td>
                        <td><span style="font-size:0.8rem;font-weight:500;">{{ $req->actionLabel() }}</span></td>
                        <td style="max-width:200px;">
                            <div style="font-size:0.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $req->subject }}">
                                {{ $req->subject }}
                            </div>
                            <div style="font-size:0.75rem;color:var(--text-secondary);margin-top:0.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $req->details }}">
                                {{ Str::limit($req->details, 60) }}
                            </div>
                        </td>
                        <td><span class="badge badge-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                        <td style="color:var(--text-secondary);font-size:0.8rem;white-space:nowrap;">{{ $req->created_at->format('M j, Y') }}</td>
                        <td>
                            @if($req->isPending())
                                <button onclick="openModal('approve-{{ $req->id }}')" class="btn btn-success btn-sm">Approve</button>
                                <button onclick="openModal('reject-{{ $req->id }}')" class="btn btn-danger btn-sm" style="margin-left:0.25rem;">Reject</button>
                            @else
                                <button onclick="openModal('view-{{ $req->id }}')" class="btn btn-outline btn-sm">View Notes</button>
                            @endif
                        </td>
                    </tr>

                    {{-- Approve Modal --}}
                    @if($req->isPending())
                    <tr id="approve-{{ $req->id }}" style="display:none;">
                        <td colspan="8" style="background:rgba(34,197,94,0.05);padding:1.5rem;">
                            <form method="POST" action="{{ route('principal.requests.approve', $req) }}">
                                @csrf
                                @method('PATCH')
                                <p style="font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;color:#166534;">
                                    Approve: "{{ $req->subject }}"
                                </p>
                                <div class="form-group">
                                    <label class="form-label">Tip / guidance for the admin (optional)</label>
                                    <textarea name="reviewer_notes" class="form-control" rows="3" placeholder="Leave a helpful note or instruction for the admin..."></textarea>
                                </div>
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="submit" class="btn btn-success btn-sm">Confirm Approval</button>
                                    <button type="button" onclick="closeModal('approve-{{ $req->id }}')" class="btn btn-outline btn-sm">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>

                    {{-- Reject Modal --}}
                    <tr id="reject-{{ $req->id }}" style="display:none;">
                        <td colspan="8" style="background:rgba(239,68,68,0.05);padding:1.5rem;">
                            <form method="POST" action="{{ route('principal.requests.reject', $req) }}">
                                @csrf
                                @method('PATCH')
                                <p style="font-size:0.875rem;font-weight:600;margin-bottom:0.75rem;color:#991b1b;">
                                    Reject: "{{ $req->subject }}"
                                </p>
                                <div class="form-group">
                                    <label class="form-label">Reason / guidance <span style="color:#ef4444;">*</span></label>
                                    <textarea name="reviewer_notes" class="form-control" rows="3" required placeholder="Explain why this is rejected and what the admin should do instead..."></textarea>
                                </div>
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
                                    <button type="button" onclick="closeModal('reject-{{ $req->id }}')" class="btn btn-outline btn-sm">Cancel</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endif

                    {{-- View Notes Modal --}}
                    <tr id="view-{{ $req->id }}" style="display:none;">
                        <td colspan="8" style="background:var(--bg-tertiary);padding:1.5rem;">
                            <p style="font-size:0.875rem;font-weight:600;margin-bottom:0.5rem;">Request Details</p>
                            <p style="font-size:0.875rem;margin-bottom:0.75rem;color:var(--text-secondary);">{{ $req->details }}</p>
                            @if($req->reviewer_notes)
                                <p style="font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.35rem;">Principal Notes:</p>
                                <p style="font-size:0.875rem;background:var(--bg-secondary);padding:0.75rem;border-radius:0.375rem;border:1px solid var(--border-color);">{{ $req->reviewer_notes }}</p>
                            @endif
                            <div style="margin-top:0.75rem;">
                                <button type="button" onclick="closeModal('view-{{ $req->id }}')" class="btn btn-outline btn-sm">Close</button>
                            </div>
                        </td>
                    </tr>

                    @endforeach
                </tbody>
            </table>

            <div style="padding:1rem 1.5rem;">
                {{ $requests->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
function openModal(id) {
    // Close all open modals first
    document.querySelectorAll('tr[id]').forEach(r => {
        if (r.id.startsWith('approve-') || r.id.startsWith('reject-') || r.id.startsWith('view-')) {
            r.style.display = 'none';
        }
    });
    const row = document.getElementById(id);
    if (row) row.style.display = '';
}
function closeModal(id) {
    const row = document.getElementById(id);
    if (row) row.style.display = 'none';
}
</script>
@endsection
