{{-- Grade School: Faculty Workload History --}}
@extends('layouts.grade-school-teacher')

@section('title', 'Faculty Workload History')

@section('content')
@include('partials.teacher-page-banner', [
    'eyebrow' => 'Grade School Division',
    'pageTitle' => 'Workload History',
    'pageSubtitle' => 'Your approved schedule assignments and load records',
])

@include('partials.teacher-workload-history-embed', [
    'historyApi' => '/api/grade-school-teacher/workload-history',
    'facultyLoadFallback' => '/api/grade-school-teacher/faculty-load',
])
@endsection
