{{-- resources/views/principal/users.blade.php --}}
@extends('layouts.principal')

@section('title', 'All Users')

@section('content')
<style>
.principal-user-actions {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: flex-start;
    gap: 0.4rem;
}
.principal-user-actions form { margin: 0; display: inline-flex; }
.principal-user-actions .btn { margin: 0; white-space: nowrap; flex-shrink: 0; }
</style>
<div class="header">
    <div class="header-left">
        <div>
            <h1 class="page-title">All Users</h1>
            <p class="page-subtitle">Manage accounts across all roles and school levels</p>
        </div>
    </div>
    <div class="header-right">
        <button onclick="document.getElementById('create-user-form').style.display = document.getElementById('create-user-form').style.display === 'none' ? 'block' : 'none'" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Add User
        </button>
    </div>
</div>

{{-- Create User Form (collapsible) --}}
<div id="create-user-form" style="display:none;">
    <div class="card" style="margin-bottom:1.5rem;border-left:4px solid #1a3a5c;">
        <div class="card-header">
            <span class="card-title">Create New User</span>
            <button onclick="document.getElementById('create-user-form').style.display='none'" class="btn btn-outline btn-sm">Close</button>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('principal.users.store') }}">
                @csrf
                @if($errors->any())
                    <div class="alert alert-error">
                        <ul style="margin:0;padding-left:1.2rem;">
                            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div style="position:relative;">
                            <input type="password" name="password" id="create_password" class="form-control" required minlength="8" style="padding-right:2.5rem;">
                            <button type="button" onclick="togglePwd('create_password', this)" tabindex="-1"
                                style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:0;line-height:1;">
                                <svg id="create_password_icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div style="position:relative;">
                            <input type="password" name="password_confirmation" id="create_password_confirmation" class="form-control" required minlength="8" style="padding-right:2.5rem;">
                            <button type="button" onclick="togglePwd('create_password_confirmation', this)" tabindex="-1"
                                style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:0;line-height:1;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role_id" class="form-control" required>
                            <option value="">— select role —</option>
                            @foreach($roles->where('name', '!=', 'principal') as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}
                                </option>
                            @endforeach
                        </select>
                        <small style="color:var(--text-secondary);font-size:0.75rem;">Principal accounts are provisioned via the system seeder only.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">School Level</label>
                        <select name="school_level" class="form-control">
                            <option value="">— auto-detect from role —</option>
                            <option value="grade_school" {{ old('school_level') === 'grade_school' ? 'selected' : '' }}>Grade School</option>
                            <option value="junior_high"  {{ old('school_level') === 'junior_high'  ? 'selected' : '' }}>Junior High</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create User</button>
            </form>
        </div>
    </div>
</div>

{{-- Users Table --}}
<div class="card">
    <div class="card-body" style="padding:0;">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Role</th>
                    <th>School Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div style="font-weight:500;">{{ $user->first_name }} {{ $user->last_name }}</div>
                    </td>
                    <td style="font-size:0.8rem;color:var(--text-secondary);">{{ $user->email }}</td>
                    <td style="font-size:0.8rem;">
                        @if(($user->role?->name ?? '') === 'principal' && !empty($user->display_password))
                            <code style="background:var(--bg-tertiary,#f3f4f6);padding:.15rem .4rem;border-radius:.25rem;">{{ $user->display_password }}</code>
                        @else
                            <span style="color:var(--text-secondary);">—</span>
                        @endif
                    </td>
                    <td>
                        @php $roleName = $user->role?->name ?? ''; @endphp
                        @if($roleName === 'principal')
                            <span class="badge badge-principal">Principal</span>
                        @elseif(str_contains($roleName, 'admin'))
                            <span class="badge badge-admin">Admin</span>
                        @else
                            <span class="badge" style="background:rgba(107,114,128,0.12);color:#374151;">
                                {{ $user->role?->display_name ?? ucfirst(str_replace('_',' ', $roleName)) }}
                            </span>
                        @endif
                    </td>
                    <td style="font-size:0.8rem;">
                        {{ $user->school_level ? ucfirst(str_replace('_',' ', $user->school_level)) : '—' }}
                    </td>
                    <td>
                        <span class="badge {{ $user->is_active ? 'badge-active' : 'badge-inactive' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="principal-user-actions">
                        <button type="button" class="btn btn-outline btn-sm"
                            onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->first_name) }}', '{{ addslashes($user->last_name) }}', '{{ addslashes($user->email) }}', {{ $user->role_id ?? 'null' }}, '{{ $user->school_level ?? '' }}', '{{ addslashes($user->role?->name ?? '') }}')">
                            Edit
                        </button>

                        @if($user->id !== Auth::id())
                            {{-- Activate / Deactivate --}}
                            <form method="POST" action="{{ route('principal.users.toggle', $user) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-outline btn-sm"
                                    onclick="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} this user?')">
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>

                            {{-- Permanent delete (only shown if account is not principal) --}}
                            @if(($user->role?->name ?? '') !== 'principal')
                            <form method="POST" action="{{ route('principal.users.destroy', $user) }}" style="display:inline;"
                                  onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm"
                                    style="background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;">
                                    Delete
                                </button>
                            </form>
                            @endif
                        @else
                            <span style="font-size:0.75rem;color:var(--text-secondary);">You</span>
                        @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div style="padding:1rem 1.5rem;">
            {{ $users->links() }}
        </div>
    </div>
