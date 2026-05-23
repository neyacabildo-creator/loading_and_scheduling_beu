{{-- resources/views/grade-school-teacher/faculty-loading.blade.php --}}
@extends('layouts.grade-school-teacher')

@section('title', 'Faculty Teaching Load')

@section('content')
    @include('partials.teacher-page-banner', [
        'eyebrow' => 'Grade School Division',
        'pageTitle' => 'My Teaching Load',
        'pageSubtitle' => 'Summary of subjects and hours assigned to you this semester',
        'showPrint' => true,
    ])

    @include('partials.teacher-workload-summary-embed', ['facultyLoadApi' => '/api/grade-school-teacher/faculty-load'])
@endsection
