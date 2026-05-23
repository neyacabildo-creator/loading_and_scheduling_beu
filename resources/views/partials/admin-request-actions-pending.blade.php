@php
    $approveRoute = $approveRoute ?? '#';
    $rejectRoute = $rejectRoute ?? '#';
@endphp
<div class="str-actions-inline">
    <form method="POST" action="{{ $approveRoute }}" class="str-action-form">
        @csrf
        @method('PATCH')
        <button type="submit" class="str-btn-approve">Approve</button>
    </form>
    <form method="POST" action="{{ $rejectRoute }}" class="str-action-form" onsubmit="return confirm('Reject this request?');">
        @csrf
        @method('PATCH')
        <button type="submit" class="str-btn-reject">Reject</button>
    </form>
</div>
