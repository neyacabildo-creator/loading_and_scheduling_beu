@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Faculty Load</h1>
    <form method="POST" action="{{ route('admin.faculty-loading.update', $facultyLoad->id) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="faculty_id">Faculty</label>
            <select name="faculty_id" id="faculty_id" class="form-control" required>
                @foreach($faculties as $faculty)
                    <option value="{{ $faculty->id }}" {{ $faculty->id == $facultyLoad->faculty_id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject" class="form-control" value="{{ $facultyLoad->subject }}" required>
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control" required>
                <option value="active" {{ $facultyLoad->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $facultyLoad->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Save Changes</button>
    </form>
</div>
@endsection