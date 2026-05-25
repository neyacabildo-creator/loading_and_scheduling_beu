@extends('layouts.teacher')

@section('title', 'My Profile & Settings')

@section('content')
    @include('partials.teacher-profile-settings', [
        'divisionLabel' => 'Junior High Division',
        'photoRoute'    => route('teacher.profile.photo'),
        'updateRoute'   => route('teacher.profile.update'),
        'allowNameEdit' => true,
    ])
@endsection
