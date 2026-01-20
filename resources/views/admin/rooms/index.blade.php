{{-- resources/views/admin/rooms/index.blade.php --}}
@extends('layouts.admin')

@section('title', 'Rooms Management')

@section('content')
    <style>
        .rooms-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
        .room-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .room-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem; }
        .room-number { font-size: 1.25rem; font-weight: 600; color: #2d3436; }
        .room-status { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-available { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .status-unavailable { background: rgba(150,150,150,0.15); color: #6b6b6b; }
        .status-maintenance { background: rgba(200,150,50,0.15); color: #c89632; }
        .room-info { display: grid; gap: 0.75rem; margin-bottom: 1rem; }
        .info-row { display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem; }
        .info-label { color: #7a7a6e; font-weight: 500; }
        .info-value { color: #2d3436; font-weight: 500; }
        .facilities { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .facility-badge { display: inline-block; padding: 0.25rem 0.5rem; background: rgba(45,122,80,0.1); color: #2d7a50; border-radius: 0.25rem; font-size: 0.75rem; }
        .room-actions { display: flex; gap: 0.5rem; }
        .btn-edit, .btn-delete { flex: 1; padding: 0.5rem; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; transition: all 0.2s; }
        .btn-edit { background: rgba(100,100,100,0.15); color: #666; }
        .btn-edit:hover { background: #666; color: white; }
        .btn-delete { background: rgba(200,50,50,0.15); color: #c83232; }
        .btn-delete:hover { background: #c83232; color: white; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Rooms Management</h1>
        </div>
        <div class="header-right">
            <a href="{{ route('admin.rooms.create') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Add Room
            </a>
        </div>
    </div>

    @if(session('success'))
        <div style="background: rgba(45,122,80,0.15); border: 1px solid #2d7a50; color: #2d7a50; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
            {{ session('success') }}
        </div>
    @endif

    @if($rooms->isEmpty())
        <div style="background: white; border: 1px solid #e8dcc8; border-radius: 0.75rem; padding: 2rem; text-align: center; color: #7a7a6e;">
            <p>No rooms found. <a href="{{ route('admin.rooms.create') }}" style="color: #2d7a50; font-weight: 600;">Create one now</a></p>
        </div>
    @else
        <div class="rooms-grid">
            @foreach($rooms as $room)
                <div class="room-card">
                    <div class="room-header">
                        <div class="room-number">{{ $room->room_number }}</div>
                        <span class="room-status status-{{ $room->status }}">{{ ucfirst($room->status) }}</span>
                    </div>
                    <div class="room-info">
                        <div class="info-row">
                            <span class="info-label">Building:</span>
                            <span class="info-value">{{ $room->building }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Capacity:</span>
                            <span class="info-value">{{ $room->capacity }} students</span>
                        </div>
                    </div>
                    <div class="facilities">
                        @if($room->has_laboratory)
                            <span class="facility-badge">🧪 Laboratory</span>
                        @endif
                        @if($room->has_projector)
                            <span class="facility-badge">📽️ Projector</span>
                        @endif
                        @if($room->has_ac)
                            <span class="facility-badge">❄️ AC</span>
                        @endif
                    </div>
                    <div class="room-actions">
                        <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn-edit">Edit</a>
                        <form method="POST" action="{{ route('admin.rooms.destroy', $room->id) }}" style="flex: 1;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete" style="width: 100%;" onclick="return confirm('Delete this room?')">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
