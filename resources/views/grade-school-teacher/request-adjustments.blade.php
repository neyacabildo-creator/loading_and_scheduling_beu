@extends('layouts.grade-school-teacher')

@section('title', 'Request Schedule Adjustments')

@section('content')
    @include('partials.teacher-request-adjustments', [
        'divisionLabel' => 'Grade School Division',
        'apiBase'       => '/api/grade-school-teacher/adjustment-requests',
        'gradeLevels'   => ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'],
    ])
@endsection
