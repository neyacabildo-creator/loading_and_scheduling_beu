{{-- resources/views/grade-school-admin/reports/index.blade.php --}}
@extends('layouts.grade-school-admin')

@section('title', 'Grade School - Reports')

@section('content')
    <style>
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
        .section-title { font-size: 1.25rem; font-weight: 700; color: #2d3436; margin: 2rem 0 1rem 0; }
        .table-card { background: white; border-radius: 0.75rem; border: 1px solid #e8dcc8; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 2rem; }
        .table-header { padding: 1.5rem; border-bottom: 1px solid #e8dcc8; }
        table { width: 100%; border-collapse: collapse; }
        th { padding: 1rem 1.5rem; background: #f5f3ed; text-align: left; font-weight: 600; color: #2d3436; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; }
        td { padding: 1rem 1.5rem; border-bottom: 1px solid #e8dcc8; font-size: 0.875rem; }
        tr:hover { background: #fafaf8; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600; }
        .badge-completed { background: #dcfce7; color: #22863a; }
        .badge-pending { background: #fef3c7; color: #b8860b; }

        html[data-theme="dark"] .report-card,
        html[data-theme="dark"] .table-card {
            background: #2d2d2d;
            border-color: #404040;
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
        }

        html[data-theme="dark"] .report-title,
        html[data-theme="dark"] .section-title,
        html[data-theme="dark"] .table-header > div,
        html[data-theme="dark"] td {
            color: #e0e0e0;
        }

        html[data-theme="dark"] .report-description,
        html[data-theme="dark"] .report-meta,
        html[data-theme="dark"] .badge-pending {
            color: #b0b0b0;
        }

        html[data-theme="dark"] .report-meta {
            border-top-color: #404040;
            border-bottom-color: #404040;
        }

        html[data-theme="dark"] th {
            background: #3a3a3a;
            color: #e0e0e0;
            border-bottom-color: #404040;
        }

        html[data-theme="dark"] td {
            border-bottom-color: #404040;
        }

        html[data-theme="dark"] tr:hover {
            background: #343434;
        }

        html[data-theme="dark"] .badge-completed {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }

        html[data-theme="dark"] .badge-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fcd34d;
        }
    </style>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1 class="page-title">Grade School Reports</h1>
        </div>
        <div class="header-right"></div>
    </div>

    <!-- Report Templates -->
    <h2 class="section-title">Available Report Templates</h2>
    <div class="report-grid">
        <div class="report-card">
            <div class="report-icon icon-bg-1"></div>
            <h3 class="report-title">Master Loading Summary</h3>
            <p class="report-description">Institution-wide summary of faculty loading, assignments, and total teaching load distribution.</p>
            <div class="report-meta">
                <span>Format: CSV, JSON</span>
                <span>Operational</span>
            </div>
            <button class="report-btn report-generate-btn" data-type="master_loading_summary">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon icon-bg-2"></div>
            <h3 class="report-title">Complete Schedule Listing</h3>
            <p class="report-description">Complete institutional schedule listing with subject, section, teacher, room, and time allocations.</p>
            <div class="report-meta">
                <span>Format: CSV, JSON</span>
                <span>Operational</span>
            </div>
            <button class="report-btn report-generate-btn" data-type="complete_schedule_listing">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon icon-bg-3"></div>
            <h3 class="report-title">Workload Analytics</h3>
            <p class="report-description">Workload metrics for institutional monitoring including average load, overload, and underload indicators.</p>
            <div class="report-meta">
                <span>Format: CSV, JSON</span>
                <span>Analytics</span>
            </div>
            <button class="report-btn report-generate-btn" data-type="workload_analytics">Generate Report</button>
        </div>

        <div class="report-card">
            <div class="report-icon icon-bg-4"></div>
            <h3 class="report-title">Compliance Report</h3>
            <p class="report-description">Compliance and monitoring report for approvals, conflicts, and schedule data quality checks.</p>
            <div class="report-meta">
                <span>Format: CSV, JSON</span>
                <span>Compliance</span>
            </div>
            <button class="report-btn report-generate-btn" data-type="compliance_report">Generate Report</button>
        </div>
    </div>

    <!-- Recent Reports -->
    <h2 class="section-title">Recently Generated Reports</h2>
    <div class="table-card">
        <div class="table-header">
            <div style="font-size: 1.125rem; font-weight: 600; color: #2d3436;">Report History</div>
        </div>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Report Name</th>
                        <th>Type</th>
                        <th>Generated Date</th>
                        <th>Generated By</th>
                        <th>File Size</th>
                        <th>Status</th>
                        <th>Format</th>
                    </tr>
                </thead>
                <tbody id="report-history-body">
                    <tr>
                        <td colspan="7" style="text-align:center;color:#7a7a6e;">Loading report history...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const generateBaseUrl = "{{ route('grade-school-admin.reports.generate', ['type' => '__TYPE__']) }}";
        const historyUrl = "{{ route('grade-school-admin.reports.history') }}";

        function formatBytes(bytes) {
            if (!bytes || bytes <= 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB'];
            let i = 0;
            let val = bytes;
            while (val >= 1024 && i < units.length - 1) {
                val /= 1024;
                i++;
            }
            return `${val.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
        }

        function statusBadge(status) {
            const value = (status || 'processing').toLowerCase();
            if (value === 'completed') return '<span class="badge badge-completed">Completed</span>';
            if (value === 'failed') return '<span class="badge" style="background:#fee2e2;color:#991b1b;">Failed</span>';
            return '<span class="badge badge-pending">Processing</span>';
        }

        async function loadHistory() {
            const tbody = document.getElementById('report-history-body');
            try {
                const res = await fetch(historyUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const payload = await res.json();
                const rows = payload?.data || [];

                if (!rows.length) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#7a7a6e;">No reports generated yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = rows.map((row) => `
                    <tr>
                        <td><strong>${row.filename || '-'}</strong></td>
                        <td>${(row.report_type || '-').replaceAll('_', ' ')}</td>
                        <td>${row.generated_at || '-'}</td>
                        <td>${row.generated_by || '-'}</td>
                        <td>${formatBytes(row.file_size || 0)}</td>
                        <td>${statusBadge(row.status)}</td>
                        <td>${row.format || 'CSV'}</td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#991b1b;">Failed to load report history.</td></tr>';
            }
        }

        async function generateReport(type, button) {
            const url = generateBaseUrl.replace('__TYPE__', type) + '?format=csv';
            const original = button.textContent;

            button.disabled = true;
            button.textContent = 'Generating...';

            try {
                const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!response.ok) throw new Error('Failed to generate report');

                const blob = await response.blob();
                const link = document.createElement('a');
                const objectUrl = URL.createObjectURL(blob);
                const disposition = response.headers.get('Content-Disposition') || '';
                const match = disposition.match(/filename="?([^";]+)"?/i);
                link.href = objectUrl;
                link.download = match ? match[1] : `${type}.csv`;
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(objectUrl);

                await loadHistory();
            } catch (err) {
                alert('Unable to generate report. Please try again.');
            } finally {
                button.disabled = false;
                button.textContent = original;
            }
        }

        document.querySelectorAll('.report-generate-btn').forEach((btn) => {
            btn.addEventListener('click', () => generateReport(btn.dataset.type, btn));
        });

        loadHistory();
    </script>
@endsection
