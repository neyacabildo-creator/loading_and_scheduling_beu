@php
    $user = Auth::user();
    $displayName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? 'Teacher');
    $initial = strtoupper(substr($displayName, 0, 1));
    $photoUrl = $user->profile_photo_path
        ? asset('storage/' . ltrim($user->profile_photo_path, '/'))
        : null;
    $photoRoute = $photoRoute ?? route('teacher.profile.photo');
    $updateRoute = $updateRoute ?? route('teacher.profile.update');
    $divisionLabel = $divisionLabel ?? 'Teacher Portal';
@endphp

<style>
    .profile-hero {
        background: linear-gradient(135deg, #1a5336 0%, #2d7a50 60%, #3d9970 100%);
        border-radius: 0.75rem;
        padding: 2rem;
        margin-bottom: 1.75rem;
        color: #fff;
    }
    .profile-hero .eyebrow {
        font-size: 0.78rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        opacity: 0.75;
        margin: 0 0 0.35rem;
    }
    .profile-hero h1 {
        font-size: 1.75rem;
        font-weight: 800;
        margin: 0 0 0.35rem;
    }
    .profile-hero p {
        margin: 0;
        font-size: 0.875rem;
        opacity: 0.8;
    }
    .profile-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    @media (min-width: 900px) {
        .profile-grid { grid-template-columns: 280px 1fr; }
    }
    .profile-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        padding: 1.5rem;
    }
    .profile-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--green-primary, #2d7a50);
    }
    .profile-avatar-wrap {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 1rem;
        background: linear-gradient(135deg, #2d7a50, #1a5336);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 4px solid rgba(45, 122, 80, 0.2);
    }
    .profile-avatar-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .profile-avatar-wrap span {
        color: #fff;
        font-size: 2.5rem;
        font-weight: 800;
    }
    .profile-name {
        text-align: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-primary);
        margin: 0 0 0.25rem;
    }
    .profile-email {
        text-align: center;
        font-size: 0.82rem;
        color: var(--text-secondary);
        margin: 0 0 1rem;
        word-break: break-all;
    }
    .profile-field { margin-bottom: 1rem; }
    .profile-field label {
        display: block;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text-primary);
        margin-bottom: 0.35rem;
    }
    .profile-field input[type="file"],
    .profile-field input[type="password"] {
        width: 100%;
        padding: 0.65rem 0.75rem;
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        background: var(--bg-primary);
        color: var(--text-primary);
        font-size: 0.875rem;
        box-sizing: border-box;
    }
    .profile-field input:focus {
        outline: none;
        border-color: var(--green-primary, #2d7a50);
        box-shadow: 0 0 0 3px rgba(45, 122, 80, 0.12);
    }
    .profile-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.7rem 1.25rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.15s, background 0.15s;
    }
    .profile-btn-primary {
        background: var(--green-primary, #2d7a50);
        color: #fff;
        width: 100%;
    }
    .profile-btn-primary:hover { background: #1a5c3a; transform: translateY(-1px); }
    .profile-alert {
        padding: 0.85rem 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1.25rem;
        font-size: 0.875rem;
    }
    .profile-alert-success {
        background: #e8f5e9;
        border: 1px solid #a5d6a7;
        color: #2e7d32;
    }
    .profile-alert-error {
        background: #ffebee;
        border: 1px solid #ef9a9a;
        color: #c62828;
    }
    .profile-hint {
        font-size: 0.78rem;
        color: var(--text-secondary);
        margin-top: 0.35rem;
    }
</style>

@include('partials.teacher-page-banner', [
    'eyebrow' => $divisionLabel,
    'pageTitle' => 'My Profile',
    'pageSubtitle' => 'Manage your profile photo and account password.',
    'notificationsApi' => str_contains($photoRoute ?? '', 'grade-school-teacher')
        ? '/api/grade-school-teacher/notifications'
        : '/api/teacher/notifications',
])

<div class="profile-grid">
    <div class="profile-card">
        <h2 class="profile-card-title">Account</h2>
        <div id="photoPreviewWrap" class="profile-avatar-wrap">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="Profile photo">
            @else
                <span>{{ $initial }}</span>
            @endif
        </div>
        <p class="profile-name">{{ $displayName }}</p>
        <p class="profile-email">{{ $user->email }}</p>
        <form method="POST" action="{{ $photoRoute }}" enctype="multipart/form-data">
            @csrf
            <div class="profile-field">
                <label for="profilePhotoInput">Upload new photo</label>
                <input type="file" id="profilePhotoInput" name="photo" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" onchange="previewProfilePhoto(this)">
                <p class="profile-hint">JPG, PNG, GIF or WebP. Max 2 MB.</p>
            </div>
            @error('photo')
                <p class="profile-hint" style="color:#c62828;">{{ $message }}</p>
            @enderror
            <button type="submit" class="profile-btn profile-btn-primary">Save Photo</button>
        </form>
    </div>

    <div class="profile-card">
        <h2 class="profile-card-title">Change Password</h2>
        <form method="POST" action="{{ $updateRoute }}">
            @csrf
            @method('PUT')
            <div class="profile-field">
                <label for="current_password">Current password</label>
                <input type="password" id="current_password" name="current_password" autocomplete="current-password" placeholder="Required when setting a new password">
                @error('current_password')
                    <p class="profile-hint" style="color:#c62828;">{{ $message }}</p>
                @enderror
            </div>
            <div class="profile-field">
                <label for="password">New password</label>
                <input type="password" id="password" name="password" autocomplete="new-password" placeholder="At least 8 characters">
                @error('password')
                    <p class="profile-hint" style="color:#c62828;">{{ $message }}</p>
                @enderror
            </div>
            <div class="profile-field">
                <label for="password_confirmation">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" placeholder="Re-enter new password">
            </div>
            <p class="profile-hint">Leave new password fields blank to keep your current password.</p>
            <button type="submit" class="profile-btn profile-btn-primary" style="margin-top:0.5rem;width:auto;">Update Password</button>
        </form>
    </div>
</div>

<script>
function previewProfilePhoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const wrap = document.getElementById('photoPreviewWrap');
        wrap.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
