@extends('layouts.teacher')

@section('title', 'Request Schedule Adjustments')

@section('content')
    @include('partials.teacher-request-adjustments', [
        'divisionLabel' => 'Junior High Division',
        'apiBase'       => '/api/teacher/adjustment-requests',
        'gradeLevels'   => ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
    ])
@endsection
