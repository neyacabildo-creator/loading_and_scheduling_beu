@php
    $approveRoute = $approveRoute ?? '#';
    $rejectRoute = $rejectRoute ?? '#';
@endphp
<div class="str-actions-grid">
    <div class="str-action-panel str-action-panel--approve">
        <div class="str-action-panel-title">Approve</div>
        <form method="POST" action="{{ $approveRoute }}">
            @csrf
            @method('PATCH')
            <input type="text" name="admin_notes" class="str-action-input" placeholder="Optional note…" autocomplete="off">
            <button type="submit" class="str-btn-approve">Approve</button>
        </form>
    </div>
    <div class="str-action-panel str-action-panel--reject">
        <div class="str-action-panel-title">Reject</div>
        <form method="POST" action="{{ $rejectRoute }}">
            @csrf
            @method('PATCH')
            <input type="text" name="admin_notes" class="str-action-input" placeholder="Reason…" autocomplete="off">
            <button type="submit" class="str-btn-reject">Reject</button>
        </form>
    </div>
</div>
