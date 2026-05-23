{{-- Grade School: Faculty Workload History --}}
@extends('layouts.grade-school-teacher')

@section('title', 'Faculty Workload History')

@section('content')
<div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;">
    <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin:0 0 .3rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Grade School Division</p>
    <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">Workload History</h1>
    <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Your approved schedule assignments and load records</p>
</div>

@include('partials.teacher-workload-history-embed', [
    'historyApi' => '/api/grade-school-teacher/workload-history',
    'facultyLoadFallback' => '/api/grade-school-teacher/faculty-load',
])
@endsection
