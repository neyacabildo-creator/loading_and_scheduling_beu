@extends('layouts.grade-school-teacher')

@section('title', 'Print / Export Schedule')

@section('content')
    @include('partials.teacher-print-export', [
        'schedules'       => $schedules,
        'exportCsvUrl'    => $exportCsvUrl,
        'exportPrintUrl'  => $exportPrintUrl,
        'divisionLabel'   => $divisionLabel,
    ])
@endsection
