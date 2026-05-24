{{-- Shared screen + print styles for admin print/export schedule pages --}}
<style>
.pe-filter-card{background:var(--bg-secondary);border:1px solid var(--border-color);border-radius:.75rem;padding:1.5rem;margin-bottom:1.5rem;box-shadow:var(--shadow-sm);}
.pe-filter-row{display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;}
.pe-fgroup{display:flex;flex-direction:column;gap:.35rem;}
.pe-fgroup label{font-size:.78rem;font-weight:700;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.04em;}
.pe-select,.pe-input{padding:.55rem .85rem;border:1px solid var(--border-color);border-radius:.375rem;background:var(--bg-secondary);color:var(--text-primary);font-size:.875rem;min-width:150px;}
.pe-select:focus,.pe-input:focus{outline:none;border-color:var(--green-primary);}
.pe-btn{padding:.6rem 1.4rem;border:none;border-radius:.45rem;cursor:pointer;font-weight:600;font-size:.85rem;transition:all .2s;text-decoration:none;display:inline-flex;align-items:center;gap:.3rem;}
.pe-btn-primary{background:linear-gradient(135deg,var(--green-primary),#0d3d20);color:#fff;}
.pe-btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(45,122,80,.3);}
.pe-btn-secondary{background:var(--bg-secondary);border:1px solid var(--border-color);color:var(--text-primary);}
.pe-btn-secondary:hover{border-color:var(--green-primary);color:var(--green-primary);}
.pe-action-bar{display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem;}
.pe-empty{background:var(--bg-secondary);border:2px dashed var(--border-color);border-radius:.75rem;padding:3rem;text-align:center;color:var(--text-secondary);}
.pe-day-block{margin-bottom:2.5rem;}
.pe-day-title{font-size:.9rem;font-weight:700;color:var(--green-primary);text-transform:uppercase;letter-spacing:.07em;margin-bottom:.6rem;padding-bottom:.4rem;border-bottom:2px solid var(--green-primary);}
.pe-sched-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.pe-sched-table th{padding:.5rem .55rem;background:#1a5336;color:#fff;border:1px solid #0d2e1e;text-align:center;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;}
.pe-sched-table td{padding:.38rem .45rem;border:1px solid var(--border-color);text-align:center;vertical-align:middle;font-size:.76rem;}
.pe-sched-table .time-cell{background:var(--bg-tertiary);font-weight:700;color:var(--text-primary);white-space:nowrap;min-width:70px;font-size:.72rem;}
.pe-sched-table .break-row td{background:rgba(245,158,11,.08);font-weight:700;color:#92400e;font-size:.71rem;letter-spacing:.06em;}
.pe-cell-entry{margin:.1rem 0;}
.pe-cell-subject{font-weight:700;color:var(--text-primary);}
.pe-cell-teacher{font-size:.7rem;color:var(--text-secondary);}
.pe-empty-cell{color:#ccc;font-size:.68rem;}
/* Printable document header */
.pe-print-header{display:none;text-align:center;margin-bottom:1.5rem;padding:0 0 1rem;border-bottom:3px double #1a5336;}
.pe-print-brand{display:flex;align-items:center;justify-content:center;gap:1rem;margin-bottom:.85rem;}
.pe-print-logo{width:52px;height:52px;object-fit:contain;flex-shrink:0;}
.pe-print-brand-text{text-align:left;}
.pe-print-school{font-size:11pt;font-weight:800;margin:0;color:#1a5336;text-transform:uppercase;letter-spacing:.04em;line-height:1.25;}
.pe-print-subtitle{font-size:9pt;margin:.2rem 0 0;color:#444;font-weight:600;}
.pe-print-title{font-size:12pt;font-weight:700;margin:0 0 .35rem;text-transform:uppercase;letter-spacing:.05em;color:#1a5336;}
.pe-print-meta{font-size:9pt;color:#555;margin:0;}
.pe-print-footer-note{font-size:7.5pt;color:#888;margin:.5rem 0 0;font-style:italic;}
@media print {
    body *{visibility:hidden;}
    #pe-printable,#pe-printable *{visibility:visible;}
    #pe-printable{position:absolute;top:0;left:0;width:100%;}
    .pe-sched-table{font-size:8pt;}
    .pe-sched-table th{background:#1a5336!important;color:#fff!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .pe-sched-table .break-row td{background:rgba(245,158,11,.08)!important;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
    .pe-print-header{display:block!important;}
    .pe-print-school,.pe-print-title,.pe-day-title{color:#1a5336!important;}
    .pe-day-title{border-bottom-color:#1a5336!important;}
    .pe-day-block{page-break-inside:avoid;}
}
</style>
