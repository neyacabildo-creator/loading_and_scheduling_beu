{{-- resources/views/grade-school-admin/master-schedule/manage.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Master Loading Schedule – ' . $teacher->first_name . ' ' . $teacher->last_name)

@section('content')
    @include('master-weekly-schedule._grid', [
        'saveRoute'  => 'grade-school-admin.master-schedule.save',
        'clearRoute' => 'grade-school-admin.master-schedule.clear',
        'backRoute'  => 'grade-school-admin.faculty-loading',
    ])
@endsection
