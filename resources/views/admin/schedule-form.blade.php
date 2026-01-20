{{-- resources/views/admin/schedule-form.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create Schedule')

@section('content')
    <style>
        .form-container { max-width: 900px; margin: 0 auto; }
        .form-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 2rem; }
        .form-header { margin-bottom: 2rem; }
        .form-title { font-size: 1.875rem; font-weight: bold; color: #2d3436; margin-bottom: 0.5rem; }
        .form-subtitle { color: #7a7a6e; font-size: 0.875rem; }
        
        .form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #e8dcc8; }
        .form-section:last-child { border-bottom: none; }
        .section-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
        
        .form-group { display: flex; flex-direction: column; }
        .form-group.full { grid-column: 1 / -1; }
        
        .form-label { font-weight: 600; color: #2d3436; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .required::after { content: ' *'; color: #ef4444; }
        
        .form-input, .form-select, .form-textarea { 
            padding: 0.75rem;
            border: 1px solid #e8dcc8;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #2d7a50;
            box-shadow: 0 0 0 3px rgba(45, 122, 80, 0.1);
        }
        
        .form-input::placeholder { color: #b8b8b0; }
        
        .input-help { font-size: 0.75rem; color: #7a7a6e; margin-top: 0.25rem; }
        
        .form-error { 
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
        }
        
        .error-message { 
            color: #ef4444; 
            font-size: 0.75rem; 
            margin-top: 0.25rem;
        }
        
        .success-message { 
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #15803d;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .form-actions { 
            display: flex; 
            gap: 1rem; 
            margin-top: 2rem; 
            padding-top: 2rem;
            border-top: 1px solid #e8dcc8;
        }
        
        .btn { 
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%);
            color: white;
        }
        
        .btn-primary:hover { 
            background: linear-gradient(135deg, #1a5336 0%, #0f3d26 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(45, 122, 80, 0.3);
        }
        
        .btn-secondary { 
            background: white;
            color: #2d3436;
            border: 1px solid #e8dcc8;
        }
        
        .btn-secondary:hover { 
            background: #f5f3ed;
            border-color: #2d7a50;
        }
        
        .time-group { display: grid; grid-template-columns: 1fr auto 1fr; gap: 0.75rem; align-items: flex-end; }
        .time-separator { color: #7a7a6e; font-weight: 600; }
        
        .form-info { 
            background: rgba(100, 150, 200, 0.08);
            border-left: 4px solid rgba(45, 122, 80, 0.3);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            color: #2d3436;
        }
        
        .form-info strong { color: #2d7a50; }
    </style>

    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <h1 class="form-title">Create Class Schedule</h1>
            <p class="form-subtitle">Add a new class schedule for a teacher. Created schedules will be marked as approved by default.</p>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div class="success-message">
                ✓ {{ session('success') }}
            </div>
        @endif

        <!-- Form -->
        <div class="form-card">
            <form id="scheduleForm" method="POST" action="{{ route('admin.schedule.store') }}" class="space-y-6">
                @csrf

                <!-- Teacher & Subject Information -->
                <div class="form-section">
                    <h2 class="section-title">Teacher & Subject Information</h2>
                    
                    <div class="form-grid">
                        <!-- Teacher -->
                        <div class="form-group">
                            <label for="faculty_id" class="form-label required">Teacher</label>
                            <select 
                                id="faculty_id" 
                                name="faculty_id" 
                                class="form-select @error('faculty_id') form-error @enderror"
                                required
                            >
                                <option value="">Select a teacher</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('faculty_id') == $teacher->id ? 'selected' : '' }}>
                                        {{ $teacher->first_name }} {{ $teacher->last_name }} 
                                        @if($teacher->position) - {{ $teacher->position }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('faculty_id')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Subject -->
                        <div class="form-group">
                            <label for="subject" class="form-label required">Subject</label>
                            <input 
                                type="text" 
                                id="subject" 
                                name="subject" 
                                class="form-input @error('subject') form-error @enderror"
                                value="{{ old('subject') }}"
                                placeholder="e.g., Mathematics, Science, English"
                                required
                            >
                            @error('subject')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Grade Level -->
                        <div class="form-group">
                            <label for="grade_section" class="form-label required">Grade & Section</label>
                            <input 
                                type="text" 
                                id="grade_section" 
                                name="grade_section" 
                                class="form-input @error('grade_section') form-error @enderror"
                                value="{{ old('grade_section') }}"
                                placeholder="e.g., Grade 7 - Section A"
                                required
                            >
                            @error('grade_section')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                            <span class="input-help">Include grade level and section letter</span>
                        </div>

                        <!-- Student Count -->
                        <div class="form-group">
                            <label for="student_count" class="form-label required">Number of Students</label>
                            <input 
                                type="number" 
                                id="student_count" 
                                name="student_count" 
                                class="form-input @error('student_count') form-error @enderror"
                                value="{{ old('student_count', '') }}"
                                min="1"
                                max="100"
                                placeholder="e.g., 35"
                                required
                            >
                            @error('student_count')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Room -->
                        <div class="form-group full">
                            <label for="room_id" class="form-label">Room Assignment</label>
                            <select id="room_id" name="room_id" class="form-select @error('room_id') form-error @enderror">
                                <option value="">Select a room (optional)</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->room_number }} 
                                        @if($room->building) - {{ $room->building }} @endif
                                        (Capacity: {{ $room->capacity }})
                                    </option>
                                @endforeach
                            </select>
                            @error('room_id')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                            <span class="input-help">Leave empty if room is not yet assigned</span>
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="form-section">
                    <h2 class="section-title">Schedule Details</h2>
                    
                    <div class="form-grid">
                        <!-- Day of Week -->
                        <div class="form-group">
                            <label for="day_of_week" class="form-label required">Day of Week</label>
                            <select id="day_of_week" name="day_of_week" class="form-select @error('day_of_week') form-error @enderror" required>
                                <option value="">Select a day</option>
                                <option value="Monday" {{ old('day_of_week') == 'Monday' ? 'selected' : '' }}>Monday</option>
                                <option value="Tuesday" {{ old('day_of_week') == 'Tuesday' ? 'selected' : '' }}>Tuesday</option>
                                <option value="Wednesday" {{ old('day_of_week') == 'Wednesday' ? 'selected' : '' }}>Wednesday</option>
                                <option value="Thursday" {{ old('day_of_week') == 'Thursday' ? 'selected' : '' }}>Thursday</option>
                                <option value="Friday" {{ old('day_of_week') == 'Friday' ? 'selected' : '' }}>Friday</option>
                                <option value="Saturday" {{ old('day_of_week') == 'Saturday' ? 'selected' : '' }}>Saturday</option>
                                <option value="Sunday" {{ old('day_of_week') == 'Sunday' ? 'selected' : '' }}>Sunday</option>
                            </select>
                            @error('day_of_week')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Time Period -->
                        <div class="form-group full">
                            <label class="form-label required">Time Period</label>
                            <div class="time-group">
                                <div>
                                    <label for="start_time" class="form-label" style="margin-bottom: 0.25rem; font-size: 0.75rem;">Start Time</label>
                                    <input 
                                        type="time" 
                                        id="start_time" 
                                        name="start_time" 
                                        class="form-input @error('start_time') form-error @enderror"
                                        value="{{ old('start_time') }}"
                                        required
                                    >
                                    @error('start_time')
                                        <span class="error-message">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="time-separator">to</div>
                                <div>
                                    <label for="end_time" class="form-label" style="margin-bottom: 0.25rem; font-size: 0.75rem;">End Time</label>
                                    <input 
                                        type="time" 
                                        id="end_time" 
                                        name="end_time" 
                                        class="form-input @error('end_time') form-error @enderror"
                                        value="{{ old('end_time') }}"
                                        required
                                    >
                                    @error('end_time')
                                        <span class="error-message">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information -->
                <div class="form-info">
                    <strong>Note:</strong> Schedules will be created in pending status. You must review and approve them in the Pending Schedules section before they appear in the teacher's dashboard.
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>Create & Approve Schedule</span>
                    </button>
                    <a href="{{ route('admin.class-schedule') }}" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime && startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time');
                return false;
            }
        });

        // Auto-calculate student count limits based on room capacity
        document.getElementById('room_id').addEventListener('change', function() {
            const roomText = this.options[this.selectedIndex].text;
            const capacityMatch = roomText.match(/Capacity:\s*(\d+)/);
            if (capacityMatch) {
                const capacity = parseInt(capacityMatch[1]);
                const studentInput = document.getElementById('student_count');
                if (studentInput.value && parseInt(studentInput.value) > capacity) {
                    console.warn(`Selected room capacity is ${capacity}, but ${studentInput.value} students assigned`);
                }
            }
        });
    </script>
@endsection
