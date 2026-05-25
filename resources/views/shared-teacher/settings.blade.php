{{-- resources/views/shared-teacher/settings.blade.php --}}
@extends('layouts.shared-teacher')

@section('title', 'My Profile')

@section('content')
@if(session('success'))
    <div class="alert alert-success" style="background:rgba(45,122,80,.1);border:1px solid rgba(45,122,80,.3);color:#2d7a50;padding:.85rem 1rem;border-radius:.5rem;margin-bottom:1.25rem;">
        {{ session('success') }}
    </div>
@endif

@include('partials.teacher-profile-settings', [
    'divisionLabel' => 'Shared Teacher Portal',
    'photoRoute' => route('shared-teacher.profile.photo'),
    'updateRoute' => route('shared-teacher.profile.update'),
    'allowNameEdit' => true,
    'notificationsApi' => '/api/shared-teacher/notifications',
])
@endsection
