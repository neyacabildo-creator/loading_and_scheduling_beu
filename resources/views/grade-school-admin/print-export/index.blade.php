{{-- resources/views/admin/print-export/index.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Export Reports')

@section('content')
    <style>
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .export-section { background: white; padding: 2rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .section-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem; }
        .export-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .option-card { background: #f5f3ed; padding: 1.5rem; border-radius: 0.5rem; border: 2px solid transparent; cursor: pointer; transition: all 0.2s; text-align: center; }
        .option-card:hover { border-color: #2d7a50; background: white; }
        .option-card.selected { border-color: #2d7a50; background: rgba(45, 122, 80, 0.05); }
        .option-icon { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .option-label { font-size: 0.875rem; font-weight: 600; color: #2d3436; }
        .export-controls { background: #f5f3ed; padding: 1.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; }
        .control-group { margin-bottom: 1rem; }
        .control-label { font-size: 0.875rem; font-weight: 600; color: #2d3436; margin-bottom: 0.5rem; display: block; }
        .control-input { width: 100%; padding: 0.5rem 1rem; border: 1px solid #e8dcc8; border-radius: 0.375rem; font-size: 0.875rem; }
        .button-group { display: flex; gap: 1rem; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; cursor: pointer; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; }
        .btn-primary { background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45, 122, 80, 0.3); }
        .btn-secondary { background: white; border: 1px solid #e8dcc8; color: #2d3436; }
        .btn-secondary:hover { border-color: #2d7a50; color: #2d7a50; }
        .table-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .table-header { padding: 1.5rem; border-bottom: 1px solid #e8dcc8; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 1.5rem; background: #f5f3ed; text-align: left; font-weight: 600; color: #2d3436; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; }
        tr:hover { background: #fafaf8; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
        .badge-ready { background: #dcfce7; color: #22863a; }
        .badge-processing { background: #fef3c7; color: #b8860b; }
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .report-card { background: white; padding: 1.5rem; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); display: flex; flex-direction: column; }
        .report-icon { width: 50px; height: 50px; border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem; font-size: 1.5rem; }
        .report-title { font-size: 1rem; font-weight: 600; color: #2d3436; margin-bottom: 0.5rem; }
        .report-description { font-size: 0.875rem; color: #7a7a6e; line-height: 1.5; margin-bottom: 1rem; flex: 1; }
        .report-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 1rem 0; border-top: 1px solid #e8dcc8; border-bottom: 1px solid #e8dcc8; font-size: 0.75rem; color: #7a7a6e; }
        .report-btn { display: inline-block; padding: 0.5rem 1.5rem; background: linear-gradient(135deg, #2d7a50 0%, #1a5336 100%); color: white; border: none; border-radius: 0.375rem; text-decoration: none; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .report-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(45, 122, 80, 0.3); }
        .icon-bg-1 { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
        .icon-bg-2 { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .icon-bg-3 { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .icon-bg-4 { background: rgba(167, 139, 250, 0.1); color: #a78bfa; }
        .icon-bg-5 { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .icon-bg-6 { background: rgba(236, 72, 153, 0.1); color: #ec4899; }
        .action-btn-small { padding: 0.5rem 0.75rem; border: 1px solid #e8dcc8; background: white; border-radius: 0.375rem; cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #2d3436; transition: all 0.2s; }
        .action-btn-small:hover { border-color: #2d7a50; color: #2d7a50; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Export Reports</h1>
        </div>
    </div>

    <!-- Generate Report Templates -->
    <h2 style="font-size: 1.25rem; font-weight: 700; color: #2d3436; margin: 0 0 1rem 0;"> Generate & Export Report Templates</h2>
    <div class="report-grid">
        <div class="report-card">
            <div class="report-icon icon-bg-1"></div>
            <h3 class="report-title">Master Schedule Report</h3>
            <p class="report-description">Complete institution-wide class schedules with all courses, instructors, sections, rooms, and time slots.</p>
            <div class="report-meta">
                <span>Format: PDF, Excel</span>
                <span>Est. Size: 2-5 MB</span>
            </div>
            <button class="report-btn" onclick="generateReport('master-schedule','pdf')">Generate & Export</button>
        </div>
        <div class="report-card">
            <div class="report-icon icon-bg-2"></div>
            <h3 class="report-title">Faculty Workload Report</h3>
            <p class="report-description">Comprehensive analysis of teaching loads, assignments, course allocations, and workload balance across all faculty.</p>
            <div class="report-meta">
                <span>Format: PDF, Excel</span>
                <span>Est. Size: 1-3 MB</span>
            </div>
            <button class="report-btn" onclick="generateReport('faculty-workload','pdf')">Generate & Export</button>
        </div>
        <div class="report-card">
            <div class="report-icon icon-bg-3"></div>
            <h3 class="report-title">Room Utilization Report</h3>
            <p class="report-description">Analysis of room usage patterns, capacity utilization rates, and peak hour utilization metrics.</p>
            <div class="report-meta">
                <span>Format: PDF, Excel</span>
                <span>Est. Size: 800 KB</span>
            </div>
            <button class="report-btn" onclick="generateReport('room-utilization','pdf')">Generate & Export</button>
        </div>
        <div class="report-card">
            <div class="report-icon icon-bg-4"></div>
            <h3 class="report-title">Conflict Analysis Report</h3>
            <p class="report-description">Detailed listing of all scheduling conflicts, resource constraints, and recommended resolutions.</p>
            <div class="report-meta">
                <span>Format: PDF, Excel</span>
                <span>Est. Size: 1 MB</span>
            </div>
            <button class="report-btn" onclick="generateReport('conflict-analysis','pdf')">Generate & Export</button>
        </div>
        <div class="report-card">
            <div class="report-icon icon-bg-5"></div>
            <h3 class="report-title">Compliance & Audit Report</h3>
            <p class="report-description">Institutional compliance review including adherence to policies, regulations, and operational standards.</p>
            <div class="report-meta">
                <span>Format: PDF, Excel</span>
                <span>Est. Size: 1.5 MB</span>
            </div>
            <button class="report-btn" onclick="generateReport('compliance','pdf')">Generate & Export</button>
        </div>
        <div class="report-card">
            <div class="report-icon icon-bg-6"></div>
            <h3 class="report-title">Custom Report Builder</h3>
            <p class="report-description">Create custom reports with selected data fields, filters, and formatting options for specific needs.</p>
            <div class="report-meta">
                <span>Format: PDF, Excel, CSV</span>
                <span>Flexible</span>
            </div>
            <button class="report-btn" onclick="generateReport('custom','csv')">Build & Export</button>
        </div>
    </div>

    <!-- Export Master Schedule -->
    <div class="export-section">
        <h2 class="section-title"> Master Schedule Export</h2>
        
        <div class="export-options">
            <div class="option-card selected">
                <div class="option-icon"></div>
                <div class="option-label">PDF Document</div>
            </div>
            <div class="option-card">
                <div class="option-icon"></div>
                <div class="option-label">Excel File</div>
            </div>
            <div class="option-card">
                <div class="option-icon"></div>
                <div class="option-label">CSV Format</div>
            </div>
        </div>

        <div class="export-controls">
            <div class="control-group">
                <label class="control-label">Select Schedule</label>
                <select class="control-input">
                    <option>-- Select a schedule --</option>
                    <option>Computer Science Department - 2024</option>
                    <option>Engineering Department - 2024</option>
                    <option>All Departments - 2024</option>
                </select>
            </div>
            <div class="control-group">
                <label class="control-label">Include Sections</label>
                <select class="control-input">
                    <option>All Sections</option>
                    <option>By Department</option>
                    <option>By Year Level</option>
                </select>
            </div>
            <div class="control-group">
                <label class="control-label">Page Orientation</label>
                <select class="control-input">
                    <option>Landscape (Recommended)</option>
                    <option>Portrait</option>
                </select>
            </div>
            <div class="button-group">
                <button class="btn btn-primary"> Export Schedule</button>
                <button class="btn btn-secondary">Preview</button>
            </div>
        </div>
    </div>

    <!-- Export Faculty Workload -->
    <div class="export-section">
        <h2 class="section-title"> Faculty Workload Export</h2>
        
        <div class="export-options">
            <div class="option-card selected">
                <div class="option-icon"></div>
                <div class="option-label">PDF Report</div>
            </div>
            <div class="option-card">
                <div class="option-icon"></div>
                <div class="option-label">Excel Workbook</div>
            </div>
        </div>

        <div class="export-controls">
            <div class="control-group">
                <label class="control-label">Department Filter</label>
                <select class="control-input">
                    <option>All Departments</option>
                    <option>Computer Science</option>
                    <option>Engineering</option>
                    <option>Liberal Arts</option>
                </select>
            </div>
            <div class="control-group">
                <label class="control-label">Include Metrics</label>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #2d3436;">
                        <input type="checkbox" checked> Load Hours
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #2d3436;">
                        <input type="checkbox" checked> Course Assignments
                    </label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 500; color: #2d3436;">
                        <input type="checkbox"> Load Balance Analysis
                    </label>
                </div>
            </div>
            <div class="button-group">
                <button class="btn btn-primary"> Export Workload</button>
                <button class="btn btn-secondary">Preview</button>
            </div>
        </div>
    </div>

    <!-- Export Compliance Report -->
    <div class="export-section">
        <h2 class="section-title"> Compliance Report Export</h2>
        
        <div class="export-options">
            <div class="option-card selected">
                <div class="option-icon"></div>
                <div class="option-label">Formal PDF</div>
            </div>
            <div class="option-card">
                <div class="option-icon"></div>
                <div class="option-label">Executive Summary</div>
            </div>
        </div>

        <div class="export-controls">
            <div class="control-group">
                <label class="control-label">Report Type</label>
                <select class="control-input">
                    <option>Full Compliance Report</option>
                    <option>Summary Report</option>
                    <option>Audit Trail</option>
                </select>
            </div>
            <div class="button-group">
                <button class="btn btn-primary"> Export Compliance</button>
                <button class="btn btn-secondary">Preview</button>
            </div>
        </div>
    </div>

    <!-- Export History -->
    <h2 style="font-size: 1.25rem; font-weight: 700; color: #2d3436; margin: 2rem 0 1rem 0;">Recent Exports</h2>
    <div class="table-card">
        <div class="table-header">
            <div style="font-size: 1.125rem; font-weight: 600; color: #2d3436;">Export History</div>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Export Name</th>
                        <th>Format</th>
                        <th>File Size</th>
                        <th>Exported Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Master Schedule - Jan 2024</strong></td>
                        <td>PDF</td>
                        <td>4.2 MB</td>
                        <td>2024-01-15 11:30 AM</td>
                        <td><span class="badge badge-ready">Ready</span></td>
                        <td style="display: flex; gap: 0.5rem;">
                            <button class="action-btn-small">Download</button>
                            <button class="action-btn-small">Share</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Faculty Workload Report</strong></td>
                        <td>Excel</td>
                        <td>2.1 MB</td>
                        <td>2024-01-14 03:45 PM</td>
                        <td><span class="badge badge-ready">Ready</span></td>
                        <td style="display: flex; gap: 0.5rem;">
                            <button class="action-btn-small">Download</button>
                            <button class="action-btn-small">Share</button>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Compliance Report</strong></td>
                        <td>PDF</td>
                        <td>1.8 MB</td>
                        <td>2024-01-13 10:00 AM</td>
                        <td><span class="badge badge-ready">Ready</span></td>
                        <td style="display: flex; gap: 0.5rem;">
                            <button class="action-btn-small">Download</button>
                            <button class="action-btn-small">Share</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function generateReport(type, format) {
            var btn = event.target;
            var original = btn.textContent;
            btn.textContent = 'Generating…';
            btn.disabled = true;
            fetch('/grade-school-admin/export/generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                body: JSON.stringify({ type: type, format: format })
            })
            .then(function(res) {
                if (res.ok) return res.blob();
                throw new Error('Export failed');
            })
            .then(function(blob) {
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = type + '-report.' + format;
                a.click();
                URL.revokeObjectURL(url);
            })
            .catch(function() {
                alert('Report generation is not yet connected to a backend endpoint. Please configure the /grade-school-admin/export/generate route.');
            })
            .finally(function() {
                btn.textContent = original;
                btn.disabled = false;
            });
        }

        // Option card selection
        document.querySelectorAll('.option-card').forEach(function(card) {
            card.addEventListener('click', function() {
                var parent = this.closest('.export-options');
                parent.querySelectorAll('.option-card').forEach(function(c) { c.classList.remove('selected'); });
                this.classList.add('selected');
            });
        });
    </script>

@endsection
