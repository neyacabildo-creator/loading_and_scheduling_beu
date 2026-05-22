{{-- resources/views/admin/users/create.blade.php --}}
@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Create User</h1>
        </div>
        <div class="header-right">
            <a href="{{ route('admin.users') }}" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: transparent; color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; text-decoration: none; font-weight: 600; font-size: 0.875rem;">
                ← Back to Users
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="table-card" style="max-width: 600px;">
        <div class="table-header">
            <div class="table-title">Add New User</div>
        </div>
        <form style="padding: 2rem;" action="{{ route('admin.users.store') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">First Name</label>
                <input type="text" name="first_name" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Last Name</label>
                <input type="text" name="last_name" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Email</label>
                <input type="email" name="email" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Role</label>
                <select name="role_id" id="jh_role_id" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required onchange="toggleSharedSubjectsJH(this.value)">
                    <option value="">Select Role</option>
                    <option value="1">Administrator</option>
                    <option value="2">Teacher</option>
                    <option value="7">Shared Teacher</option>
                </select>
            </div>

            {{-- Subject fields shown only for Shared Teacher --}}
            <div id="jh_shared_subjects_wrap" style="display:none; margin-bottom: 1.5rem; background: rgba(59,130,246,.06); border: 1px solid rgba(59,130,246,.2); border-radius: 0.5rem; padding: 1rem;">
                <p style="margin:0 0 0.75rem; font-size:0.8rem; color:#2563eb; font-weight:600;">Shared Teacher — Subjects Handled (select 1 or 2 subjects)</p>
                <div style="margin-bottom:0.75rem;">
                    <label style="display:block; margin-bottom:0.4rem; font-size:0.875rem; font-weight:500; color:var(--text-primary);">Subject 1</label>
                    <select name="subject1" id="jh_subject1" style="width:100%; padding:0.65rem; border:1px solid var(--border-color); border-radius:0.5rem; background:var(--bg-secondary); color:var(--text-primary);">
                        <option value="">— Select Subject —</option>
                        <option value="MATHEMATICS">MATHEMATICS</option>
                        <option value="ADV MATH">ADV MATH</option>
                        <option value="SCIENCE">SCIENCE</option>
                        <option value="ADV SCI">ADV SCI</option>
                        <option value="ENGLISH">ENGLISH</option>
                        <option value="FILIPINO">FILIPINO</option>
                        <option value="AP">AP</option>
                        <option value="MAPEH">MAPEH</option>
                        <option value="TLE">TLE</option>
                        <option value="COMP">COMP</option>
                        <option value="CLVE">CLVE</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; margin-bottom:0.4rem; font-size:0.875rem; font-weight:500; color:var(--text-primary);">Subject 2 <span style="color:var(--text-secondary);font-weight:400;">(optional)</span></label>
                    <select name="subject2" id="jh_subject2" style="width:100%; padding:0.65rem; border:1px solid var(--border-color); border-radius:0.5rem; background:var(--bg-secondary); color:var(--text-primary);">
                        <option value="">— None —</option>
                        <option value="MATHEMATICS">MATHEMATICS</option>
                        <option value="ADV MATH">ADV MATH</option>
                        <option value="SCIENCE">SCIENCE</option>
                        <option value="ADV SCI">ADV SCI</option>
                        <option value="ENGLISH">ENGLISH</option>
                        <option value="FILIPINO">FILIPINO</option>
                        <option value="AP">AP</option>
                        <option value="MAPEH">MAPEH</option>
                        <option value="TLE">TLE</option>
                        <option value="COMP">COMP</option>
                        <option value="CLVE">CLVE</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Password</label>
                <input type="password" name="password" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Confirm Password</label>
                <input type="password" name="password_confirmation" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" style="flex: 1; padding: 0.75rem; background: var(--green-primary); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Create User</button>
                <a href="{{ route('admin.users') }}" style="flex: 1; padding: 0.75rem; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; cursor: pointer; font-weight: 600; text-align: center; text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
function toggleSharedSubjectsJH(roleId) {
    // Role ID 7 = Shared Teacher
    var wrap = document.getElementById('jh_shared_subjects_wrap');
    if (wrap) wrap.style.display = (String(roleId) === '7') ? 'block' : 'none';
    // Make subject1 required only for shared teacher
    var s1 = document.getElementById('jh_subject1');
    if (s1) s1.required = (String(roleId) === '7');
}
// Restore state on validation failure
(function() {
    var roleId = document.getElementById('jh_role_id').value;
    if (roleId) toggleSharedSubjectsJH(roleId);
})();
</script>
@endpush
