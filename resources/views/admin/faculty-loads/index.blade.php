{{-- resources/views/admin/faculty-loads/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Faculty Loads')

@section('content')
    <style>
        .loads-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .table-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .table-header { padding: 1.5rem; border-bottom: 1px solid #e8dcc8; display: flex; justify-content: space-between; align-items: center; }
        .table-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 1.5rem; background: #f5f3ed; text-align: left; font-weight: 600; color: #2d3436; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; }
        tr:hover { background: #fafaf8; }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-active { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .status-inactive { background: rgba(150,150,150,0.15); color: #6b6b6b; }
        .action-btn { padding: 0.5rem 0.75rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 500; transition: all 0.2s; }
        .btn-edit { background: rgba(100,100,100,0.15); color: #666; }
        .btn-edit:hover { background: #666; color: white; }
        .btn-delete { background: rgba(200,50,50,0.15); color: #c83232; }
        .btn-delete:hover { background: #c83232; color: white; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Faculty Loads Management</h1>
        </div>
        <div class="header-right">
            <a href="{{ route('admin.faculty-loads.create') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Add Faculty Load
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: rgba(45,122,80,0.15); border: 1px solid #2d7a50; color: #2d7a50; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-card">
        @if($facultyLoads->isEmpty())
            <div style="padding: 2rem; text-align: center; color: #7a7a6e;">
                <p>No faculty loads found. <a href="{{ route('admin.faculty-loads.create') }}" style="color: #2d7a50; font-weight: 600;">Add one now</a></p>
            </div>
        @else
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Faculty Name</th>
                            <th>Department</th>
                            <th>Classes</th>
                            <th>Load Hours</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facultyLoads as $load)
                            <tr>
                                <td>{{ $load->faculty->first_name }} {{ $load->faculty->last_name }}</td>
                                <td>{{ $load->department }}</td>
                                <td>{{ $load->classes_assigned }}</td>
                                <td>{{ $load->load_hours }} hrs</td>
                                <td><span class="status-badge status-{{ $load->status }}">{{ ucfirst($load->status) }}</span></td>
                                <td>{{ substr($load->notes ?? 'N/A', 0, 30) }}...</td>
                                <td style="display: flex; gap: 0.5rem;">
                                    <a href="{{ route('admin.faculty-loads.edit', $load->id) }}" class="action-btn btn-edit">Edit</a>
                                    <form method="POST" action="{{ route('admin.faculty-loads.destroy', $load->id) }}" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-btn btn-delete" onclick="return confirm('Delete this faculty load?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
