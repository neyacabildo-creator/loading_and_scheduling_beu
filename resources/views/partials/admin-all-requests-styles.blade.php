<style>
/* ── All Requests: panels & tables (JH / GS admin) ── */
.str-pending-pill {
    background: #ef4444;
    color: #fff;
    font-size: 0.78rem;
    font-weight: 700;
    border-radius: 9999px;
    padding: 4px 14px;
}
.str-flash-success,
.flash-success.str-flash-success {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    background: rgba(45, 122, 80, 0.1);
    border: 1px solid rgba(45, 122, 80, 0.3);
    color: #2d7a50;
    padding: 0.875rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.str-panel {
    background: var(--bg-secondary);
    border: 1px solid rgba(45, 122, 80, 0.22);
    border-left: 4px solid var(--green-primary, #2d7a50);
    border-radius: 0.75rem;
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0, 0, 0, 0.08));
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.str-panel-header {
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, rgba(45, 122, 80, 0.14) 0%, rgba(45, 122, 80, 0.04) 100%);
    border-bottom: 1px solid rgba(45, 122, 80, 0.18);
}
.str-panel-header .str-section-title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--green-primary, #2d7a50);
}
.str-panel-header .str-section-desc {
    margin: 0.35rem 0 0;
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.45;
}
.str-panel-body {
    padding: 0;
    background: var(--bg-secondary);
}
.str-panel-body .str-empty-state {
    margin: 0;
    background: var(--bg-secondary);
}
.str-panel-body .str-table-wrap {
    padding: 0;
}

html[data-theme="dark"] .str-panel {
    background: var(--bg-secondary) !important;
    border-color: var(--border-color) !important;
}

html[data-theme="dark"] .str-panel-body {
    background: var(--bg-secondary) !important;
}
html[data-theme="dark"] .str-panel-header {
    background: linear-gradient(135deg, rgba(45, 122, 80, 0.15) 0%, var(--bg-primary) 100%) !important;
}

html[data-theme="dark"] .str-data-table {
    background: var(--bg-secondary) !important;
}

html[data-theme="dark"] .str-data-table thead tr {
    background: var(--bg-primary) !important;
}

html[data-theme="dark"] .str-data-table thead th {
    background: var(--bg-primary) !important;
    color: var(--text-secondary) !important;
    border-color: var(--border-color) !important;
}

html[data-theme="dark"] .str-data-table tbody td {
    background: var(--bg-secondary) !important;
    color: var(--text-tertiary) !important;
    border-color: var(--border-color) !important;
}

html[data-theme="dark"] .str-data-table tbody tr:nth-child(even) td {
    background: var(--bg-tertiary) !important;
}

html[data-theme="dark"] .str-data-table tbody tr:hover td {
    background: rgba(45, 122, 80, 0.12) !important;
}

html[data-theme="dark"] .str-data-table tbody td.str-col-teacher,
html[data-theme="dark"] .str-data-table tbody td.str-col-subject,
html[data-theme="dark"] .str-data-table .str-teacher-name,
html[data-theme="dark"] .str-data-table .str-day-line {
    color: var(--text-primary) !important;
}

.str-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 0.35rem;
}
.str-section-desc {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin: 0 0 1rem;
}
.str-section-block {
    margin-top: 0;
}

.str-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

/* Match admin .table-card / faculty-loading table colors */
.str-data-table {
    width: 100%;
    min-width: 960px;
    border-collapse: collapse;
    font-size: 0.875rem;
    table-layout: fixed;
    background: var(--bg-secondary);
}

.str-data-table thead th {
    background: transparent;
    padding: 1rem 1.25rem;
    text-align: left;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--green-primary, #2d7a50);
    border-bottom: 1px solid rgba(45, 122, 80, 0.2);
    white-space: nowrap;
    vertical-align: middle;
}

.str-data-table thead tr {
    background: linear-gradient(180deg, rgba(45, 122, 80, 0.18) 0%, rgba(45, 122, 80, 0.06) 100%);
}

.str-data-table tbody td {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-tertiary);
    background: var(--bg-secondary);
    vertical-align: top;
    line-height: 1.45;
    overflow: hidden;
}

.str-data-table tbody tr:nth-child(even) td {
    background: var(--bg-primary);
}

.str-data-table tbody tr:hover td {
    background: var(--bg-tertiary) !important;
}

.str-data-table tbody tr:last-child td {
    border-bottom: none;
}

/* Column widths — Notes centered between Preferred Day & Time and Submitted */
.str-col-teacher { width: 11%; }
.str-col-subject { width: 11%; }
.str-col-grade   { width: 11%; }
.str-col-time    { width: 16%; }
.str-col-notes   { width: 14%; text-align: center; }
.str-col-date    { width: 10%; text-align: right; }
.str-col-status  { width: 8%; }
.str-col-actions { width: 19%; min-width: 260px; }

.str-data-table thead th.str-col-notes {
    text-align: center;
    padding-left: 1.5rem;
    padding-right: 1.5rem;
}
.str-data-table tbody td.str-col-notes {
    text-align: center;
    padding-left: 1.25rem;
    padding-right: 1.25rem;
}
.str-data-table tbody td.str-col-notes .str-notes-text,
.str-data-table tbody td.str-col-notes .str-notes-reason {
    display: inline-block;
    max-width: 100%;
    text-align: center;
    margin: 0 auto;
}
.str-data-table thead th.str-col-date,
.str-data-table tbody td.str-col-date {
    text-align: right;
}

