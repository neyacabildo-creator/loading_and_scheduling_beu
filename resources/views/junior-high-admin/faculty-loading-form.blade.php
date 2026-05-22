{{-- resources/views/admin/faculty-loading-form.blade.php --}}
@extends('layouts.admin')

@section('title', $facultyLoad ? 'Edit Faculty Load' : 'Create Faculty Load')

@section('content')
    <style>
        .form-wrapper { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem 1rem; }
        .form-card { background: var(--bg-secondary); border-radius: 0.75rem; border: 1px solid var(--border-color); padding: 2rem; max-width: 600px; width: 100%; box-shadow: var(--shadow-md); }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid var(--border-color); border-radius: 0.375rem; font-size: 0.875rem; font-family: inherit; box-sizing: border-box; background: var(--bg-secondary); color: var(--text-primary); }
        .form-input:focus, .form-select:focus, .form-textarea:focus { outline: none; border-color: var(--green-primary); box-shadow: 0 0 0 3px rgba(45,122,80,0.15); }
        .form-textarea { resize: vertical; min-height: 100px; }
        .button-group { display: flex; gap: 1rem; margin-top: 2rem; flex-wrap: wrap; }
        .btn-submit { padding: 0.75rem 1.5rem; background: linear-gradient(135deg, var(--green-primary) 0%, #0d3d20 100%); color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; flex: 1; min-width: 120px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45,122,80,0.3); }
        .btn-cancel { padding: 0.75rem 1.5rem; background: var(--border-color); color: var(--text-primary); border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600; font-size: 0.875rem; text-decoration: none; display: inline-block; transition: all 0.2s; flex: 1; min-width: 120px; text-align: center; }
        .btn-cancel:hover { background: var(--bg-tertiary); }
        .error-message { color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem; }
        .error-input { border-color: #ef4444 !important; }
        .back-link { color: var(--green-primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 1.5rem; transition: all 0.2s; }
        .back-link:hover { gap: 0.75rem; }

        @media (max-width: 768px) {
            .form-wrapper { padding: 1rem; }
            .form-card { padding: 1.5rem; }
            .button-group { flex-direction: column; }
            .btn-submit, .btn-cancel { width: 100%; }
        }

        @media (max-width: 640px) {
            .form-wrapper { min-height: auto; padding: 0.5rem; }
            .form-card { padding: 1rem; }
            .form-label { font-size: 0.8rem; }
            .form-input, .form-select, .form-textarea { font-size: 0.8rem; padding: 0.5rem 0.75rem; }
            .btn-submit, .btn-cancel { padding: 0.5rem 1rem; font-size: 0.75rem; }
        }
    </style>

    <div class="form-wrapper">
        <div>
            <a href="{{ route('admin.faculty-loading') }}" class="back-link">← Back to Faculty Loading</a>

            <div class="form-card">
                <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text-primary); margin-top: 0;">
                    {{ $facultyLoad ? 'Edit Faculty Load' : 'Create New Faculty Load' }}
                </h2>

                @if($errors->any())
                    <div style="background: rgba(239,68,68,0.15); border: 1px solid #ef4444; color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin: 0.5rem 0 0 1.5rem;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $facultyLoad ? route('admin.faculty-loading.update', $facultyLoad) : route('admin.faculty-loading.store') }}">
                    @csrf
                    @if($facultyLoad)
                        @method('PUT')
                    @endif

                    <div class="form-group">
                        <label class="form-label">Faculty Member <span style="color: #ef4444;">*</span></label>
                        <input type="text" id="facultySearch" class="form-input @error('faculty_id') error-input @enderror" 
                               placeholder="Search faculty by name..." autocomplete="off" required
                               style="position: relative; z-index: 10;">
                        <input type="hidden" name="faculty_id" id="faculty_id_hidden" value="{{ old('faculty_id', $facultyLoad?->faculty_id) }}" required>
                        <div id="facultyDropdown" style="position: absolute; top: 100%; left: 0; right: 0; max-height: 200px; overflow-y: auto; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.375rem; display: none; z-index: 1000; margin-top: 0.25rem;"></div>
                        <div id="sharedTeacherNotice" style="background:rgba(245,158,11,.12);border:1px solid #f59e0b;color:#b45309;padding:.5rem .75rem;border-radius:.375rem;font-size:.8rem;margin-top:.4rem;display:none;">⚡ This teacher is a Shared Teacher (teaches in both JH and GS)</div>
                        @error('faculty_id')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <script>
                        const teachers = {!! json_encode($teachers->map(fn($t) => ['id' => $t->id, 'name' => ($t->first_name ?? '') . ' ' . ($t->last_name ?? ''), 'email' => $t->email, 'is_shared' => in_array((string)$t->id, $sharedTeacherUserIds ?? [])])) !!};
                        const searchInput = document.getElementById('facultySearch');
                        const hiddenInput = document.getElementById('faculty_id_hidden');
                        const dropdown = document.getElementById('facultyDropdown');
                        
                        // Set initial value if editing
                        const currentFacultyId = "{{ old('faculty_id', $facultyLoad?->faculty_id) }}";
                        if (currentFacultyId) {
                            const current = teachers.find(t => t.id == currentFacultyId);
                            if (current) {
                                searchInput.value = current.name + ' (' + current.email + ')';
                                showSharedNotice(current.is_shared);
                            }
                        }

                        searchInput.addEventListener('input', function() {
                            const value = this.value.toLowerCase();
                            if (value.length === 0) {
                                dropdown.style.display = 'none';
                                return;
                            }

                            const filtered = teachers.filter(t => 
                                t.name.toLowerCase().includes(value) || 
                                t.email.toLowerCase().includes(value)
                            );

                            if (filtered.length === 0) {
                                dropdown.innerHTML = '<div style="padding: 0.75rem; color: var(--text-secondary);">No faculty found</div>';
                                dropdown.style.display = 'block';
                                return;
                            }

                            dropdown.innerHTML = filtered.map(teacher => `
                                <div style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: all 0.2s; background: var(--bg-secondary);" 
                                     onmouseover="this.style.background='var(--bg-tertiary)'" 
                                     onmouseout="this.style.background='var(--bg-secondary)'"
                                     onclick="selectFaculty(${teacher.id}, '${teacher.name} (${teacher.email})', ${teacher.is_shared})">
                                    <strong>${teacher.name}</strong>${teacher.is_shared ? ' <span style=\'background:#2563eb;color:white;border-radius:9999px;font-size:0.65rem;padding:1px 6px;font-weight:700;\'>SHARED</span>' : ''}<br>
                                    <small style="color: var(--text-secondary);">${teacher.email}</small>
                                </div>
                            `).join('');
                            dropdown.style.display = 'block';
                        });

                        function showSharedNotice(isShared) {
                            var notice = document.getElementById('sharedTeacherNotice');
                            if (notice) notice.style.display = isShared ? 'block' : 'none';
                        }

                        function selectFaculty(id, name, isShared) {
                            searchInput.value = name;
                            hiddenInput.value = id;
                            dropdown.style.display = 'none';
                            showSharedNotice(isShared);
                        }

                        document.addEventListener('click', function(e) {
                            if (e.target !== searchInput && !dropdown.contains(e.target)) {
                                dropdown.style.display = 'none';
                            }
                        });
                    </script>

                    <div class="form-group">
                        <label class="form-label">Grade Level</label>
                        <select id="jhGradeLevelSelect" name="grade_level" class="form-select @error('grade_level') error-input @enderror">
                            <option value="">-- Select Grade Level --</option>
                            @foreach(['Grade 7','Grade 8','Grade 9','Grade 10'] as $grade)
                                <option value="{{ $grade }}" {{ old('grade_level', $facultyLoad?->grade_level) == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                            @endforeach
                        </select>
                        @error('grade_level')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Subject</label>
                        <select id="jhSubjectSelect" name="subject" class="form-select @error('subject') error-input @enderror">
                            <option value="">-- Select Subject --</option>
                        </select>
                        @error('subject')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Classes Assigned <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="classes_assigned" class="form-input @error('classes_assigned') error-input @enderror"
                               value="{{ old('classes_assigned', $facultyLoad?->classes_assigned) }}" placeholder="Number of classes" min="1" required>
                        @error('classes_assigned')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Load Hours <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="load_hours" class="form-input @error('load_hours') error-input @enderror" 
                               value="{{ old('load_hours', $facultyLoad?->load_hours) }}" placeholder="e.g., 6.5" step="0.25" min="0.5" max="999.99" required>
                        @error('load_hours')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status <span style="color: #ef4444;">*</span></label>
                        <select name="status" class="form-select @error('status') error-input @enderror" required>
                            <option value="">-- Select Status --</option>
                            <option value="available" {{ in_array(old('status', $facultyLoad?->status), ['available']) ? 'selected' : '' }}>Available</option>
                            <option value="unavailable" {{ in_array(old('status', $facultyLoad?->status), ['not_available', 'unavailable']) ? 'selected' : '' }}>Unavailable</option>
                        </select>
                        @error('status')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn-submit">
                            {{ $facultyLoad ? 'Update Faculty Load' : 'Create Faculty Load' }}
                        </button>
                        <a href="{{ route('admin.faculty-loading') }}" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Grade level → Subject mapping for JH
    const JH_GRADE_SUBJECTS = {
        'Grade 7': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Edukasyon sa Pagpapakatao','Christian Living Education','MAPEH','Technology and Livelihood Education'],
        'Grade 8': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Edukasyon sa Pagpapakatao','Christian Living Education','MAPEH','Technology and Livelihood Education'],
        'Grade 9': ['Mathematics','Science','English','Filipino','Araling Panlipunan','Edukasyon sa Pagpapakatao','Christian Living Education','MAPEH','Technology and Livelihood Education','Computer Education'],
        'Grade 10':['Mathematics','Science','English','Filipino','Araling Panlipunan','Edukasyon sa Pagpapakatao','Christian Living Education','MAPEH','Technology and Livelihood Education','Computer Education'],
    };
    const jhGradeEl   = document.getElementById('jhGradeLevelSelect');
    const jhSubjectEl = document.getElementById('jhSubjectSelect');
    const jhCurrentSubject = @json(old('subject', $facultyLoad?->subject ?? ''));

    function jhPopulateSubjects(grade) {
        const subjects = JH_GRADE_SUBJECTS[grade] || Object.values(JH_GRADE_SUBJECTS).flat().filter((v,i,a)=>a.indexOf(v)===i).sort();
        jhSubjectEl.innerHTML = '<option value="">-- Select Subject --</option>'
            + subjects.map(s => `<option value="${s}"${s === jhCurrentSubject ? ' selected' : ''}>${s}</option>`).join('');
    }

    jhGradeEl.addEventListener('change', () => jhPopulateSubjects(jhGradeEl.value));
    // Init on page load
    jhPopulateSubjects(jhGradeEl.value);
    </script>
@endsection
