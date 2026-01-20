@extends('layouts.teacher')

@section('title', 'Class Performance')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Class Performance Analysis</h1>
        </div>
    </div>

    <!-- Performance Stats -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Classes</p>
                    <p class="stat-value" id="stat-classes">0</p>
                    <p class="stat-change">This semester</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Students</p>
                    <p class="stat-value" id="stat-students">0</p>
                    <p class="stat-change">All classes combined</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Avg Class Size</p>
                    <p class="stat-value" id="stat-average">0</p>
                    <p class="stat-change">Students per class</p>
                </div>
                <div class="stat-icon warning">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Classes Performance Table -->
    <div class="content-grid">
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Class-wise Performance</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject</th>
                        <th>Grade/Section</th>
                        <th>Students</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody id="performance-list">
                    <tr class="loading">
                        <td colspan="7" style="text-align: center; padding: 2rem;">Loading performance data...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadPerformance();
        });

        function loadPerformance() {
            fetch('/api/teacher/performance')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayPerformance(data.data, data.stats);
                    } else {
                        document.getElementById('performance-list').innerHTML = 
                            '<tr><td colspan="7" style="text-align: center; color: #f87171;">Error loading performance data</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function displayPerformance(classes, stats) {
            // Update stats
            document.getElementById('stat-classes').textContent = stats.total_classes;
            document.getElementById('stat-students').textContent = stats.total_students;
            document.getElementById('stat-average').textContent = stats.average_class_size;

            if (classes.length === 0) {
                document.getElementById('performance-list').innerHTML = 
                    '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #999;">No class performance data available.</td></tr>';
                return;
            }

            const html = classes.map((cls, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${cls.subject}</strong></td>
                    <td>${cls.grade_section}</td>
                    <td><span class="badge-size">${cls.student_count}</span></td>
                    <td>${cls.day_of_week}</td>
                    <td>${cls.start_time} - ${cls.end_time}</td>
                    <td>
                        <div class="progress-bar">
                            <div class="progress" style="width: ${(cls.student_count / 50) * 100}%"></div>
                        </div>
                    </td>
                </tr>
            `).join('');

            document.getElementById('performance-list').innerHTML = html;
        }
    </script>

    <style>
        .badge-size {
            display: inline-block;
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: linear-gradient(90deg, #22c55e, #16a34a);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
    </style>
@endsection
