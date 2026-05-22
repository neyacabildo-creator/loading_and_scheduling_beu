{{-- Faculty Workload History - STL View --}}
@extends('layouts.teacher')

@section('title', 'Faculty Workload History')

@section('content')
    <style>
        .header-section { background: linear-gradient(135deg, var(--green-primary) 0%, #0d3d20 100%); color: white; padding: 2rem; border-radius: 0.75rem; margin-bottom: 2rem; }
        .header-title { font-size: 1.875rem; font-weight: bold; margin: 0; }
        .faculty-card { background: var(--bg-secondary); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid var(--border-color); }
        .faculty-name { font-size: 1.125rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
        .stat-item { background: var(--bg-primary); padding: 1rem; border-radius: 0.5rem; text-align: center; border: 1px solid var(--border-color); }
        .stat-value { font-size: 1.75rem; font-weight: bold; color: var(--green-primary); margin: 0.5rem 0; }
        .stat-label { font-size: 0.875rem; color: var(--text-secondary); margin: 0; }
        .trend-indicator { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; margin-top: 0.5rem; }
        .trend-stable { background: rgba(76, 175, 80, 0.1); color: #388e3c; }
        .trend-up { background: rgba(255, 0, 0, 0.1); color: #d32f2f; }
        .trend-down { background: rgba(76, 175, 80, 0.1); color: #388e3c; }
        .history-chart { margin-top: 1.5rem; background: var(--bg-primary); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border-color); }
        .chart-title { font-weight: 600; color: var(--text-primary); margin-bottom: 1rem; }
        .chart-bar { display: flex; align-items: center; margin-bottom: 0.75rem; }
        .chart-label { width: 80px; font-size: 0.75rem; color: var(--text-secondary); }
        .chart-value { flex: 1; height: 24px; background: linear-gradient(90deg, var(--green-primary), #0d3d20); border-radius: 4px; margin: 0 0.5rem; position: relative; min-width: 50px; }
        .chart-number { position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); color: white; font-size: 0.75rem; font-weight: 600; }
        .filter-bar { background: var(--bg-secondary); padding: 1rem; border-radius: 0.75rem; margin-bottom: 2rem; border: 1px solid var(--border-color); display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        .filter-select { padding: 0.5rem 0.75rem; border: 1px solid var(--border-color); background: var(--bg-primary); color: var(--text-primary); border-radius: 0.25rem; }
        .summary-section { background: var(--bg-secondary); border-radius: 0.75rem; padding: 2rem; margin-top: 2rem; border: 1px solid var(--border-color); }
        .summary-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: var(--text-primary); }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
        .summary-item { background: var(--bg-primary); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); text-align: center; }
        .summary-value { font-size: 2rem; font-weight: bold; color: var(--green-primary); margin: 0.5rem 0; }
        .summary-label { font-size: 0.875rem; color: var(--text-secondary); }
    </style>

    <div style="background:linear-gradient(135deg,#1a5336 0%,#2d7a50 60%,#3d9970 100%);border-radius:.75rem;padding:2rem;margin-bottom:2rem;">
        <h1 style="color:white;font-size:1.75rem;font-weight:800;margin:0 0 .3rem;">Workload History</h1>
        <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin:0;">Historical analysis and trends for subject team faculty</p>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <span style="font-weight: 600; color: var(--text-primary);">Filter:</span>
        <select class="filter-select" onchange="filterByStatus(this.value)">
            <option value="all">All Faculty</option>
            <option value="active">Active Only</option>
            <option value="overloaded">Overloaded</option>
        </select>
        <select class="filter-select" onchange="filterByPeriod(this.value)" style="margin-left: auto;">
            <option value="current">Current Period</option>
            <option value="semester1">Semester 1</option>
            <option value="semester2">Semester 2</option>
            <option value="annual">Annual</option>
        </select>
    </div>

    <!-- Faculty Workload Cards -->
    <div id="faculty-container" style="margin-bottom: 2rem;">
        <p style="text-align: center; color: var(--text-secondary);">Loading faculty workload data...</p>
    </div>

    <!-- Team Summary -->
    <div class="summary-section">
        <h2 class="summary-title"> Team Workload Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <p class="summary-label">Total Faculty</p>
                <p class="summary-value" id="total-faculty">0</p>
            </div>
            <div class="summary-item">
                <p class="summary-label">Average Load</p>
                <p class="summary-value" id="avg-load">0</p>
                <p class="summary-label">hours</p>
            </div>
            <div class="summary-item">
                <p class="summary-label">Total Load</p>
                <p class="summary-value" id="total-load">0</p>
                <p class="summary-label">hours</p>
            </div>
            <div class="summary-item">
                <p class="summary-label">Max Load</p>
                <p class="summary-value" id="max-load">0</p>
                <p class="summary-label">hours</p>
            </div>
        </div>
    </div>

    <script>
        let allFacultyData = [];

        function loadFacultyWorkloadHistory() {
            fetch('/api/stl/workload-history')
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        allFacultyData = d.faculties || [];
                        displayFacultyCards(allFacultyData);
                        updateSummary(allFacultyData);
                    }
                })
                .catch(e => {
                    console.error('Error loading workload history:', e);
                    document.getElementById('faculty-container').innerHTML = '<p style="text-align: center; color: var(--text-secondary);">Error loading workload history</p>';
                });
        }

        function displayFacultyCards(faculties) {
            if (!faculties || faculties.length === 0) {
                document.getElementById('faculty-container').innerHTML = '<p style="text-align: center; color: var(--text-secondary); padding: 2rem;">No faculty data available</p>';
                return;
            }

            const html = faculties.map(faculty => {
                const trend = faculty.trend === 'up' ? ' Increasing' : faculty.trend === 'down' ? ' Decreasing' : ' Stable';
                const trendClass = faculty.trend === 'up' ? 'trend-up' : faculty.trend === 'down' ? 'trend-down' : 'trend-stable';
                
                return `
                    <div class="faculty-card">
                        <div class="faculty-name"> ${faculty.name}</div>
                        
                        <div class="stat-grid">
                            <div class="stat-item">
                                <p class="stat-label">Total Load</p>
                                <p class="stat-value">${faculty.total_load}</p>
                                <p class="stat-label">hours</p>
                            </div>
                            <div class="stat-item">
                                <p class="stat-label">Assignments</p>
                                <p class="stat-value">${faculty.assignments}</p>
                                <p class="stat-label">subjects</p>
                            </div>
                            <div class="stat-item">
                                <p class="stat-label">Average Load</p>
                                <p class="stat-value">${faculty.average_load}</p>
                                <p class="stat-label">per subject</p>
                            </div>
                            <div class="stat-item">
                                <p class="stat-label">Trend</p>
                                <p class="stat-value" style="font-size: 1rem;">
                                    <span class="trend-indicator ${trendClass}">${trend}</span>
                                </p>
                            </div>
                        </div>

                        <div class="history-chart">
                            <div class="chart-title">Load Distribution</div>
                            <div class="chart-bar">
                                <div class="chart-label">Current</div>
                                <div class="chart-value" style="width: ${Math.min(faculty.total_load * 3, 100)}%;">
                                    <span class="chart-number">${faculty.total_load}h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('faculty-container').innerHTML = html;
        }

        function updateSummary(faculties) {
            if (!faculties || faculties.length === 0) return;

            const totalFaculty = faculties.length;
            const totalLoad = faculties.reduce((sum, f) => sum + parseFloat(f.total_load || 0), 0);
            const avgLoad = totalFaculty > 0 ? (totalLoad / totalFaculty).toFixed(1) : 0;
            const maxLoad = Math.max(...faculties.map(f => parseFloat(f.total_load || 0)));

            document.getElementById('total-faculty').textContent = totalFaculty;
            document.getElementById('avg-load').textContent = avgLoad;
            document.getElementById('total-load').textContent = totalLoad.toFixed(0);
            document.getElementById('max-load').textContent = maxLoad.toFixed(0);
        }

        function filterByStatus(status) {
            let filtered = allFacultyData;
            if (status !== 'all') {
                if (status === 'active') {
                    filtered = allFacultyData.filter(f => f.assignments > 0);
                } else if (status === 'overloaded') {
                    filtered = allFacultyData.filter(f => parseFloat(f.total_load) > 30);
                }
            }
            displayFacultyCards(filtered);
            updateSummary(filtered);
        }

        function filterByPeriod(period) {
            // In production, this would fetch data for the selected period
            console.log('Filter by period:', period);
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadFacultyWorkloadHistory);
    </script>
@endsection
