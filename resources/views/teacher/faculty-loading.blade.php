@extends('layouts.teacher')

@section('title', 'Faculty Loading')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Faculty Teaching Load</h1>
        </div>
    </div>

    <!-- Loading Stats -->
    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Total Units</p>
                    <p class="stat-value" id="total-units">0</p>
                    <p class="stat-change">This semester</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                </div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <p class="stat-label">Classes</p>
                    <p class="stat-value" id="total-classes">0</p>
                    <p class="stat-change">Per week</p>
                </div>
                <div class="stat-icon success">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Schedule -->
    <div class="content-grid">
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Weekly Schedule & Load Distribution</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Subject</th>
                        <th>Grade/Section</th>
                        <th>Room</th>
                        <th>Load</th>
                    </tr>
                </thead>
                <tbody id="load-schedule">
                    <tr class="loading">
                        <td colspan="6" style="text-align: center; padding: 2rem;">Loading schedule...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary by Day -->
    <div class="content-grid" style="margin-top: 2rem;">
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Load by Day of Week</h2>
            </div>
            <div id="day-summary" style="padding: 1.5rem;">
                <div class="loading">Loading...</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            loadFacultyLoad();
        });

        function loadFacultyLoad() {
            fetch('/api/teacher/faculty-load')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayFacultyLoad(data);
                    } else {
                        document.getElementById('load-schedule').innerHTML = 
                            '<tr><td colspan="6" style="text-align: center; color: #f87171;">Error loading schedule</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function displayFacultyLoad(data) {
            // Update stats
            document.getElementById('total-units').textContent = data.total_units;
            document.getElementById('total-classes').textContent = data.schedules.length;

            if (data.schedules.length === 0) {
                document.getElementById('load-schedule').innerHTML = 
                    '<tr><td colspan="6" style="text-align: center; padding: 2rem; color: #999;">No schedule available.</td></tr>';
                return;
            }

            // Display schedule
            const html = data.schedules.map(s => `
                <tr>
                    <td><strong>${s.day_of_week}</strong></td>
                    <td>${s.start_time} - ${s.end_time}</td>
                    <td>${s.subject}</td>
                    <td>${s.grade_section}</td>
                    <td>${s.room?.room_name || 'TBA'}</td>
                    <td><span class="badge-unit">1 unit</span></td>
                </tr>
            `).join('');

            document.getElementById('load-schedule').innerHTML = html;

            // Display day summary
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            let daySummary = '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">';
            
            days.forEach(day => {
                const dayClasses = data.schedules.filter(s => s.day_of_week === day);
                daySummary += `
                    <div style="padding: 1rem; background: linear-gradient(135deg, rgba(45,122,80,0.1) 0%, rgba(45,122,80,0.05) 100%); border-radius: 0.75rem; border-left: 3px solid #2d7a50;">
                        <div style="font-weight: 600; color: #2d3436; margin-bottom: 0.5rem;">${day}</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #2d7a50; margin-bottom: 0.5rem;">${dayClasses.length}</div>
                        <div style="font-size: 0.75rem; color: #7a7a6e;">class${dayClasses.length !== 1 ? 'es' : ''}</div>
                    </div>
                `;
            });

            daySummary += '</div>';
            document.getElementById('day-summary').innerHTML = daySummary;
        }
    </script>

    <style>
        .badge-unit {
            display: inline-block;
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
    </style>
@endsection