.str-teacher-requests-table .str-cell-daytime,
.str-teacher-requests-table .str-cell-notes,
.str-teacher-requests-table .str-cell-grade {
    word-break: break-word;
}
.str-request-type-meta {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-top: 0.25rem;
    text-transform: capitalize;
}
.str-notes-reason {
    font-size: 0.82rem;
    color: var(--text-primary);
    line-height: 1.45;
    word-break: break-word;
}
.str-notes-detail {
    font-size: 0.78rem;
    color: var(--text-secondary);
    margin-top: 0.35rem;
    line-height: 1.4;
    word-break: break-word;
}

.str-data-table tbody td.str-col-teacher,
.str-data-table tbody td.str-col-subject,
.str-data-table tbody td.str-cell-subject {
    color: var(--text-primary);
}

.str-teacher-cell {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
    min-width: 0;
}
.str-teacher-cell-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.35rem 0.5rem;
    width: 100%;
}
.str-teacher-name {
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1.35;
    word-break: normal;
}
.str-teacher-meta,
.str-school-level-badge {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 500;
    color: #065f46;
    background: #d1fae5;
    padding: 0.15rem 0.55rem;
    border-radius: 9999px;
    white-space: nowrap;
    line-height: 1.4;
    margin-top: 0;
}
.str-cell-subject {
    font-weight: 500;
}
.str-day-line {
    font-weight: 500;
    color: var(--text-primary);
}
.str-time-line {
    font-size: 0.78rem;
    color: var(--text-secondary);
    margin-top: 0.2rem;
    white-space: nowrap;
}
.str-notes-text {
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-break: break-word;
    font-size: 0.8rem;
    color: var(--text-secondary);
    line-height: 1.5;
}
.str-date-cell {
    font-size: 0.78rem;
    white-space: nowrap;
    color: var(--text-secondary);
}

/* Status badges (match app) */
.str-data-table .status-pending,
.str-data-table .status-approved,
.str-data-table .status-rejected {
    display: inline-block;
    padding: 0.2rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: capitalize;
}
.str-data-table .status-pending  { background: rgba(245, 158, 11, 0.15); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.35); }
.str-data-table .status-approved { background: rgba(45, 122, 80, 0.15); color: #2d7a50; border: 1px solid rgba(45, 122, 80, 0.35); }
.str-data-table .status-rejected { background: rgba(107, 114, 128, 0.12); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.25); }

/* Compact side-by-side approve / reject */
.str-actions-inline {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 0.45rem;
    flex-wrap: nowrap;
}
.str-action-form {
    margin: 0;
    display: inline-flex;
}
.str-actions-inline .str-btn-approve,
.str-actions-inline .str-btn-reject {
    width: auto;
    min-width: 5.5rem;
    padding: 0.4rem 0.85rem;
    font-size: 0.75rem;
    white-space: nowrap;
}

.schedule-actions-row {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 0.4rem;
    flex-wrap: nowrap;
}
.schedule-actions-row .action-btn {
    margin: 0;
    flex-shrink: 0;
}

.str-action-panel form {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    margin: 0;
}
.str-action-input {
    width: 100%;
    box-sizing: border-box;
    padding: 0.35rem 0.5rem;
    font-size: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    background: var(--bg-secondary);
    color: var(--text-primary);
}
.str-action-input:focus {
    outline: none;
    border-color: var(--green-primary);
}
.str-btn-approve,
.str-btn-reject {
    width: 100%;
    border: none;
    padding: 0.4rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s, transform 0.1s;
}
.str-btn-approve {
    background: var(--green-primary, #2d7a50);
    color: #fff;
}
.str-btn-approve:hover { background: #1a5336; }
.str-btn-reject {
    background: #ef4444;
    color: #fff;
}
.str-btn-reject:hover { background: #dc2626; }

.str-reviewed-box {
    font-size: 0.78rem;
    color: var(--text-secondary);
    line-height: 1.55;
    padding: 0.35rem 0;
}
.str-reviewed-quote {
    color: var(--text-primary);
    font-style: italic;
    margin-bottom: 0.35rem;
    word-break: break-word;
}

.str-empty-state {
    padding: 3rem;
    text-align: center;
    color: var(--text-secondary);
}
.str-empty-state p {
    font-weight: 500;
    margin: 0;
}

.str-presence-badge {
    display: inline-block;
    margin-top: 0.35rem;
    padding: 0.15rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}
.str-presence-absent {
    background: rgba(244, 67, 54, 0.15);
    color: #c62828;
}
.str-presence-on_leave {
    background: rgba(255, 152, 0, 0.15);
    color: #e65100;
}

.str-absent-alert {
    background: rgba(255, 152, 0, 0.12);
    border: 1px solid rgba(230, 126, 34, 0.35);
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
    color: var(--text-primary);
}
.str-absent-alert strong {
    display: block;
    margin-bottom: 0.65rem;
    font-size: 0.9rem;
}
.str-absent-group {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
    margin-top: 0.35rem;
}
.str-absent-group-label {
    font-weight: 600;
    color: var(--text-secondary);
    margin-right: 0.25rem;
}
.str-absent-chip {
    display: inline-block;
    padding: 0.25rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.72rem;
    font-weight: 600;
}
.str-absent-chip em {
    font-style: normal;
    opacity: 0.9;
    font-weight: 500;
}

@media (max-width: 1100px) {
    .str-actions-grid {
        grid-template-columns: 1fr;
    }
    .str-data-table {
        min-width: 880px;
    }
}
</style>
