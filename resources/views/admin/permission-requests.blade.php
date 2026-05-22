{{-- resources/views/admin/permission-requests.blade.php
     Shown to both JH and GS admins so they can ask Principal for approval / tips
--}}
@php
    $isGs = Auth::user()->role?->name === 'admin_grade_school';
    $storeRoute  = $isGs ? 'grade-school-admin.permission-requests.store'  : 'admin.permission-requests.store';
    $cancelRoute = $isGs ? 'grade-school-admin.permission-requests.cancel' : 'admin.permission-requests.cancel';
    $layout      = $isGs ? 'layouts.grade-school-admin' : 'layouts.admin';
@endphp
@extends($layout)

@section('title', 'Permission Requests')

@section('content')
<div class="header">
    <div class="header-left">
        <div>
            <h1 class="page-title">Permission Requests</h1>
            <p style="font-size:0.875rem;color:var(--text-secondary);margin-top:0.25rem;">
                Ask the Principal (Principal / Secretary) for guidance or approval before performing sensitive actions.
            </p>
        </div>
    </div>
</div>

@if(session('success'))
    <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#166534;padding:0.875rem 1rem;border-radius:0.5rem;margin-bottom:1.25rem;font-size:0.875rem;">
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#991b1b;padding:0.875rem 1rem;border-radius:0.5rem;margin-bottom:1.25rem;font-size:0.875rem;">
        {{ session('error') }}
    </div>
@endif

<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.75rem;align-items:start;">

    {{-- Submit new request --}}
    <div class="table-card">
        <h3>Submit a New Request</h3>
        <form method="POST" action="{{ route($storeRoute) }}">
            @csrf
            @if($errors->any())
                <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.8rem;">
                    @foreach($errors->all() as $err)<div>{{ $err }}</div>@endforeach
                </div>
            @endif

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.35rem;text-transform:uppercase;letter-spacing:0.03em;">Action Type</label>
                <select name="action_type" style="width:100%;padding:0.6rem 0.875rem;border:1px solid var(--border-color);border-radius:0.375rem;font-size:0.875rem;background:var(--bg-tertiary);color:var(--text-primary);" required>
                    <option value="">— select type —</option>
                    @foreach($actionTypes as $key => $label)
                        <option value="{{ $key }}" {{ old('action_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.35rem;text-transform:uppercase;letter-spacing:0.03em;">Subject (one line)</label>
                <input type="text" name="subject" maxlength="200" style="width:100%;padding:0.6rem 0.875rem;border:1px solid var(--border-color);border-radius:0.375rem;font-size:0.875rem;background:var(--bg-tertiary);color:var(--text-primary);" value="{{ old('subject') }}" required placeholder="e.g. Delete schedule for Maria Santos">
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.8rem;font-weight:600;color:var(--text-secondary);margin-bottom:0.35rem;text-transform:uppercase;letter-spacing:0.03em;">Details</label>
                <textarea name="details" rows="5" maxlength="2000" style="width:100%;padding:0.6rem 0.875rem;border:1px solid var(--border-color);border-radius:0.375rem;font-size:0.875rem;background:var(--bg-tertiary);color:var(--text-primary);resize:vertical;" required placeholder="Explain what you intend to do and why you need approval...">{{ old('details') }}</textarea>
            </div>

            <button type="submit" style="padding:0.6rem 1.25rem;background:var(--green-primary);color:white;border:none;border-radius:0.375rem;font-size:0.875rem;font-weight:600;cursor:pointer;">
                Send Request
            </button>
        </form>
    </div>

    {{-- My Requests list --}}
    <div class="table-card" style="padding:0;overflow:hidden;">
        <div style="padding:1.5rem;border-bottom:1px solid var(--border-color);display:flex;justify-content:space-between;align-items:center;">
            <h3 style="margin:0;">My Requests</h3>
        </div>
        @if($myRequests->isEmpty())
            <div style="padding:3rem;text-align:center;color:var(--text-secondary);font-size:0.875rem;">
                You have not submitted any requests yet.
            </div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Principal Notes</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($myRequests as $req)
                    <tr>
                        <td style="white-space:nowrap;font-size:0.8rem;">{{ $req->actionLabel() }}</td>
                        <td style="max-width:160px;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $req->subject }}">
                            {{ $req->subject }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $req->status }}">{{ ucfirst($req->status) }}</span>
                        </td>
                        <td style="max-width:200px;font-size:0.8rem;color:var(--text-secondary);">
                            @if($req->reviewer_notes)
                                <span title="{{ $req->reviewer_notes }}">{{ Str::limit($req->reviewer_notes, 60) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-size:0.75rem;color:var(--text-secondary);white-space:nowrap;">
                            {{ $req->created_at->format('M j, Y') }}
                        </td>
                        <td>
                            @if($req->isPending())
                                <form method="POST" action="{{ route($cancelRoute, $req) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" style="padding:0.25rem 0.6rem;font-size:0.75rem;background:transparent;border:1px solid var(--border-color);border-radius:0.25rem;cursor:pointer;color:var(--text-secondary);" onclick="return confirm('Cancel this request?')">
                                        Cancel
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div style="padding:1rem 1.5rem;">
                {{ $myRequests->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
