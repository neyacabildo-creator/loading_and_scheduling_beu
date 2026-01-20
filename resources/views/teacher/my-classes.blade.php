@extends('layouts.teacher')

@section('title', 'My Classes')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">My Classes</h1>
        </div>
    </div>

    <!-- Classes List -->
    <div class="content-grid">
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Active Classes</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Grade/Section</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Room</th>
                        <th>Students</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="classes-list">
                    <tr class="loading">
                        <td colspan="8" style="text-align: center; padding: 2rem;">Loading classes...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .loading { color: #999; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .status-badge.active { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
    </style>

    <script>
        // Load classes on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadClasses();
        });

        function loadClasses() {
            fetch('/api/teacher/classes')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        displayClasses(data.data);
                    } else {
                        document.getElementById('classes-list').innerHTML = 
                            '<tr><td colspan="8" style="text-align: center; padding: 2rem; color: #999;">No active classes found.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('classes-list').innerHTML = 
                        '<tr><td colspan="8" style="text-align: center; color: #f87171;">Error loading classes</td></tr>';
                });
        }

        function displayClasses(classes) {
            const html = classes.map((cls, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${cls.subject}</strong></td>
                    <td>${cls.grade_section}</td>
                    <td>${cls.day_of_week}</td>
                    <td>${cls.start_time} - ${cls.end_time}</td>
                    <td>${cls.room?.room_name || 'TBA'}</td>
                    <td><span class="badge-info">${cls.student_count}</span></td>
                    <td><span class="status-badge active">Active</span></td>
                </tr>
            `).join('');
            
            document.getElementById('classes-list').innerHTML = html;
        }
    </script>

    <style>
        .badge-info {
            display: inline-block;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
@endsection