</div>

{{-- Edit User Modal --}}
<div id="edit-user-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:var(--bg-secondary,#fff);border-radius:.75rem;width:100%;max-width:480px;margin:1rem;padding:2rem;box-shadow:0 20px 60px rgba(0,0,0,.25);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h2 style="font-size:1.1rem;font-weight:700;color:var(--text-primary);">Edit Account</h2>
            <button onclick="closeEditModal()" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--text-secondary);">&times;</button>
        </div>

        <form id="edit-user-form" method="POST">
            @csrf
            @method('PATCH')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">Role</label>
                <input type="text" id="edit_role_readonly" class="form-control" readonly
                    style="display:none;background:var(--bg-tertiary,#f3f4f6);color:var(--text-secondary);">
                <select name="role_id" id="edit_role_id" class="form-control" required>
                    <option value="">— select role —</option>
                    @foreach($roles->where('name', '!=', 'principal') as $role)
                        <option value="{{ $role->id }}">{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</option>
                    @endforeach
                </select>
                <small id="edit_role_hint" style="color:var(--text-secondary);font-size:0.75rem;">Principal role cannot be assigned through this form.</small>
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">School Level</label>
                <select name="school_level" id="edit_school_level" class="form-control">
                    <option value="">— not applicable / institution-wide —</option>
                    <option value="grade_school">Grade School</option>
                    <option value="junior_high">Junior High</option>
                </select>
                <small style="color:var(--text-secondary);font-size:0.75rem;">Required for teachers and admins; optional for principal accounts.</small>
            </div>
            <div style="background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.25);border-radius:.5rem;padding:.85rem 1rem;margin-bottom:1rem;font-size:.82rem;color:#92400e;">
                ⚠ Leave password fields blank to keep the current password.
            </div>
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label">New Password <span style="font-weight:400;color:var(--text-secondary);">(optional)</span></label>
                <div style="position:relative;">
                    <input type="password" name="password" id="edit_password" class="form-control" minlength="8" placeholder="Minimum 8 characters" style="padding-right:2.5rem;">
                    <button type="button" onclick="togglePwd('edit_password', this)" tabindex="-1"
                        style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:0;line-height:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label">Confirm New Password</label>
                <div style="position:relative;">
                    <input type="password" name="password_confirmation" id="edit_password_confirmation" class="form-control" placeholder="Repeat new password" style="padding-right:2.5rem;">
                    <button type="button" onclick="togglePwd('edit_password_confirmation', this)" tabindex="-1"
                        style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-secondary);padding:0;line-height:1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div style="display:flex;gap:.75rem;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, firstName, lastName, email, roleId, schoolLevel, roleName) {
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value  = lastName;
    document.getElementById('edit_email').value      = email;
    document.getElementById('edit_school_level').value = schoolLevel || '';

    const roleSel = document.getElementById('edit_role_id');
    const roleReadonly = document.getElementById('edit_role_readonly');
    const roleHint = document.getElementById('edit_role_hint');
    const isPrincipal = roleName === 'principal';

    if (isPrincipal) {
        roleSel.style.display = 'none';
        roleSel.removeAttribute('required');
        roleSel.removeAttribute('name');
        roleReadonly.style.display = 'block';
        roleReadonly.value = 'Principal';
        if (roleHint) roleHint.textContent = 'Principal role cannot be changed here.';
    } else {
        roleSel.style.display = '';
        roleSel.setAttribute('required', 'required');
        roleSel.setAttribute('name', 'role_id');
        roleReadonly.style.display = 'none';
        roleSel.value = roleId && roleId !== 'null' ? String(roleId) : '';
        if (roleHint) roleHint.textContent = 'Principal role cannot be assigned through this form.';
    }

    document.getElementById('edit-user-form').action = '/principal/users/' + id;
    // Clear password fields
    // Clear password fields and reset eye icons
    ['edit_password', 'edit_password_confirmation'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.value = ''; el.type = 'password'; }
    });
    const modal = document.getElementById('edit-user-modal');
    modal.style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('edit-user-modal').style.display = 'none';
}
function togglePwd(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    // Swap icon: eye / eye-off
    btn.innerHTML = showing
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
}
// Close on backdrop click
document.getElementById('edit-user-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
@endsection
