@php
    $jhGrid = $stWeeklyTimetable['jh'] ?? ['slots' => [], 'cells' => []];
    $gsGrid = $stWeeklyTimetable['gs'] ?? ['slots' => [], 'cells' => []];
    $days = $stWeeklyTimetable['days'] ?? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
@endphp
<style>
.st-tt-panel { background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 1rem; margin-bottom: 1.75rem; overflow: hidden; }
.st-tt-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 1.1rem 1.25rem; border-bottom: 1px solid var(--border-color); }
.st-tt-head h2 { margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text-primary); }
.st-tt-tabs { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.st-tt-tab { padding: 0.35rem 0.85rem; border-radius: 9999px; border: 1px solid var(--border-color); background: var(--bg-primary); font-size: 0.78rem; font-weight: 600; cursor: pointer; color: var(--text-secondary); }
.st-tt-tab.active { background: var(--green-primary); border-color: var(--green-primary); color: #fff; }
.st-tt-tab.jh.active { background: #2563eb; border-color: #2563eb; }
.st-tt-days { display: flex; gap: 0.25rem; padding: 0.65rem 1.25rem; border-bottom: 1px solid var(--border-color); flex-wrap: wrap; }
.st-tt-day { padding: 0.35rem 0.7rem; border: 1px solid var(--border-color); border-radius: 0.375rem; background: var(--bg-secondary); font-size: 0.78rem; font-weight: 600; cursor: pointer; color: var(--text-secondary); }
.st-tt-day.active { background: var(--green-primary); border-color: var(--green-primary); color: #fff; }
.st-tt-table-wrap { overflow-x: auto; padding: 0 1rem 1rem; }
.st-tt-table { width: 100%; border-collapse: collapse; min-width: 520px; }
.st-tt-table th, .st-tt-table td { border: 1px solid var(--border-color); padding: 0.55rem 0.65rem; font-size: 0.8rem; vertical-align: top; }
.st-tt-table th { background: var(--bg-primary); font-weight: 700; color: var(--text-secondary); text-align: left; white-space: nowrap; }
.st-tt-cell-filled { background: rgba(45, 122, 80, 0.08); }
.st-tt-cell-filled.jh { background: rgba(37, 99, 235, 0.08); }
.st-tt-subject { font-weight: 700; color: var(--text-primary); margin: 0 0 0.15rem; }
.st-tt-meta { font-size: 0.72rem; color: var(--text-secondary); margin: 0; }
.st-tt-empty { color: var(--text-secondary); font-style: italic; }
</style>

<div class="st-tt-panel">
    <div class="st-tt-head">
        <h2>Weekly Timetable</h2>
        <div class="st-tt-tabs">
            <button type="button" class="st-tt-tab jh active" data-level="jh">Junior High</button>
            <button type="button" class="st-tt-tab gs" data-level="gs">Grade School</button>
            <button type="button" class="st-tt-tab" data-level="all">Combined</button>
        </div>
    </div>
    <div class="st-tt-days" id="stTtDayBar"></div>
    <div class="st-tt-table-wrap">
        <table class="st-tt-table">
            <thead><tr id="stTtHeadRow"><th>Time</th><th>Class</th></tr></thead>
            <tbody id="stTtBody"></tbody>
        </table>
    </div>
</div>

<script>
window.__ST_WEEKLY_TIMETABLE__ = {
    days: @json($days),
    jh: @json($jhGrid, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    gs: @json($gsGrid, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
};
(function () {
    const cfg = window.__ST_WEEKLY_TIMETABLE__;
    let level = 'jh';
    let day = cfg.days[0] || 'Monday';

    const dayBar = document.getElementById('stTtDayBar');
    const body = document.getElementById('stTtBody');

    function renderDayButtons() {
        dayBar.innerHTML = '';
        cfg.days.forEach(function (d) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'st-tt-day' + (d === day ? ' active' : '');
            btn.textContent = d.slice(0, 3);
            btn.dataset.day = d;
            btn.addEventListener('click', function () {
                day = d;
                renderDayButtons();
                renderGrid();
            });
            dayBar.appendChild(btn);
        });
    }

    function cellFor(levelKey, slotOrder) {
        const grid = cfg[levelKey];
        if (!grid || !grid.cells || !grid.cells[day]) return null;
        return grid.cells[day][slotOrder] || null;
    }

    function renderGrid() {
        const slots = (level === 'jh' ? cfg.jh : level === 'gs' ? cfg.gs : null);
        const jhSlots = cfg.jh.slots || [];
        const gsSlots = cfg.gs.slots || [];
        const useSlots = level === 'all'
            ? (jhSlots.length >= gsSlots.length ? jhSlots : gsSlots)
            : (slots && slots.slots ? slots.slots : []);

        body.innerHTML = '';
        if (!useSlots.length) {
            body.innerHTML = '<tr><td colspan="2" class="st-tt-empty">No timetable slots configured.</td></tr>';
            return;
        }

        useSlots.forEach(function (slot) {
            const tr = document.createElement('tr');
            const timeTd = document.createElement('td');
            timeTd.textContent = (slot.label || slot.start) + (slot.end ? ' – ' + slot.end : '');
            tr.appendChild(timeTd);

            const classTd = document.createElement('td');
            if (level === 'all') {
                const jh = cellFor('jh', slot.order);
                const gs = cellFor('gs', slot.order);
                if (!jh && !gs) {
                    classTd.innerHTML = '<span class="st-tt-empty">—</span>';
                } else {
                    let html = '';
                    if (jh) html += '<div class="st-tt-cell-filled jh" style="margin-bottom:.35rem;padding:.35rem;border-radius:.35rem;"><p class="st-tt-subject">' + escapeHtml(jh.subject) + '</p><p class="st-tt-meta">JH · ' + escapeHtml(jh.grade_section) + '</p></div>';
                    if (gs) html += '<div class="st-tt-cell-filled" style="padding:.35rem;border-radius:.35rem;"><p class="st-tt-subject">' + escapeHtml(gs.subject) + '</p><p class="st-tt-meta">GS · ' + escapeHtml(gs.grade_section) + '</p></div>';
                    classTd.innerHTML = html;
                }
            } else {
                const cell = cellFor(level, slot.order);
                if (!cell) {
                    classTd.innerHTML = '<span class="st-tt-empty">—</span>';
                } else {
                    classTd.className = 'st-tt-cell-filled' + (level === 'jh' ? ' jh' : '');
                    classTd.innerHTML = '<p class="st-tt-subject">' + escapeHtml(cell.subject) + '</p><p class="st-tt-meta">' + escapeHtml(cell.grade_section) + '</p>';
                }
            }
            tr.appendChild(classTd);
            body.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    document.querySelectorAll('.st-tt-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.st-tt-tab').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            level = tab.dataset.level;
            renderGrid();
        });
    });

    renderDayButtons();
    renderGrid();
})();
</script>
