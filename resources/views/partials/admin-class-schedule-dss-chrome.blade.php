{{-- Conflict DSS banner + legend for class-schedule pages. Expects $dssPrefix: jh|gs --}}
@php $pfx = $dssPrefix ?? 'jh'; @endphp

@once
@push('scripts')
    <script src="{{ asset('js/admin-class-schedule-dss.js') }}?v=1" defer></script>
@endpush
<style>
    .cs-dss-banner {
        display: none;
        background: rgba(200, 50, 50, .09);
        border: 1px solid #c83232;
        border-radius: .625rem;
        padding: .85rem 1.15rem;
        margin-bottom: 1.25rem;
        color: var(--text-primary);
    }
    .cs-dss-banner-head {
        display: flex;
        align-items: flex-start;
        gap: .65rem;
        flex-wrap: wrap;
    }
    .cs-dss-banner-icon { color: #c83232; font-size: 1.1rem; line-height: 1; flex-shrink: 0; }
    .cs-dss-banner-title { font-size: .88rem; font-weight: 700; color: #c83232; margin: 0; }
    .cs-dss-banner-lead { font-size: .8rem; color: var(--text-secondary); margin: .25rem 0 0; line-height: 1.45; }
    .cs-dss-conflict-list {
        margin: .6rem 0 0 1.1rem;
        padding: 0;
        font-size: .78rem;
        color: var(--text-primary);
        line-height: 1.5;
    }
    .cs-dss-conflict-list li { margin-bottom: .2rem; }
    .cs-dss-legend {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem 1.25rem;
        font-size: .72rem;
        color: var(--text-secondary);
        margin-bottom: 1.25rem;
        padding: .5rem .75rem;
        background: var(--bg-tertiary);
        border-radius: .5rem;
        border: 1px solid var(--border-color);
    }
    .cs-dss-legend-item { display: inline-flex; align-items: center; gap: .35rem; }
    .cs-dss-swatch {
        width: 12px;
        height: 12px;
        border-radius: 2px;
        flex-shrink: 0;
    }
    .cs-dss-swatch-conflict { background: #c83232; }
    .cs-dss-swatch-missing { background: #d97706; }
    tr.cs-row-conflict td { background: rgba(200, 50, 50, .07) !important; }
    tr.cs-row-conflict td:first-child { box-shadow: inset 3px 0 0 #c83232; }
    tr.cs-row-missing td { background: rgba(217, 119, 6, .07) !important; }
    tr.cs-row-missing td:first-child { box-shadow: inset 3px 0 0 #d97706; }
    .cs-dss-pill {
        display: inline-block;
        font-size: .62rem;
        font-weight: 700;
        border-radius: 9999px;
        padding: 1px 6px;
        margin-right: 4px;
        vertical-align: middle;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .cs-dss-pill-conflict { background: rgba(200, 50, 50, .15); color: #c83232; }
    .cs-dss-pill-warn { background: rgba(217, 119, 6, .15); color: #b45309; }
</style>
@endonce

<div id="{{ $pfx }}ConflictBanner" class="cs-dss-banner" role="alert">
    <div class="cs-dss-banner-head">
        <span class="cs-dss-banner-icon" aria-hidden="true">&#9888;</span>
        <div style="flex:1;min-width:200px;">
            <p class="cs-dss-banner-title">
                <span id="{{ $pfx }}ConflictCount">0</span> scheduling conflict(s) detected
            </p>
            <p id="{{ $pfx }}ConflictLead" class="cs-dss-banner-lead">Checking schedules…</p>
            <div id="{{ $pfx }}ConflictList"></div>
        </div>
    </div>
</div>

<div class="cs-dss-legend" aria-label="Schedule grid legend">
    <span class="cs-dss-legend-item"><span class="cs-dss-swatch cs-dss-swatch-conflict"></span> Red — time conflict (double-booked)</span>
    <span class="cs-dss-legend-item"><span class="cs-dss-swatch cs-dss-swatch-missing"></span> Amber — missing schedule date or room</span>
</div>
