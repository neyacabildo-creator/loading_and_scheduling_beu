{{-- resources/views/grade-school-admin/users/index.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'User Accounts')

@section('content')
    <style>
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
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
        .btn-activate { background: rgba(45,122,80,0.15); color: #2d7a50; }
        .btn-activate:hover { background: #2d7a50; color: white; }
        .btn-deactivate { background: rgba(200,130,0,0.15); color: #b06000; }
        .btn-deactivate:hover { background: #b06000; color: white; }
        .btn-edit { background: rgba(37,99,235,0.12); color: #1d4ed8; }
        .btn-edit:hover { background: #1d4ed8; color: white; }
        .btn-delete { background: rgba(200,50,50,0.12); color: #c83232; }
        .btn-delete:hover { background: #c83232; color: white; }
        .avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem; margin-right: 0.75rem; }
        .pwd-cell { display: flex; align-items: center; gap: 0.35rem; max-width: 200px; }
        .pwd-mask { font-family: ui-monospace, monospace; font-size: 0.8rem; color: #7a7a6e; letter-spacing: 0.05em; }
        .pwd-plain { font-family: ui-monospace, monospace; font-size: 0.8rem; color: #2d3436; word-break: break-all; }
        .pwd-toggle-btn { padding: 0.25rem 0.5rem; font-size: 0.7rem; border: 1px solid #e8dcc8; border-radius: 0.25rem; background: #f5f3ed; cursor: pointer; color: #2d7a50; font-weight: 600; white-space: nowrap; }
        .pwd-toggle-btn:hover { background: #2d7a50; color: white; }
        .pwd-unavailable { font-size: 0.75rem; color: #9ca3af; font-style: italic; }

        /* Modal styles */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9000; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; border-radius: 0.75rem; width: 100%; max-width: 540px; margin: 1rem; box-shadow: 0 20px 60px rgba(0,0,0,0.25); overflow: hidden; animation: modalSlideIn 0.25s ease; }
        @keyframes modalSlideIn { from { opacity: 0; transform: translateY(-16px); } to { opacity: 1; transform: translateY(0); } }
        .modal-head { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid #e8dcc8; }
        .modal-head-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; }
        .modal-close-btn { background: none; border: none; cursor: pointer; color: #7a7a6e; font-size: 1.25rem; padding: 0.2rem 0.4rem; line-height: 1; border-radius: 0.25rem; }
        .modal-close-btn:hover { background: #f5f3ed; color: #2d3436; }
        .modal-body { padding: 1.5rem; overflow-y: auto; max-height: 65vh; }
        .modal-foot { display: flex; gap: 1rem; justify-content: flex-end; padding: 1rem 1.5rem; border-top: 1px solid #e8dcc8; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-field { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: #2d3436; margin-bottom: 0.4rem; }
        .form-input { width: 100%; padding: 0.65rem 0.875rem; border: 1px solid #e8dcc8; border-radius: 0.5rem; font-size: 0.875rem; color: #2d3436; background: white; font-family: inherit; transition: border-color 0.2s; }
        .form-input:focus { outline: none; border-color: #2d7a50; box-shadow: 0 0 0 3px rgba(45,122,80,0.1); }
        .form-input.is-error { border-color: #c83232; }
        .field-error { display: block; font-size: 0.75rem; color: #c83232; margin-top: 0.25rem; min-height: 1.1em; }
        .modal-err-banner { display: none; background: rgba(200,50,50,0.12); border: 1px solid #c83232; color: #b71c1c; padding: 0.85rem 1rem; border-radius: 0.5rem; margin-bottom: 1.25rem; font-size: 0.875rem; line-height: 1.5; }
        .btn-modal-cancel { padding: 0.65rem 1.25rem; background: transparent; border: 1px solid #e8dcc8; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; color: #7a7a6e; transition: all 0.2s; }
        .btn-modal-cancel:hover { border-color: #2d3436; color: #2d3436; }
        .btn-modal-submit { padding: 0.65rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; color: white; transition: opacity 0.2s; }
        .btn-modal-submit:hover { opacity: 0.9; }
        .btn-modal-submit:disabled { opacity: 0.55; cursor: not-allowed; }
        .success-toast { position: fixed; bottom: 2rem; right: 2rem; background: #2d7a50; color: white; padding: 0.875rem 1.5rem; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999; animation: toastIn 0.3s ease; display: none; }
        @keyframes toastIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* Dark mode */
        html[data-theme="dark"] .table-card { background: var(--bg-secondary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] .table-header { border-color: var(--border-color) !important; }
        html[data-theme="dark"] .table-title { color: var(--text-primary) !important; }
        html[data-theme="dark"] th { background: var(--bg-primary) !important; color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] td { color: var(--text-primary) !important; border-color: var(--border-color) !important; }
        html[data-theme="dark"] tr:hover { background: var(--bg-tertiary) !important; }
        html[data-theme="dark"] .status-inactive { color: var(--text-secondary) !important; }
        html[data-theme="dark"] #searchInput { background: var(--bg-tertiary) !important; border-color: var(--border-color) !important; color: var(--text-primary) !important; }
        html[data-theme="dark"] .modal-box { background: var(--bg-secondary) !important; }
        html[data-theme="dark"] .modal-head { border-color: var(--border-color) !important; }
        html[data-theme="dark"] .modal-head-title { color: var(--text-primary) !important; }
        html[data-theme="dark"] .modal-close-btn { color: var(--text-secondary) !important; }
        html[data-theme="dark"] .modal-close-btn:hover { background: var(--bg-primary) !important; color: var(--text-primary) !important; }
        html[data-theme="dark"] .modal-foot { border-color: var(--border-color) !important; }
        html[data-theme="dark"] .form-label { color: var(--text-primary) !important; }
        html[data-theme="dark"] .form-input { background: var(--bg-tertiary) !important; border-color: var(--border-color) !important; color: var(--text-primary) !important; }
        html[data-theme="dark"] .form-input:focus { border-color: var(--green-primary) !important; }
        html[data-theme="dark"] .btn-modal-cancel { border-color: var(--border-color) !important; color: var(--text-secondary) !important; }
        html[data-theme="dark"] .btn-modal-cancel:hover { border-color: var(--text-primary) !important; color: var(--text-primary) !important; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">User Accounts Management</h1>
        </div>
        <div class="header-right">
            <button onclick="openAddUserModal()" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; font-size: 0.875rem;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                Add User
            </button>
        </div>
    </div>

    <!-- Success toast -->
    <div id="gsSuccessToast" class="success-toast"></div>

    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Grade School Teachers</div>
            <input type="text" id="searchInput" placeholder="Search teachers..." style="padding: 0.5rem 1rem; border: 1px solid #e8dcc8; border-radius: 0.375rem; font-size: 0.875rem;" oninput="filterTable()">
        </div>
        <div style="overflow-x: auto;">
            <table id="usersTable">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>School Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr><td colspan="7" style="padding: 2rem; text-align: center; color: #7a7a6e;">Loading teachers...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="gsAddUserModal" class="modal-overlay" onclick="handleModalOverlay(event)">
        <div class="modal-box" id="gsModalBox">
            <div class="modal-head">
                <span class="modal-head-title">Add New User</span>
                <button class="modal-close-btn" onclick="closeAddUserModal()">✕</button>
            </div>
            <div class="modal-body">
                <div id="gsModalErrBanner" class="modal-err-banner"></div>
                <div class="form-row">
                    <div class="form-field">
                        <label class="form-label">First Name <span style="color:#c83232">*</span></label>
                        <input type="text" id="gs_first_name" class="form-input" placeholder="First name">
                        <span class="field-error" id="gs_err_first_name"></span>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Last Name <span style="color:#c83232">*</span></label>
                        <input type="text" id="gs_last_name" class="form-input" placeholder="Last name">
                        <span class="field-error" id="gs_err_last_name"></span>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label">Email Address <span style="color:#c83232">*</span></label>
                    <input type="email" id="gs_email" class="form-input" placeholder="email@example.com">
                    <span class="field-error" id="gs_err_email"></span>
                </div>
                <div class="form-field">
                    <label class="form-label">Role <span style="color:#c83232">*</span></label>
                    <select id="gs_role_id" class="form-input" onchange="gsOnRoleChange()">
                        <option value="">Select role...</option>
                        <option value="2">Admin - Grade School</option>
                        <option value="4">Teacher - Grade School</option>
                        <option value="7">Shared Teacher</option>
                    </select>
                    <span class="field-error" id="gs_err_role_id"></span>
                </div>
                {{-- Shared Teacher Subjects (conditional) --}}
                <div id="gs_subjects_container" style="display:none;">
                    <div class="form-row">
                        <div class="form-field">
                            <label class="form-label">Primary Subject <span style="color:#c83232">*</span></label>
                            <select id="gs_subject1" class="form-input">
                                <option value="">— Select Subject —</option>
                            </select>
                            <span class="field-error" id="gs_err_subject1"></span>
                        </div>
                        <div class="form-field">
                            <label class="form-label">Secondary Subject <span style="color:#c83232">*</span></label>
                            <select id="gs_subject2" class="form-input">
                                <option value="">— Select Subject —</option>
                            </select>
                            <span class="field-error" id="gs_err_subject2"></span>
                        </div>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label">Password <span style="color:#c83232">*</span></label>
                    <div style="position:relative;">
                        <input type="password" id="gs_password" class="form-input" placeholder="Minimum 8 characters" style="padding-right:2.5rem;">
                        <button type="button" onclick="togglePwd('gs_password',this)" tabindex="-1"
                            style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#7a7a6e;padding:0;line-height:1;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <span class="field-error" id="gs_err_password"></span>
                </div>
                <div class="form-field" style="margin-bottom: 0;">
                    <label class="form-label">Confirm Password <span style="color:#c83232">*</span></label>
                    <div style="position:relative;">
                        <input type="password" id="gs_password_confirmation" class="form-input" placeholder="Repeat password" style="padding-right:2.5rem;">
                        <button type="button" onclick="togglePwd('gs_password_confirmation',this)" tabindex="-1"
                            style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#7a7a6e;padding:0;line-height:1;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <span class="field-error" id="gs_err_password_confirmation"></span>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn-modal-cancel" onclick="closeAddUserModal()">Cancel</button>
                <button class="btn-modal-submit" id="gsSubmitUserBtn" onclick="submitAddUser()">Create User</button>
            </div>
        </div>
    </div>

    <script>
        let allTeachers = [];
        let gsSubjects = [];

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s ?? '';
            return d.innerHTML;
        }

        function toggleUserPassword(id) {
            const mask = document.getElementById('pwd-mask-' + id);
            const plain = document.getElementById('pwd-plain-' + id);
            const btn = document.getElementById('pwd-btn-' + id);
            if (!mask || !plain || !btn) return;
            const show = plain.style.display === 'none';
            plain.style.display = show ? 'inline' : 'none';
            mask.style.display = show ? 'none' : 'inline';
            btn.textContent = show ? 'Hide' : 'Show';
        }

        function loadTeachers() {
            fetch('/api/grade-school-admin/teachers', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                allTeachers = data.data || [];
                renderTeachers(allTeachers);
            })
            .catch(() => {
                document.getElementById('usersTableBody').innerHTML =
                    '<tr><td colspan="7" style="padding:2rem;text-align:center;color:#ef4444;">Error loading teachers. Please refresh.</td></tr>';
            });
        }

        function loadSubjects() {
            fetch('/api/subjects', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.json())
                .then(data => {
                    gsSubjects = data.subjects || [];
                    populateSubjectSelects();
                })
                .catch(err => console.error('Error loading subjects:', err));
        }

        function populateSubjectSelects() {
            const s1 = document.getElementById('gs_subject1');
            const s2 = document.getElementById('gs_subject2');
            const defaultOption = '<option value="">— Select Subject —</option>';
            const options = gsSubjects.map(s => `<option value="${s}">${s}</option>`).join('');
            s1.innerHTML = defaultOption + options;
            s2.innerHTML = defaultOption + options;
        }

        function gsOnRoleChange() {
            const roleId = document.getElementById('gs_role_id').value;
            const container = document.getElementById('gs_subjects_container');
            if (roleId === '7') { // Shared Teacher
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        loadTeachers();
        loadSubjects();

        function renderTeachers(teachers) {
            const tbody = document.getElementById('usersTableBody');
            if (!teachers.length) {
                tbody.innerHTML = '<tr><td colspan="7" style="padding:2rem;text-align:center;color:#7a7a6e;">No Grade School teachers found.</td></tr>';
                return;
            }
            tbody.innerHTML = teachers.map(t => {
                const initials = ((t.first_name || t.name || '?')[0] + (t.last_name ? t.last_name[0] : '')).toUpperCase();
                const displayName = t.first_name && t.last_name ? t.first_name + ' ' + t.last_name : (t.name || '—');
                const roleName = (t.role && t.role.display_name) ? t.role.display_name : 'Grade School Teacher';
                const statusClass = t.is_active ? 'status-active' : 'status-inactive';
                const statusText  = t.is_active ? 'Active' : 'Inactive';
                const pwdCell = t.plain_password
                    ? `<div class="pwd-cell"><span class="pwd-mask" id="pwd-mask-${t.id}">••••••••</span><span class="pwd-plain" id="pwd-plain-${t.id}" style="display:none;">${escapeHtml(t.plain_password)}</span><button type="button" class="pwd-toggle-btn" onclick="toggleUserPassword(${t.id})" id="pwd-btn-${t.id}">Show</button></div>`
                    : `<span class="pwd-unavailable" title="Re-save password in Edit to store a retrievable copy">Not stored</span>`;
                return `<tr id="gs-teacher-row-${t.id}">
                    <td><span class="avatar">${initials}</span><strong>${displayName}</strong></td>
                    <td>${t.email}</td>
                    <td>${pwdCell}</td>
                    <td>${roleName}</td>
                    <td><span style="background:#d1fae5;color:#065f46;padding:0.25rem 0.75rem;border-radius:9999px;font-size:0.75rem;font-weight:500;">Grade School</span></td>
                    <td><span id="gs-status-badge-${t.id}" class="status-badge ${statusClass}">${statusText}</span></td>
                    <td style="white-space:nowrap;">
                        <button class="action-btn btn-edit" onclick="openEditModal(${t.id})" style="margin-right:0.35rem;">Edit</button>
                        <button class="action-btn btn-delete" onclick="deleteTeacher(${t.id})">Delete</button>
                    </td>
                </tr>`;
            }).join('');
        }

        function filterTable() {
            const q = document.getElementById('searchInput').value.toLowerCase();
            renderTeachers(q ? allTeachers.filter(t =>
                (t.name || '').toLowerCase().includes(q) ||
                (t.first_name || '').toLowerCase().includes(q) ||
                (t.last_name || '').toLowerCase().includes(q) ||
                (t.email || '').toLowerCase().includes(q)
            ) : allTeachers);
        }

        function toggleActive(id, currentlyActive) {
            const btn = document.getElementById('gs-toggle-btn-' + id);
            btn.disabled = true;
            btn.textContent = '...';
            fetch('/api/grade-school-admin/teachers/' + id + '/toggle-active', {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const isNowActive = data.is_active;
                    const badge = document.getElementById('gs-status-badge-' + id);
                    badge.className = 'status-badge ' + (isNowActive ? 'status-active' : 'status-inactive');
                    badge.textContent = isNowActive ? 'Active' : 'Inactive';
                    btn.className = 'action-btn ' + (isNowActive ? 'btn-deactivate' : 'btn-activate');
                    btn.textContent = isNowActive ? 'Deactivate' : 'Activate';
                    btn.onclick = () => toggleActive(id, isNowActive);
                    const t = allTeachers.find(x => x.id === id);
                    if (t) t.is_active = isNowActive;
                } else {
                    alert(data.message || 'Failed to update status.');
                }
                btn.disabled = false;
            })
            .catch(() => {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.textContent = currentlyActive ? 'Deactivate' : 'Activate';
            });
        }

        /* ── Modal ── */
        const GS_FIELDS = ['first_name', 'last_name', 'email', 'role_id', 'password', 'password_confirmation'];

        function openAddUserModal() {
            clearModalForm();
            document.getElementById('gsAddUserModal').classList.add('open');
            setTimeout(() => document.getElementById('gs_first_name').focus(), 50);
        }

        function closeAddUserModal() {
            document.getElementById('gsAddUserModal').classList.remove('open');
        }

        function handleModalOverlay(e) {
            if (e.target === document.getElementById('gsAddUserModal')) closeAddUserModal();
        }

        function clearModalForm() {
            GS_FIELDS.forEach(f => {
                const el = document.getElementById('gs_' + f);
                if (el) { el.value = ''; el.classList.remove('is-error'); }
                const errEl = document.getElementById('gs_err_' + f);
                if (errEl) errEl.textContent = '';
            });
            const banner = document.getElementById('gsModalErrBanner');
            banner.style.display = 'none';
            banner.textContent = '';
        }

        function setFieldError(field, msg) {
            const input = document.getElementById('gs_' + field);
            const errEl = document.getElementById('gs_err_' + field);
            if (input) input.classList.toggle('is-error', !!msg);
            if (errEl) errEl.textContent = msg || '';
        }

        function submitAddUser() {
            const btn = document.getElementById('gsSubmitUserBtn');
            btn.disabled = true;
            btn.textContent = 'Creating...';

            GS_FIELDS.forEach(f => setFieldError(f, ''));
            document.getElementById('gsModalErrBanner').style.display = 'none';

            const roleId = document.getElementById('gs_role_id').value;
            const payload = {
                first_name:            document.getElementById('gs_first_name').value.trim(),
                last_name:             document.getElementById('gs_last_name').value.trim(),
                email:                 document.getElementById('gs_email').value.trim(),
                role_id:               roleId,
                password:              document.getElementById('gs_password').value,
                password_confirmation: document.getElementById('gs_password_confirmation').value,
            };

            // Add subjects for shared teachers
            if (roleId === '7') {
                payload.subject1 = document.getElementById('gs_subject1').value.trim();
                payload.subject2 = document.getElementById('gs_subject2').value.trim();
            }

            fetch('/grade-school-admin/users', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    const credName  = payload.first_name + ' ' + payload.last_name;
                    const credEmail = payload.email;
                    const credPwd   = payload.password;
                    closeAddUserModal();
                    loadTeachers();
                    showCredentialsCard(credName, credEmail, credPwd);
                } else {
                    const errors = json.errors || {};
                    GS_FIELDS.forEach(f => setFieldError(f, (errors[f] || [])[0] || ''));
                    const banner = document.getElementById('gsModalErrBanner');
                    const emailErr = (errors.email || [])[0] || '';
                    if (emailErr) {
                        // Email-specific error (duplicate / invalid) — show prominent banner
                        banner.innerHTML =
                            '<strong>&#9888; Email already in use</strong><br>' +
                            emailErr;
                        banner.style.display = 'block';
                        // Scroll the email field into view inside the modal
                        const emailInput = document.getElementById('gs_email');
                        if (emailInput) {
                            emailInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            emailInput.focus();
                        }
                    } else if (json.message && !Object.keys(errors).length) {
                        banner.textContent = json.message;
                        banner.style.display = 'block';
                    }
                }
                btn.disabled = false;
                btn.textContent = 'Create User';
            })
            .catch(() => {
                const banner = document.getElementById('gsModalErrBanner');
                banner.textContent = 'Network error. Please try again.';
                banner.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Create User';
            });
        }

        function showToast(msg) {
            const toast = document.getElementById('gsSuccessToast');
            toast.textContent = msg;
            toast.style.display = 'block';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 4000);
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') { closeAddUserModal(); closeEditModal(); }
        });

        function togglePwd(id, btn) {
            const input = document.getElementById(id);
            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.innerHTML = showing
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';
        }

        function showCredentialsCard(name, email, password) {
            document.getElementById('gs_cred_name').textContent  = name;
            document.getElementById('gs_cred_email').textContent = email;
            document.getElementById('gs_cred_pwd').textContent   = password;
            document.getElementById('gsCredCard').style.display  = 'flex';
        }

        function closeCredCard() {
            document.getElementById('gsCredCard').style.display = 'none';
        }

        function copyCredentials() {
            const name  = document.getElementById('gs_cred_name').textContent;
            const email = document.getElementById('gs_cred_email').textContent;
            const pwd   = document.getElementById('gs_cred_pwd').textContent;
            const text  = 'Account Credentials\nName: ' + name + '\nEmail: ' + email + '\nPassword: ' + pwd;
            navigator.clipboard.writeText(text).then(function() {
                const btn = document.getElementById('gsCopyCredsBtn');
                btn.textContent = 'Copied!';
                setTimeout(function() { btn.textContent = 'Copy Credentials'; }, 2000);
            }).catch(function() {
                alert('Copy failed. Please note the credentials manually.');
            });
        }
        function openEditModal(id) {
            const t = allTeachers.find(x => x.id === id);
            if (!t) return;
            document.getElementById('gs_edit_id').value        = id;
            document.getElementById('gs_edit_first_name').value = t.first_name || '';
            document.getElementById('gs_edit_last_name').value  = t.last_name  || '';
            document.getElementById('gs_edit_email').value      = t.email      || '';
            clearEditErrors();
            document.getElementById('gsEditUserModal').classList.add('open');
            setTimeout(() => document.getElementById('gs_edit_first_name').focus(), 50);
        }

        function closeEditModal() {
            document.getElementById('gsEditUserModal').classList.remove('open');
        }

        function clearEditErrors() {
            ['first_name','last_name','email'].forEach(f => {
                const el = document.getElementById('gs_edit_' + f);
                if (el) el.classList.remove('is-error');
                const err = document.getElementById('gs_edit_err_' + f);
                if (err) err.textContent = '';
            });
            const banner = document.getElementById('gsEditErrBanner');
            banner.style.display = 'none';
            banner.textContent = '';
        }

        function submitEditUser() {
            const btn = document.getElementById('gsEditUserBtn');
            const id  = parseInt(document.getElementById('gs_edit_id').value);
            btn.disabled = true;
            btn.textContent = 'Saving...';
            clearEditErrors();
            const payload = {
                first_name: document.getElementById('gs_edit_first_name').value.trim(),
                last_name:  document.getElementById('gs_edit_last_name').value.trim(),
                email:      document.getElementById('gs_edit_email').value.trim(),
            };
            fetch('/api/grade-school-admin/teachers/' + id, {
                method: 'PUT',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(json => {
                if (json.success) {
                    closeEditModal();
                    loadTeachers();
                    showToast('User updated successfully.');
                } else {
                    const errors = json.errors || {};
                    ['first_name','last_name','email'].forEach(f => {
                        const msg = (errors[f] || [])[0];
                        if (msg) {
                            const el = document.getElementById('gs_edit_' + f);
                            if (el) el.classList.add('is-error');
                            const errEl = document.getElementById('gs_edit_err_' + f);
                            if (errEl) errEl.textContent = msg;
                        }
                    });
                    if (json.message && !Object.keys(errors).length) {
                        const banner = document.getElementById('gsEditErrBanner');
                        banner.textContent = json.message;
                        banner.style.display = 'block';
                    }
                }
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            })
            .catch(() => {
                const banner = document.getElementById('gsEditErrBanner');
                banner.textContent = 'Network error. Please try again.';
                banner.style.display = 'block';
                btn.disabled = false;
                btn.textContent = 'Save Changes';
            });
        }

        function deleteTeacher(id) {
            const t = allTeachers.find(x => x.id === id);
            const name = t ? ((t.first_name || '') + ' ' + (t.last_name || '')).trim() : 'this user';
            if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
            fetch('/api/grade-school-admin/teachers/' + id, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    allTeachers = allTeachers.filter(x => x.id !== id);
                    renderTeachers(allTeachers);
                    showToast('User deleted.');
                } else {
                    alert(data.message || 'Failed to delete user.');
                }
            })
            .catch(() => alert('Network error. Please try again.'));
        }
    </script>

    <!-- Edit User Modal -->
    <div id="gsEditUserModal" class="modal-overlay" onclick="if(event.target===this)closeEditModal()">
        <div class="modal-box">
            <div class="modal-head">
                <span class="modal-head-title">Edit User</span>
                <button class="modal-close-btn" onclick="closeEditModal()">✕</button>
            </div>
            <div class="modal-body">
                <div id="gsEditErrBanner" class="modal-err-banner"></div>
                <input type="hidden" id="gs_edit_id">
                <div class="form-row">
                    <div class="form-field">
                        <label class="form-label">First Name</label>
                        <input type="text" id="gs_edit_first_name" class="form-input" placeholder="First name">
                        <span class="field-error" id="gs_edit_err_first_name"></span>
                    </div>
                    <div class="form-field">
                        <label class="form-label">Last Name</label>
                        <input type="text" id="gs_edit_last_name" class="form-input" placeholder="Last name">
                        <span class="field-error" id="gs_edit_err_last_name"></span>
                    </div>
                </div>
                <div class="form-field" style="margin-bottom:0;">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="gs_edit_email" class="form-input" placeholder="email@example.com">
                    <span class="field-error" id="gs_edit_err_email"></span>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn-modal-cancel" onclick="closeEditModal()">Cancel</button>
                <button class="btn-modal-submit" id="gsEditUserBtn" onclick="submitEditUser()">Save Changes</button>
            </div>
        </div>
    </div>

    {{-- Credential Card — shown once after account creation so admin can share with instructor --}}
    <div id="gsCredCard" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.6);align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:.75rem;width:100%;max-width:400px;margin:1rem;padding:2rem;box-shadow:0 24px 64px rgba(0,0,0,.3);">
            <div style="text-align:center;margin-bottom:1.5rem;">
                <div style="background:#d1fae5;width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;margin-bottom:.75rem;">
                    <svg width="28" height="28" fill="none" stroke="#2d7a50" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:#2d3436;margin-bottom:.3rem;">Account Created</div>
                <div style="font-size:.8rem;color:#7a7a6e;line-height:1.5;">Share these login credentials with the instructor.<br>This will <strong>not</strong> be shown again.</div>
            </div>
            <div style="background:#f5f3ed;border-radius:.5rem;padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;flex-direction:column;gap:.75rem;">
                <div>
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#7a7a6e;margin-bottom:.2rem;">Name</div>
                    <div id="gs_cred_name" style="font-weight:600;color:#2d3436;"></div>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#7a7a6e;margin-bottom:.2rem;">Email (Login)</div>
                    <div id="gs_cred_email" style="color:#2d3436;word-break:break-all;"></div>
                </div>
                <div>
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#7a7a6e;margin-bottom:.2rem;">Password</div>
                    <div id="gs_cred_pwd" style="font-weight:700;font-family:monospace;font-size:1.05rem;color:#1a5336;letter-spacing:.04em;"></div>
                </div>
            </div>
            <div style="display:flex;flex-direction:column;gap:.6rem;">
                <button id="gsCopyCredsBtn" onclick="copyCredentials()" style="width:100%;padding:.75rem;background:linear-gradient(135deg,#2d7a50,#1a5336);color:#fff;border:none;border-radius:.5rem;font-weight:600;cursor:pointer;font-size:.875rem;">Copy Credentials</button>
                <button onclick="closeCredCard()" style="width:100%;padding:.75rem;background:transparent;border:1px solid #e8dcc8;border-radius:.5rem;font-weight:500;cursor:pointer;font-size:.875rem;color:#7a7a6e;">Done</button>
            </div>
        </div>
    </div>

@endsection
