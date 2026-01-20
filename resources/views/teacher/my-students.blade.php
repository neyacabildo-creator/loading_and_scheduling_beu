@extends('layouts.teacher')

@section('title', 'My Students')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">My Students</h1>
        </div>
    </div>

    <!-- Students Summary Stats -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Students</p>
                    <p class="stat-value" id="total-students">0</p>
                    <p class="stat-change">Across all classes</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Classes</p>
                    <p class="stat-value" id="total-classes">0</p>
                    <p class="stat-change">With students</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Students by Class -->
    <div class="content-grid">
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Student Distribution by Class</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Grade/Section</th>
                        <th>Number of Students</th>
                        <th>Class Count</th>
                        <th>Total Load</th>
                    </tr>
                </thead>
                <tbody id="students-list">
                    <tr class="loading">
                        <td colspan="6" style="text-align: center; padding: 2rem;">Loading student data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .loading { color: #999; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadStudents();
        });

        function loadStudents() {
            fetch('/api/teacher/students')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayStudents(data.data, data.total_students);
                    } else {
                        document.getElementById('students-list').innerHTML = 
                            '<tr><td colspan="6" style="text-align: center; color: #f87171;">Error loading student data</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function displayStudents(students, totalStudents) {
            document.getElementById('total-students').textContent = totalStudents;
            document.getElementById('total-classes').textContent = students.length;

            if (students.length === 0) {
                document.getElementById('students-list').innerHTML = 
                    '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #999;">No student data available.</td></tr>';
                return;
            }

            const html = students.map((student, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${student.subject}</strong></td>
                    <td>${student.grade_section}</td>
                    <td><span class="badge-students">${student.total_students}</span></td>
                    <td>${student.class_count}</td>
                    <td>${parseInt(student.total_students) * parseInt(student.class_count)}</td>
                </tr>
            `).join('');

            document.getElementById('students-list').innerHTML = html;
        }
    </script>

    <style>
        .badge-students {
            display: inline-block;
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.875rem;
        }
    </style>
@endsection
