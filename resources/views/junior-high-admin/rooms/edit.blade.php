{{-- resources/views/admin/rooms/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit Room')

@section('content')
    <style>
        .form-container { max-width: 600px; margin: 0 auto; }
        .form-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #2d3436; font-size: 0.875rem; }
        .form-label .required::after { content: ' *'; color: #ef4444; }
        .form-input, .form-select { width: 100%; padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem; font-size: 0.875rem; transition: all 0.2s; }
        .form-input:focus, .form-select:focus { outline: none; border-color: #2d7a50; box-shadow: 0 0 0 3px rgba(45,122,80,0.1); }
        .checkbox-group { display: flex; flex-direction: column; gap: 0.75rem; }
        .checkbox-item { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-item input { width: 18px; height: 18px; cursor: pointer; }
        .checkbox-item label { cursor: pointer; font-size: 0.875rem; color: #2d3436; margin: 0; }
        .form-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 2rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; text-decoration: none; display: inline-block; text-align: center; }
        .btn-primary { background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45,122,80,0.3); }
        .btn-cancel { background: #e8dcc8; color: #2d3436; }
        .btn-cancel:hover { background: #d4c9b8; }
        .error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
        .form-error { border-color: #ef4444 !important; box-shadow: 0 0 0 3px rgba(239,68,68,0.1) !important; }
    </style>

    <div class="form-container">
        <h1 class="page-title" style="margin-bottom: 2rem;">Edit Room {{ $room->room_number }}</h1>

        <div class="form-card">
            <form method="POST" action="{{ route('admin.rooms.update', $room->id) }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label class="form-label"><span class="required">Room Number</span></label>
                    <input type="text" name="room_number" class="form-input @error('room_number') form-error @enderror" value="{{ old('room_number', $room->room_number) }}" required>
                    @error('room_number')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label"><span class="required">Building</span></label>
                    <input type="text" name="building" class="form-input @error('building') form-error @enderror" value="{{ old('building', $room->building) }}" required>
                    @error('building')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label"><span class="required">Capacity (Students)</span></label>
                    <input type="number" name="capacity" class="form-input @error('capacity') form-error @enderror" value="{{ old('capacity', $room->capacity) }}" min="1" max="200" required>
                    @error('capacity')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label"><span class="required">Status</span></label>
                    <select name="status" class="form-select @error('status') form-error @enderror" required>
                        <option value="available" {{ old('status', $room->status) === 'available' ? 'selected' : '' }}>Available</option>
                        <option value="in-use" {{ old('status', $room->status) === 'in-use' ? 'selected' : '' }}>In Use</option>
                        <option value="maintenance" {{ old('status', $room->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    </select>
                    @error('status')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Facilities</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="has_laboratory" name="has_laboratory" value="1" {{ old('has_laboratory', $room->has_laboratory) ? 'checked' : '' }}>
                            <label for="has_laboratory">Has Laboratory </label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="has_projector" name="has_projector" value="1" {{ old('has_projector', $room->has_projector) ? 'checked' : '' }}>
                            <label for="has_projector">Has Projector </label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="has_ac" name="has_ac" value="1" {{ old('has_ac', $room->has_ac) ? 'checked' : '' }}>
                            <label for="has_ac">Has AC </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Room</button>
                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
