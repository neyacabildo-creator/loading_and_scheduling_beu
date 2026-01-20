@extends('layouts.teacher')

@section('title', 'Print / Export')

@section('content')
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Print / Export</h1>
        </div>
    </div>

    <!-- Export Options -->
    <div class="content-grid">
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Export Your Data</h2>
            </div>

            <div style="padding: 1.5rem;">
                <!-- Select What to Export -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #2d3436;">What would you like to export?</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="export-type" value="schedule" checked style="margin-right: 0.5rem;">
                            <span>Schedule</span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="export-type" value="classes" style="margin-right: 0.5rem;">
                            <span>Classes</span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="export-type" value="load" style="margin-right: 0.5rem;">
                            <span>Teaching Load</span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="export-type" value="all" style="margin-right: 0.5rem;">
                            <span>All Data</span>
                        </label>
                    </div>
                </div>

                <!-- Select Format -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #2d3436;">Export Format</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="format" value="pdf" checked style="margin-right: 0.5rem;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                                <path d="M7 2a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2H7z" fill="currentColor" opacity="0.3"/>
                                <text x="10" y="16" font-size="8" text-anchor="middle" fill="currentColor">PDF</text>
                            </svg>
                            <span>PDF</span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="format" value="excel" style="margin-right: 0.5rem;">
                            <svg width="20" height="20" fill="#16a34a" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                                <path d="M7 2a2 2 0 00-2 2v16a2 2 0 002 2h10a2 2 0 002-2V4a2 2 0 00-2-2H7z"/>
                            </svg>
                            <span>Excel</span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="format" value="csv" style="margin-right: 0.5rem;">
                            <span>CSV</span>
                        </label>
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; cursor: pointer; transition: all 0.2s;">
                            <input type="radio" name="format" value="print" style="margin-right: 0.5rem;">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 0.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                            </svg>
                            <span>Print</span>
                        </label>
                    </div>
                </div>

                <!-- Include Options -->
                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #2d3436;">Include</label>
                    <div style="display: grid; gap: 0.5rem;">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" checked style="margin-right: 0.5rem;">
                            <span>Header Information</span>
                        </label>
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" checked style="margin-right: 0.5rem;">
                            <span>Details</span>
                        </label>
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" checked style="margin-right: 0.5rem;">
                            <span>Footer/Signature</span>
                        </label>
                    </div>
                </div>

                <!-- Export Buttons -->
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button onclick="exportData()" style="padding: 0.75rem 1.5rem; background: #2d7a50; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        <span>Export</span>
                    </button>
                    <button onclick="printData()" style="padding: 0.75rem 1.5rem; background: #3b82f6; color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.5rem;">
                        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span>Print</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview -->
    <div class="content-grid" style="margin-top: 2rem;">
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Preview</h2>
            </div>
            <div id="preview" style="padding: 1.5rem; background: white; border-radius: 0.5rem; max-height: 400px; overflow-y: auto;">
                <p style="color: #999; text-align: center;">Select an option and click Export/Print to see preview</p>
            </div>
        </div>
    </div>

    <script>
        function exportData() {
            const exportType = document.querySelector('input[name="export-type"]:checked').value;
            const format = document.querySelector('input[name="format"]:checked').value;

            if (format === 'print') {
                printData();
                return;
            }

            // Simulate export
            alert(`Exporting ${exportType} as ${format.toUpperCase()}...\n\nThis would download your data in the selected format.`);
            
            // In a real implementation, you would:
            // fetch(`/api/teacher/export?type=${exportType}&format=${format}`)
            // .then(response => response.blob())
            // .then(blob => {
            //     const url = window.URL.createObjectURL(blob);
            //     const a = document.createElement('a');
            //     a.href = url;
            //     a.download = `export-${exportType}.${format}`;
            //     a.click();
            // });
        }

        function printData() {
            const exportType = document.querySelector('input[name="export-type"]:checked').value;

            const printContent = `
                <h2>Faculty Teaching Load & Schedule Report</h2>
                <p><strong>Export Type:</strong> ${exportType}</p>
                <p><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
                <p>Your ${exportType} data would be printed here.</p>
            `;

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print - ${exportType}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 2rem; }
                        h2 { color: #2d7a50; }
                        p { margin: 0.5rem 0; }
                        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
                        th, td { padding: 0.5rem; text-align: left; border: 1px solid #ddd; }
                        th { background: #2d7a50; color: white; }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
    </script>

    <style>
        input[type="radio"], input[type="checkbox"] {
            cursor: pointer;
        }

        label:hover {
            background: rgba(45, 122, 80, 0.05) !important;
        }

        button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
    </style>
@endsection
