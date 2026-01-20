{{-- resources/views/admin/print-export.blade.php --}}
@extends('layouts.admin')

@section('title', 'Print / Export')

@section('content')
    <style>
        .export-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .export-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; }
        .export-icon { font-size: 2.5rem; margin-bottom: 1rem; }
        .export-title { font-size: 1.125rem; font-weight: 600; color: #2d3436; margin-bottom: 0.5rem; }
        .export-desc { font-size: 0.875rem; color: #7a7a6e; margin-bottom: 1.5rem; }
        .export-btn { padding: 0.75rem 1rem; background: #2d7a50; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; width: 100%; transition: all 0.2s; }
        .export-btn:hover { background: #1a5336; }
        .export-btn.secondary { background: white; color: #2d7a50; border: 1px solid #2d7a50; }
        .export-btn.secondary:hover { background: rgba(45,122,80,0.05); }
        
        .options-section { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem; margin-bottom: 2rem; }
        .options-title { font-size: 1.25rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem; }
        .option-group { margin-bottom: 1.5rem; }
        .option-label { font-weight: 600; color: #2d3436; margin-bottom: 0.75rem; }
        .checkboxes { display: flex; flex-direction: column; gap: 0.5rem; }
        .checkbox-item { display: flex; align-items: center; gap: 0.5rem; }
        .checkbox-item input { cursor: pointer; }
        .checkbox-item label { cursor: pointer; font-size: 0.875rem; color: #7a7a6e; }
        .select-group { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-label { font-weight: 600; color: #2d3436; margin-bottom: 0.5rem; font-size: 0.875rem; }
        .form-input { padding: 0.75rem; border: 1px solid #e8dcc8; border-radius: 0.5rem; }
        .submit-btns { display: flex; gap: 1rem; }
        .submit-btns button { flex: 1; padding: 0.75rem 1.5rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.875rem; transition: all 0.2s; }
        .submit-btns .primary { background: #2d7a50; color: white; }
        .submit-btns .primary:hover { background: #1a5336; }
        .submit-btns .secondary { background: #f5f3ed; color: #2d3436; border: 1px solid #e8dcc8; }
        .submit-btns .secondary:hover { background: #e8dcc8; }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <button class="search-btn">
                <svg width="20" height="20" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
            <h1 class="page-title">Print / Export</h1>
        </div>
    </div>

    <!-- Quick Export Options -->
    <h2 style="font-size: 1.25rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem;">Quick Export</h2>
    <div class="export-grid">
        <!-- PDF Export -->
        <div class="export-card">
            <div class="export-icon">📄</div>
            <div class="export-title">PDF Report</div>
            <div class="export-desc">Export schedules as PDF document for printing or sharing</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button class="export-btn" onclick="exportPDF()">Export as PDF</button>
                <button class="export-btn secondary" onclick="printPreview()">Print Preview</button>
            </div>
        </div>

        <!-- Excel Export -->
        <div class="export-card">
            <div class="export-icon">📊</div>
            <div class="export-title">Excel Spreadsheet</div>
            <div class="export-desc">Export data to Excel format for further analysis</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button class="export-btn" onclick="exportExcel()">Export as Excel</button>
                <button class="export-btn secondary" onclick="viewPreview()">Preview Data</button>
            </div>
        </div>

        <!-- CSV Export -->
        <div class="export-card">
            <div class="export-icon">📋</div>
            <div class="export-title">CSV File</div>
            <div class="export-desc">Export data in CSV format for compatibility</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button class="export-btn" onclick="exportCSV()">Export as CSV</button>
                <button class="export-btn secondary" onclick="copyToClipboard()">Copy Data</button>
            </div>
        </div>

        <!-- Print Direct -->
        <div class="export-card">
            <div class="export-icon">🖨️</div>
            <div class="export-title">Print Schedules</div>
            <div class="export-desc">Print schedules directly to your configured printer</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button class="export-btn" onclick="window.print()">Print Now</button>
                <button class="export-btn secondary" onclick="printSettings()">Print Settings</button>
            </div>
        </div>

        <!-- Archive -->
        <div class="export-card">
            <div class="export-icon">📦</div>
            <div class="export-title">Archive Download</div>
            <div class="export-desc">Download all schedules as compressed archive</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button class="export-btn" onclick="downloadArchive()">Download Archive</button>
                <button class="export-btn secondary" onclick="selectFormats()">Select Format</button>
            </div>
        </div>

        <!-- Email Report -->
        <div class="export-card">
            <div class="export-icon">📧</div>
            <div class="export-title">Email Report</div>
            <div class="export-desc">Send schedules via email to selected recipients</div>
            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                <button class="export-btn" onclick="emailReport()">Send Email</button>
                <button class="export-btn secondary" onclick="configureEmail()">Configure</button>
            </div>
        </div>
    </div>

    <!-- Advanced Options -->
    <h2 style="font-size: 1.25rem; font-weight: 600; color: #2d3436; margin-bottom: 1.5rem;">Export Options</h2>
    <div class="options-section">
        <div class="option-group">
            <div class="option-label">Select Data to Export</div>
            <div class="checkboxes">
                <div class="checkbox-item">
                    <input type="checkbox" id="schedules" checked>
                    <label for="schedules">Class Schedules</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="faculty" checked>
                    <label for="faculty">Faculty Information</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="rooms" checked>
                    <label for="rooms">Room Assignments</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="students">
                    <label for="students">Student Roster</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="analytics" checked>
                    <label for="analytics">Analytics & Reports</label>
                </div>
            </div>
        </div>

        <div class="option-group">
            <div class="option-label">Filter Options</div>
            <div class="select-group">
                <div class="form-group">
                    <label class="form-label">Grade Level</label>
                    <select class="form-input">
                        <option value="">All Grades</option>
                        <option value="5">Grade 5</option>
                        <option value="6">Grade 6</option>
                        <option value="7">Grade 7</option>
                        <option value="8">Grade 8</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Date Range</label>
                    <input type="month" class="form-input" value="2026-01">
                </div>
            </div>
        </div>

        <div class="option-group">
            <div class="option-label">Include Metadata</div>
            <div class="checkboxes">
                <div class="checkbox-item">
                    <input type="checkbox" id="timestamp" checked>
                    <label for="timestamp">Export date/time stamp</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="footer" checked>
                    <label for="footer">Add footer with school info</label>
                </div>
                <div class="checkbox-item">
                    <input type="checkbox" id="colors">
                    <label for="colors">Include color formatting</label>
                </div>
            </div>
        </div>

        <div class="submit-btns">
            <button class="primary" onclick="exportWithOptions()">Export with Options</button>
            <button class="secondary" onclick="resetOptions()">Reset Options</button>
        </div>
    </div>

    <!-- Recent Exports -->
    <div style="background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; box-shadow: 0 1px 3px rgba(0,0,0,0.08); padding: 1.5rem;">
        <h3 style="font-size: 1.125rem; font-weight: 600; color: #2d3436; margin-bottom: 1rem;">Recent Exports</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #e8dcc8;">
                    <th style="text-align: left; padding: 0.75rem; color: #7a7a6e; font-weight: 600; font-size: 0.875rem;">File Name</th>
                    <th style="text-align: left; padding: 0.75rem; color: #7a7a6e; font-weight: 600; font-size: 0.875rem;">Format</th>
                    <th style="text-align: left; padding: 0.75rem; color: #7a7a6e; font-weight: 600; font-size: 0.875rem;">Date</th>
                    <th style="text-align: left; padding: 0.75rem; color: #7a7a6e; font-weight: 600; font-size: 0.875rem;">Size</th>
                    <th style="text-align: left; padding: 0.75rem; color: #7a7a6e; font-weight: 600; font-size: 0.875rem;">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr style="border-bottom: 1px solid #e8dcc8;">
                    <td style="padding: 1rem; color: #2d3436;">schedules_2026_01_18.pdf</td>
                    <td style="padding: 1rem; color: #2d3436;">PDF</td>
                    <td style="padding: 1rem; color: #7a7a6e;">Jan 18, 2026</td>
                    <td style="padding: 1rem; color: #7a7a6e;">2.4 MB</td>
                    <td style="padding: 1rem;"><a href="#" style="color: #2d7a50; text-decoration: none; font-weight: 500;">Download</a></td>
                </tr>
                <tr style="border-bottom: 1px solid #e8dcc8;">
                    <td style="padding: 1rem; color: #2d3436;">faculty_load_jan2026.xlsx</td>
                    <td style="padding: 1rem; color: #2d3436;">Excel</td>
                    <td style="padding: 1rem; color: #7a7a6e;">Jan 16, 2026</td>
                    <td style="padding: 1rem; color: #7a7a6e;">856 KB</td>
                    <td style="padding: 1rem;"><a href="#" style="color: #2d7a50; text-decoration: none; font-weight: 500;">Download</a></td>
                </tr>
                <tr style="border-bottom: 1px solid #e8dcc8;">
                    <td style="padding: 1rem; color: #2d3436;">all_data_2026_01_15.csv</td>
                    <td style="padding: 1rem; color: #2d3436;">CSV</td>
                    <td style="padding: 1rem; color: #7a7a6e;">Jan 15, 2026</td>
                    <td style="padding: 1rem; color: #7a7a6e;">512 KB</td>
                    <td style="padding: 1rem;"><a href="#" style="color: #2d7a50; text-decoration: none; font-weight: 500;">Download</a></td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        function exportPDF() { alert('Exporting to PDF...\nSchedules_2026_01_18.pdf'); }
        function printPreview() { alert('Opening print preview...'); }
        function exportExcel() { alert('Exporting to Excel...\nSchedules_2026_01_18.xlsx'); }
        function viewPreview() { alert('Loading data preview...'); }
        function exportCSV() { alert('Exporting to CSV...\nSchedules_2026_01_18.csv'); }
        function copyToClipboard() { alert('Data copied to clipboard!'); }
        function printSettings() { alert('Opening print settings...'); }
        function downloadArchive() { alert('Preparing archive for download...'); }
        function selectFormats() { alert('Selecting archive format...'); }
        function emailReport() { alert('Opening email composer...'); }
        function configureEmail() { alert('Email configuration panel...'); }
        function exportWithOptions() { alert('Exporting with selected options...'); }
        function resetOptions() { alert('Options reset to defaults'); }
    </script>

@endsection
