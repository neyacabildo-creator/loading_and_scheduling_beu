@extends('layouts.teacher')

@section('title', 'Faculty Teaching Load')

@section('content')
    <div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">My Teaching Load</h1>
            <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Summary of subjects and hours assigned to you this semester</p>
        </div>
        <button onclick="window.print()" style="display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.25rem;background:rgba(255,255,255,.18);color:white;border:1px solid rgba(255,255,255,.35);border-radius:.45rem;font-size:.875rem;font-weight:600;cursor:pointer;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
        </button>
    </div>

    @include('partials.teacher-workload-summary-embed', ['facultyLoadApi' => '/api/teacher/faculty-load'])
@endsection
