<script src="{{ asset('js/spup-toast.js') }}" defer></script>
@if(session('success'))
    <span data-spup-flash data-spup-flash-type="success" hidden>{{ session('success') }}</span>
@endif
@if(session('schedule_conflicts'))
    @foreach((array) session('schedule_conflicts') as $scheduleConflict)
        <span data-spup-flash data-spup-flash-type="error" hidden>{{ $scheduleConflict }}</span>
    @endforeach
@elseif(session('error'))
    <span data-spup-flash data-spup-flash-type="error" hidden>{{ session('error') }}</span>
@endif
@if(session('status'))
    <span data-spup-flash data-spup-flash-type="info" hidden>{{ session('status') }}</span>
@endif
@if(isset($errors) && $errors->any())
    <span data-spup-flash data-spup-flash-type="error" hidden>{{ $errors->first() }}</span>
@endif
