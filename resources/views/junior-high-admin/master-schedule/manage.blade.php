{{-- resources/views/junior-high-admin/master-schedule/manage.blade.php --}}
@extends('layouts.admin')

@section('title', 'Master Loading Schedule – ' . $teacher->first_name . ' ' . $teacher->last_name)

@section('content')
    @include('master-weekly-schedule._grid', [
        'saveRoute'  => 'admin.master-schedule.save',
        'clearRoute' => 'admin.master-schedule.clear',
        'backRoute'  => 'admin.faculty-loading',
    ])
@endsection
