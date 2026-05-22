@extends('layouts.grade-school-teacher')

@section('title', 'My Profile & Settings')

@section('content')
    @include('partials.teacher-profile-settings', [
        'divisionLabel' => 'Grade School Division',
        'photoRoute'    => route('grade-school-teacher.profile.photo'),
        'updateRoute'   => route('grade-school-teacher.profile.update'),
    ])
@endsection
