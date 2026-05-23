@extends('layouts.teacher')

@section('title', 'Faculty Teaching Load')

@section('content')
    @include('partials.teacher-page-banner', [
        'pageTitle' => 'My Teaching Load',
        'pageSubtitle' => 'Summary of subjects and hours assigned to you this semester',
        'showPrint' => true,
    ])

    @include('partials.teacher-workload-summary-embed', ['facultyLoadApi' => '/api/teacher/faculty-load'])
@endsection
