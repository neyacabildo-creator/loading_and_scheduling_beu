{{-- resources/views/admin/settings/index.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'System Settings')

@section('content')
    <style>
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .settings-container { display: grid; grid-template-columns: 250px 1fr; gap: 2rem; }
        .settings-nav { display: flex; flex-direction: column; gap: 0.5rem; background: white; padding: 1rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; height: fit-content; position: sticky; top: 2rem; }
        .settings-nav-item { padding: 0.75rem 1rem; border-left: 3px solid transparent; cursor: pointer; border-radius: 0.25rem; font-size: 0.875rem; font-weight: 500; color: #7a7a6e; transition: all 0.2s; }
        .settings-nav-item:hover { background: #f5f3ed; color: #2d3436; }
        .settings-nav-item.active { background: rgba(45, 122, 80, 0.1); color: #2d7a50; border-left-color: #2d7a50; }
        .settings-content { background: white; padding: 2rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .setting-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #e8dcc8; }
        .setting-section:last-child { border-bottom: none; }
        .section-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; margin-bottom: 1rem; }
        .setting-group { margin-bottom: 1.5rem; }
        .setting-label { font-size: 0.875rem; font-weight: 600; color: #2d3436; margin-bottom: 0.5rem; display: block; }
        .setting-description { font-size: 0.8rem; color: #7a7a6e; margin-bottom: 0.5rem; }
        .setting-input { width: 100%; padding: 0.5rem 1rem; border: 1px solid #e8dcc8; border-radius: 0.375rem; font-size: 0.875rem; background: white; }
        .setting-input-group { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .button-group { display: flex; gap: 1rem; margin-top: 2rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45, 122, 80, 0.3); }
        .btn-secondary { background: white; border: 1px solid #e8dcc8; color: #2d3436; }
        .btn-secondary:hover { border-color: #2d7a50; color: #2d7a50; }
        .toggle-switch { display: inline-flex; align-items: center; gap: 0.75rem; }
        .switch { width: 50px; height: 28px; background: #e0e0e0; border-radius: 14px; position: relative; cursor: pointer; transition: background 0.3s; }
        .switch.enabled { background: #2d7a50; }
        .switch-thumb { width: 24px; height: 24px; background: white; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: left 0.3s; }
        .switch.enabled .switch-thumb { left: 24px; }
        .info-box { background: #dbeafe; border: 1px solid #bfdbfe; padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; color: #0c4a6e; font-size: 0.875rem; }

        html[data-theme="dark"] .settings-nav,
        html[data-theme="dark"] .settings-content,
        html[data-theme="dark"] div[style*="background: #f5f3ed; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;"] {
            background: #2d2d2d !important;
            border-color: #404040 !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35) !important;
        }

        html[data-theme="dark"] .settings-nav-item:hover {
            background: #343434;
            color: #e0e0e0;
        }

        html[data-theme="dark"] .section-title,
        html[data-theme="dark"] .setting-label,
        html[data-theme="dark"] h2,
        html[data-theme="dark"] h1,
        html[data-theme="dark"] span,
        html[data-theme="dark"] div[style*="font-size: 0.875rem; color: #2d3436"] {
            color: #e0e0e0 !important;
        }

        html[data-theme="dark"] .settings-nav-item,
        html[data-theme="dark"] .setting-description,
        html[data-theme="dark"] div[style*="font-size: 0.8rem; color: #7a7a6e"] {
            color: #e0e0e0 !important;
        }

        html[data-theme="dark"] .setting-input {
            background: #3a3a3a;
            color: #e0e0e0;
            border-color: #4a4a4a;
        }

        html[data-theme="dark"] .btn-secondary {
            background: #3a3a3a;
            color: #e0e0e0;
            border-color: #4a4a4a;
        }

        html[data-theme="dark"] .setting-section {
            border-bottom-color: #404040;
        }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">System Settings</h1>
        </div>
    </div>

    <!-- Settings Content -->
    <div class="settings-container">
        <!-- Navigation -->
        <nav class="settings-nav">
            <div class="settings-nav-item active" onclick="switchTab('profile')"> My Profile</div>
            <div class="settings-nav-item" onclick="switchTab('general')"> General</div>
            <div class="settings-nav-item" onclick="switchTab('loading')"> Loading Rules</div>
            <div class="settings-nav-item" onclick="switchTab('scheduling')"> Scheduling</div>
            <div class="settings-nav-item" onclick="switchTab('notifications')"> Notifications</div>
            <div class="settings-nav-item" onclick="switchTab('backup')"> Backup & Recovery</div>
        </nav>

        <!-- Content -->
        <div class="settings-content">
            <!-- Profile Settings -->
            <!-- General Settings -->
            <div id="general" class="tab-content" style="display: none;">
                <div class="setting-section">
                    <h2 class="section-title">Institution Information</h2>
                    <div class="setting-group">
                        <label class="setting-label">Institution Name</label>
                        <input type="text" class="setting-input" value="Bestlink Educational University" readonly>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Abbreviation</label>
                        <input type="text" class="setting-input" value="BEU" readonly>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Department</label>
                        <input type="text" class="setting-input" value="Grade School" readonly>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Academic Year</label>
                        <select class="setting-input">
                            <option>2023-2024</option>
                            <option>2024-2025</option>
                            <option selected>2025-2026</option>
                        </select>
                    </div>
                </div>

                <div class="setting-section">
                    <h2 class="section-title">System Preferences</h2>
                    <div class="setting-group">
                        <label class="setting-label">Time Format</label>
                        <select class="setting-input">
                            <option selected>12-hour (12:00 AM - 11:59 PM)</option>
                            <option>24-hour (00:00 - 23:59)</option>
                        </select>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Date Format</label>
                        <select class="setting-input">
                            <option>MM/DD/YYYY</option>
                            <option selected>DD/MM/YYYY</option>
                            <option>YYYY-MM-DD</option>
                        </select>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Default Language</label>
                        <select class="setting-input">
                            <option selected>English (US)</option>
                            <option>Filipino (PH)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Loading Rules -->
            <div id="loading" class="tab-content" style="display: none;">
                <div class="setting-section">
                    <h2 class="section-title">Faculty Load Parameters</h2>
                    <div class="setting-input-group">
                        <div class="setting-group">
                            <label class="setting-label">Maximum Load Hours</label>
                            <div class="setting-description">Maximum teaching hours a faculty can be assigned</div>
                            <input type="number" class="setting-input" value="24" min="1">
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">Minimum Load Hours</label>
                            <div class="setting-description">Minimum teaching hours for full-time faculty</div>
                            <input type="number" class="setting-input" value="12" min="1">
                        </div>
                    </div>
                    <div class="setting-input-group">
                        <div class="setting-group">
                            <label class="setting-label">Lab Hour Multiplier</label>
                            <div class="setting-description">How many lab hours equal one classroom hour</div>
                            <input type="number" class="setting-input" value="2" step="0.5" min="0">
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">Practicum Hour Multiplier</label>
                            <div class="setting-description">How many practicum hours equal one classroom hour</div>
                            <input type="number" class="setting-input" value="1.5" step="0.5" min="0">
                        </div>
                    </div>
                </div>

                <div class="setting-section">
                    <h2 class="section-title">Load Validation Rules</h2>
                    <div class="setting-group">
                        <label class="setting-label">Allow Over-Assignment</label>
                        <div class="toggle-switch">
                            <div class="switch enabled" onclick="this.classList.toggle('enabled')">
                                <div class="switch-thumb"></div>
                            </div>
                            <span>Currently Enabled</span>
                        </div>
                        <div class="info-box">Allows faculty to be assigned more than maximum load hours with admin approval</div>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Maximum Additional Hours</label>
                        <div class="setting-description">Maximum overage allowed when over-assignment is permitted</div>
                        <input type="number" class="setting-input" value="6" min="0">
                    </div>
                </div>
            </div>

            <!-- Scheduling Settings -->
            <div id="scheduling" class="tab-content" style="display: none;">
                <div class="setting-section">
                    <h2 class="section-title">Schedule Time Slots</h2>
                    <div class="setting-input-group">
                        <div class="setting-group">
                            <label class="setting-label">Earliest Class Time</label>
                            <input type="time" class="setting-input" value="07:00">
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">Latest Class Time</label>
                            <input type="time" class="setting-input" value="18:00">
                        </div>
                    </div>
                    <div class="setting-input-group">
                        <div class="setting-group">
                            <label class="setting-label">Class Duration Options</label>
                            <select class="setting-input" multiple style="height: 100px;">
                                <option selected>1 hour</option>
                                <option selected>1.5 hours</option>
                                <option selected>2 hours</option>
                                <option selected>2.5 hours</option>
                                <option selected>3 hours</option>
                            </select>
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">Default Break Duration</label>
                            <div class="setting-description">Minutes between classes</div>
                            <input type="number" class="setting-input" value="15" min="0"> minutes
                        </div>
                    </div>
                </div>

                <div class="setting-section">
                    <h2 class="section-title">Room Constraints</h2>
                    <div class="setting-group">
                        <label class="setting-label">Max Occupancy Percentage</label>
                        <div class="setting-description">Maximum percentage of room capacity to assign</div>
                        <input type="number" class="setting-input" value="90" min="0" max="100"> %
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Allow Room Sharing</label>
                        <div class="toggle-switch">
                            <div class="switch" onclick="this.classList.toggle('enabled')">
                                <div class="switch-thumb"></div>
                            </div>
                            <span>Currently Disabled</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div id="notifications" class="tab-content" style="display: none;">
                <div class="setting-section">
                    <h2 class="section-title">Notification Settings</h2>
                    <div class="setting-group">
                        <label class="setting-label">Email Notifications</label>
                        <div class="toggle-switch">
                            <div class="switch enabled" onclick="this.classList.toggle('enabled')">
                                <div class="switch-thumb"></div>
                            </div>
                            <span>Currently Enabled</span>
                        </div>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Notify on Schedule Changes</label>
                        <div class="toggle-switch">
                            <div class="switch enabled" onclick="this.classList.toggle('enabled')">
                                <div class="switch-thumb"></div>
                            </div>
                            <span>Currently Enabled</span>
                        </div>
                    </div>
                    <div class="setting-group">
                        <label class="setting-label">Notify on Conflicts</label>
                        <div class="toggle-switch">
                            <div class="switch enabled" onclick="this.classList.toggle('enabled')">
                                <div class="switch-thumb"></div>
                            </div>
                            <span>Currently Enabled</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup & Recovery -->
            <div id="backup" class="tab-content" style="display: none;">

                @if($errors->has('backup_file'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1.25rem;">
                        &#9888; {{ $errors->first('backup_file') }}
                    </div>
                @endif

                {{-- Download Backup --}}
                <div class="setting-section">
                    <h2 class="section-title">&#8659; Create Backup</h2>
                    <p class="setting-description" style="margin-bottom:1rem;">
                        Downloads a JSON file containing <strong>all class schedules</strong> (including pending, approved, rejected, and soft-deleted records)
                        plus faculty load data for the Grade School department.
                    </p>
                    <a href="{{ route('grade-school-admin.backup.download') }}" class="btn btn-primary" style="display:inline-block;text-decoration:none;">
                        &#8659; Download Backup (JSON)
                    </a>
                    <div class="info-box" style="margin-top:1rem;">
                        <strong>What is included:</strong> class_schedules table (all rows, all statuses) and faculty_loads table.
                        The file is saved on the server and also downloaded to your browser.
                    </div>
                </div>

                {{-- Previous Backups --}}
                @if(!empty($backupFiles))
                <div class="setting-section">
                    <h2 class="section-title">&#128190; Previous Backups</h2>
                    <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                        <thead>
                            <tr style="background:#f5f3ed;">
                                <th style="text-align:left;padding:0.5rem 0.75rem;border-bottom:1px solid #e8dcc8;">File</th>
                                <th style="text-align:left;padding:0.5rem 0.75rem;border-bottom:1px solid #e8dcc8;">Date</th>
                                <th style="text-align:right;padding:0.5rem 0.75rem;border-bottom:1px solid #e8dcc8;">Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backupFiles as $bf)
                            <tr>
                                <td style="padding:0.5rem 0.75rem;border-bottom:1px solid #f0ece4;">{{ $bf['name'] }}</td>
                                <td style="padding:0.5rem 0.75rem;border-bottom:1px solid #f0ece4;">{{ $bf['date'] }}</td>
                                <td style="padding:0.5rem 0.75rem;border-bottom:1px solid #f0ece4;text-align:right;">{{ $bf['size'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Restore Backup --}}
                <div class="setting-section">
                    <h2 class="section-title">&#8679; Restore from Backup</h2>
                    <p class="setting-description" style="margin-bottom:1rem;">
                        Upload a previously downloaded backup JSON file. Existing records will be updated; missing records will be re-inserted.
                    </p>
                    <div style="background:#fef9c3;border:1px solid #fde68a;color:#78350f;padding:0.875rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">
                        <strong>&#9888; Warning:</strong> Restoring will overwrite current database entries with the values from the backup.
                        This action cannot be undone. Make a fresh backup first if needed.
                    </div>
                    <form method="POST" action="{{ route('grade-school-admin.backup.restore') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="setting-group">
                            <label class="setting-label">Select Backup File (.json)</label>
                            <input type="file" name="backup_file" accept=".json" class="setting-input" required>
                        </div>
                        <button type="submit" class="btn btn-secondary" style="margin-top:0.75rem;"
                            onclick="return confirm('Are you sure you want to restore this backup? This will overwrite existing records.');">
                            &#8679; Restore Backup
                        </button>
                    </form>
                </div>

            </div>

            <!-- My Profile Tab -->
            <div id="profile" class="tab-content">

                <!-- Profile Photo Section -->
                <div class="setting-section">
                    <h2 class="section-title">Profile Photo</h2>
                    <div style="display:flex;align-items:center;gap:2rem;margin-bottom:1.5rem;">
                        <div id="photoPreviewWrap" style="width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,#2d7a50,#1a5336);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                            @if(Auth::user()->profile_photo_path)
                                <img id="photoPreview" src="{{ Storage::url(Auth::user()->profile_photo_path) }}" style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span id="photoPreview" style="color:white;font-size:1.5rem;font-weight:700;display:flex;align-items:center;justify-content:center;width:100%;height:100%;">{{ strtoupper(substr(Auth::user()->first_name ?? 'G', 0, 1)) }}</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('grade-school-admin.profile.photo') }}" enctype="multipart/form-data" style="flex:1;">
                            @csrf
                            <div class="setting-group">
                                <label class="setting-label">Upload New Photo</label>
                                <input type="file" name="photo" accept="image/*" class="setting-input" onchange="previewAdminPhoto(this)">
                            </div>
                            <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">Upload Photo</button>
                        </form>
                    </div>
                </div>

                <div class="setting-section">
                    <h2 class="section-title">My Profile</h2>
                    <form method="POST" action="{{ route('grade-school-admin.profile.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="setting-group">
                            <label class="setting-label">First Name</label>
                            <input type="text" name="first_name" class="setting-input" value="{{ Auth::user()->first_name ?? '' }}">
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">Last Name</label>
                            <input type="text" name="last_name" class="setting-input" value="{{ Auth::user()->last_name ?? '' }}">
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">Email Address</label>
                            <input type="email" name="email" class="setting-input" value="{{ Auth::user()->email }}">
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">New Password <span style="font-weight:400;color:#7a7a6e;">(leave blank to keep current)</span></label>
                            <input type="password" name="password" class="setting-input" placeholder="Enter new password">
                        </div>
                        <div class="setting-group">
                            <label class="setting-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="setting-input" placeholder="Confirm new password">
                        </div>
                        <div class="button-group" style="margin-top:1.5rem;">
                            <button type="submit" class="btn btn-primary"> Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Save Button (shown for tabs other than profile/backup) -->
            <div id="save-btn-group" class="button-group" style="display:none;">
                <button class="btn btn-primary"> Save Changes</button>
                <button class="btn btn-secondary">&#x21BA; Reset to Defaults</button>
            </div>
        </div>
    </div>

    <script>
        function previewAdminPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const wrap = document.getElementById('photoPreviewWrap');
                    wrap.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            var params = new URLSearchParams(window.location.search);
            var tab = params.get('tab') || 'profile';
            switchTab(tab);
        });

        function switchTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
            // Show selected
            document.getElementById(tabName).style.display = 'block';
            
            // Toggle global save button (hide for profile & backup — those have their own actions)
            document.getElementById('save-btn-group').style.display = (tabName === 'profile' || tabName === 'backup') ? 'none' : 'flex';

            // Update nav active — use attribute selector to avoid relying on event.target
            document.querySelectorAll('.settings-nav-item').forEach(el => el.classList.remove('active'));
            var navEl = document.querySelector('.settings-nav-item[onclick*="\'' + tabName + '\'"]');
            if (navEl) navEl.classList.add('active');
        }
    </script>

@endsection
