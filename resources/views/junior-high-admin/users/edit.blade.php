{{-- resources/views/admin/users/edit.blade.php --}}
@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Edit User</h1>
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
            <div class="table-title">User Information</div>
        </div>
        <form style="padding: 2rem;" action="#" method="POST">
            @csrf
            @method('PUT')
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">First Name</label>
                <input type="text" name="first_name" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" placeholder="First Name" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Last Name</label>
                <input type="text" name="last_name" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" placeholder="Last Name" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Email</label>
                <input type="email" name="email" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" placeholder="Email" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Role</label>
                <select name="role_id" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                    <option value="">Select Role</option>
                    <option value="1">Administrator</option>
                    <option value="2">Teacher</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Status</label>
                <select name="status" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div style="margin-bottom: 2rem; padding: 1rem; background: var(--bg-tertiary); border-radius: 0.5rem; border-left: 3px solid var(--green-primary);">
                <p style="color: var(--text-secondary); font-size: 0.875rem; margin: 0;">Leave password fields empty to keep the current password</p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">New Password (Optional)</label>
                <input type="password" name="password" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" placeholder="Leave blank to keep current">
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-primary);">Confirm Password (Optional)</label>
                <input type="password" name="password_confirmation" style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-secondary); color: var(--text-primary);" placeholder="Leave blank to keep current">
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" style="flex: 1; padding: 0.75rem; background: var(--green-primary); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600;">Save Changes</button>
                <a href="{{ route('admin.users') }}" style="flex: 1; padding: 0.75rem; background: var(--bg-primary); color: var(--text-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; cursor: pointer; font-weight: 600; text-align: center; text-decoration: none;">Cancel</a>
            </div>
        </form>
    </div>
@endsection
