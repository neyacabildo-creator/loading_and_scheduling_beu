{{-- Request Schedule Adjustments - STL View --}}
@extends('layouts.teacher')

@section('title', 'Request Schedule Adjustments')

@section('content')
    <style>
        .header-section { background: linear-gradient(135deg, var(--green-primary) 0%, #0d3d20 100%); color: white; padding: 2rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .header-title { font-size: 1.875rem; font-weight: bold; margin: 0; }
        .form-section { background: var(--bg-secondary); border-radius: 0.75rem; padding: 2rem; margin-bottom: 2rem; border: 1px solid var(--border-color); }
        .form-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: var(--text-primary); border-bottom: 2px solid var(--green-primary); padding-bottom: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary); }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-primary); color: var(--text-primary); font-family: inherit; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: var(--green-primary); box-shadow: 0 0 0 3px rgba(45, 122, 80, 0.1); }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .btn-primary { background: var(--green-primary); color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary:hover { background: #1a5c3a; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45, 122, 80, 0.3); }
        .adjustment-card { background: var(--bg-secondary); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; border-left: 4px solid var(--green-primary); border: 1px solid var(--border-color); border-left: 4px solid var(--green-primary); }
        .adjustment-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
        .adjustment-subject { font-weight: 600; color: var(--text-primary); }
        .status-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
        .status-pending { background: rgba(255, 193, 7, 0.1); color: #f57f17; }
        .status-approved { background: rgba(76, 175, 80, 0.1); color: #388e3c; }
        .status-rejected { background: rgba(244, 67, 54, 0.1); color: #d32f2f; }
        .adjustment-details { font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.6; }
        .adjustment-reason { background: var(--bg-primary); padding: 1rem; border-radius: 0.5rem; margin: 0.75rem 0; border-left: 3px solid var(--green-primary); }
        .adjustment-proposed { background: rgba(45, 122, 80, 0.05); padding: 1rem; border-radius: 0.5rem; margin: 0.75rem 0; border-left: 3px solid var(--green-primary); }
        .priority-high { color: #d32f2f; }
        .priority-medium { color: #f57f17; }
        .priority-low { color: #388e3c; }
        .success-message { background: rgba(76, 175, 80, 0.1); border-left: 4px solid #388e3c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; color: var(--text-primary); display: none; }
        .tabs { display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; font-weight: 600; color: var(--text-secondary); border-bottom: 2px solid transparent; transition: all 0.3s; }
        .tab.active { color: var(--green-primary); border-bottom-color: var(--green-primary); }
    </style>

    @include('partials.teacher-page-banner', [
        'pageTitle' => 'Request Schedule Adjustments',
        'pageSubtitle' => 'Submit schedule change requests for team review and approval',
    ])

    <div id="success-message" class="success-message"></div>

    <!-- Tabs -->
    <div class="tabs">
        <div class="tab active" onclick="switchTab('new-request')"> New Request</div>
        <div class="tab" onclick="switchTab('my-requests')"> My Requests</div>
    </div>

    <!-- New Request Form -->
    <div id="new-request" class="tab-content">
        <div class="form-section">
            <h2 class="form-title">Create Adjustment Request</h2>

            <form id="adjustmentForm" onsubmit="handleSubmit(event)">
                @csrf
                
                <div class="form-group">
                    <label for="schedule_id">Select Schedule *</label>
                    <select id="schedule_id" name="schedule_id" required onchange="loadScheduleDetails()">
                        <option value="">-- Choose Schedule --</option>
                        <option value="sched_001">Mathematics - Monday 09:00 AM - Room A101</option>
                        <option value="sched_002">Physics - Wednesday 10:30 AM - Room B205</option>
                        <option value="sched_003">Chemistry - Friday 02:00 PM - Room C301</option>
                    </select>
                </div>

                <div id="schedule-details" style="background: var(--bg-primary); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; border: 1px solid var(--border-color); display: none;">
                    <p style="margin: 0; font-size: 0.875rem; color: var(--text-secondary);">Current Schedule Details:</p>
                    <p id="details-text" style="margin: 0.5rem 0 0 0; color: var(--text-primary); font-weight: 500;"></p>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Adjustment *</label>
                    <textarea 
                        id="reason" 
                        name="reason" 
                        placeholder="Explain why this adjustment is needed (minimum 10 characters)"
                        minlength="10"
                        required></textarea>
                </div>

                <div class="form-group">
                    <label for="proposed_change">Proposed Change *</label>
                    <textarea 
                        id="proposed_change" 
                        name="proposed_change" 
                        placeholder="Describe the specific changes you're requesting (e.g., change time from 9 AM to 10 AM, move to different room, etc.)"
                        minlength="10"
                        required></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label for="priority">Priority Level *</label>
                        <select id="priority" name="priority" required>
                            <option value="low"> Low - Can be scheduled later</option>
                            <option value="medium" selected> Medium - Should be addressed soon</option>
                            <option value="high"> High - Urgent, time-sensitive</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="impact">Expected Impact</label>
                        <select id="impact" name="impact">
                            <option value="">-- Select Impact --</option>
                            <option value="faculty">Affects Faculty Members</option>
                            <option value="students">Affects Students</option>
                            <option value="resources">Affects Resources/Rooms</option>
                            <option value="multiple">Multiple Areas Affected</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn-primary"> Submit Request</button>
                    <button type="reset" style="padding: 0.75rem 1.5rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); border-radius: 0.5rem; cursor: pointer;">Clear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- My Requests List -->
    <div id="my-requests" class="tab-content" style="display: none;">
        <div id="requests-list" style="margin-bottom: 2rem;">
            <p style="text-align: center; color: var(--text-secondary);">Loading your requests...</p>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.style.display = 'none';
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).style.display = 'block';
            event.target.classList.add('active');

            // Load requests if my-requests tab
            if (tabName === 'my-requests') {
                loadMyRequests();
            }
        }

        function loadScheduleDetails() {
            const scheduleId = document.getElementById('schedule_id').value;
            if (scheduleId) {
                document.getElementById('schedule-details').style.display = 'block';
                document.getElementById('details-text').textContent = 'Schedule details loaded for: ' + document.getElementById('schedule_id').options[document.getElementById('schedule_id').selectedIndex].text;
            } else {
                document.getElementById('schedule-details').style.display = 'none';
            }
        }

        function handleSubmit(e) {
            e.preventDefault();

            const formData = {
                schedule_id: document.getElementById('schedule_id').value,
                reason: document.getElementById('reason').value,
                proposed_change: document.getElementById('proposed_change').value,
                priority: document.getElementById('priority').value,
                impact: document.getElementById('impact').value || null
            };

            fetch('/api/stl/request-adjustment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
                },
                body: JSON.stringify(formData)
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    document.getElementById('success-message').textContent = ' ' + d.message;
                    document.getElementById('success-message').style.display = 'block';
                    document.getElementById('adjustmentForm').reset();
                    document.getElementById('schedule-details').style.display = 'none';
                    setTimeout(() => {
                        document.getElementById('success-message').style.display = 'none';
                        loadMyRequests();
                    }, 3000);
                } else {
                    alert('Error: ' + (d.error || 'Failed to submit request'));
                }
            })
            .catch(e => alert('Error: ' + e.message));
        }

        function loadMyRequests() {
            fetch('/api/stl/my-adjustment-requests')
                .then(r => r.json())
                .then(d => {
                    if (d.success && d.requests && d.requests.length > 0) {
                        const html = d.requests.map(req => `
                            <div class="adjustment-card">
                                <div class="adjustment-header">
                                    <div>
                                        <p class="adjustment-subject">${req.schedule_subject}</p>
                                        <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: var(--text-secondary);">Requested: ${req.created_at}</p>
                                    </div>
                                    <span class="status-badge status-${req.status.toLowerCase()}">${req.status.toUpperCase()}</span>
                                </div>

                                <div class="adjustment-details">
                                    <div style="margin-bottom: 1rem;">
                                        <strong>Priority:</strong>
                                        <span class="priority-${req.priority.toLowerCase()}">${req.priority.toUpperCase()}</span>
                                    </div>
                                </div>

                                <div>
                                    <p style="font-weight: 600; margin: 0.75rem 0 0.5rem 0; color: var(--text-primary);">Reason:</p>
                                    <div class="adjustment-reason">${req.reason}</div>
                                </div>

                                <div>
                                    <p style="font-weight: 600; margin: 0.75rem 0 0.5rem 0; color: var(--text-primary);">Proposed Change:</p>
                                    <div class="adjustment-proposed">${req.proposed_change}</div>
                                </div>

                                ${req.status === 'rejected' && req.rejection_reason ? `
                                    <div style="background: rgba(244, 67, 54, 0.1); padding: 1rem; border-radius: 0.5rem; margin-top: 1rem; border-left: 3px solid #d32f2f;">
                                        <p style="font-weight: 600; margin: 0 0 0.5rem 0; color: #d32f2f;">Rejection Reason:</p>
                                        <p style="margin: 0; color: var(--text-primary);">${req.rejection_reason}</p>
                                    </div>
                                ` : ''}
                            </div>
                        `).join('');
                        document.getElementById('requests-list').innerHTML = html;
                    } else {
                        document.getElementById('requests-list').innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No requests submitted yet</p>';
                    }
                })
                .catch(e => {
                    console.error('Error loading requests:', e);
                    document.getElementById('requests-list').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Error loading requests</p>';
                });
        }
    </script>
@endsection
